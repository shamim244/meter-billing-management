<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\CompressionNegotiator;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdaptiveCompressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        SystemSetting::clearRuntimeCache();
        CompressionNegotiator::clearRuntimeCache();

        // Register temporary test routes to evaluate middleware behavior across content-types
        Route::middleware(['api'])->group(function () {
            Route::get('/test-compression/json', function () {
                return response()->json([
                    'status' => 'ok',
                    'message' => 'Adaptive Compression JSON Payload',
                    'dataset' => array_fill(0, 100, [
                        'ca_number' => '88019928371',
                        'consumer_name' => 'SHAMIM AHMAD TEST CONSUMER',
                        'reading' => 1450,
                        'units' => 65,
                    ]),
                ]);
            });

            Route::get('/test-compression/html', function () {
                $html = '<html><head><title>NBPDCL Consumer Portal</title></head><body>'
                    .str_repeat('<div><p>Consumer Bill Details: Meter No MTR-998822 Reading 1520 Units</p></div>', 30)
                    .'</body></html>';

                return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
            });

            Route::get('/test-compression/pdf-mock', function () {
                $content = str_repeat('%PDF-1.4 Mock Binary PDF Content Object Stream ', 50);

                return response($content, 200, ['Content-Type' => 'application/pdf']);
            });

            Route::get('/test-compression/small', function () {
                return response('Tiny response', 200, ['Content-Type' => 'text/plain']);
            });
        });
    }

    protected function tearDown(): void
    {
        SystemSetting::clearRuntimeCache();
        CompressionNegotiator::clearRuntimeCache();
        parent::tearDown();
    }

    /**
     * Test JSON API negotiates Zstandard when client announces support.
     */
    public function test_zstd_negotiated_for_json_api_when_client_supports_it(): void
    {
        $response = $this->withHeaders([
            'Accept-Encoding' => 'zstd, br;q=0.9, gzip;q=0.8',
        ])->get('/test-compression/json');

        $response->assertStatus(200);

        if (extension_loaded('zstd') && function_exists('zstd_compress')) {
            $this->assertEquals('zstd', $response->headers->get('Content-Encoding'));
            $uncompressed = zstd_uncompress($response->getContent());
            $this->assertNotFalse($uncompressed);
            $this->assertStringContainsString('Adaptive Compression JSON Payload', (string) $uncompressed);
        } else {
            // Graceful fallback to next tier
            $this->assertContains($response->headers->get('Content-Encoding'), ['br', 'gzip', 'deflate']);
        }
    }

    /**
     * Test HTML document negotiates Brotli when client announces support.
     */
    public function test_brotli_negotiated_for_html_when_client_supports_it(): void
    {
        $response = $this->withHeaders([
            'Accept-Encoding' => 'br, gzip;q=0.8',
        ])->get('/test-compression/html');

        $response->assertStatus(200);

        if (extension_loaded('brotli') && function_exists('brotli_compress')) {
            $this->assertEquals('br', $response->headers->get('Content-Encoding'));
            if (function_exists('brotli_uncompress')) {
                $uncompressed = brotli_uncompress($response->getContent());
                $this->assertStringContainsString('NBPDCL Consumer Portal', (string) $uncompressed);
            }
        } else {
            $this->assertEquals('gzip', $response->headers->get('Content-Encoding'));
        }
    }

    /**
     * Test graceful cascading fallback to Gzip when client only supports gzip.
     */
    public function test_cascading_fallback_to_gzip_when_client_only_supports_gzip(): void
    {
        $response = $this->withHeaders([
            'Accept-Encoding' => 'gzip',
        ])->get('/test-compression/json');

        $response->assertStatus(200);
        $this->assertEquals('gzip', $response->headers->get('Content-Encoding'));

        $uncompressed = gzdecode($response->getContent());
        $this->assertNotFalse($uncompressed);
        $this->assertStringContainsString('Adaptive Compression JSON Payload', (string) $uncompressed);
    }

    /**
     * Test admin can exclude an algorithm (e.g. disable Zstd) and it falls to next tier.
     */
    public function test_cascading_fallback_when_preferred_algorithm_disabled_by_admin(): void
    {
        SystemSetting::set('compression_disabled_algorithms', ['zstd']);
        CompressionNegotiator::clearRuntimeCache();

        $response = $this->withHeaders([
            'Accept-Encoding' => 'zstd, br, gzip',
        ])->get('/test-compression/json');

        $response->assertStatus(200);

        if (extension_loaded('brotli') && function_exists('brotli_compress')) {
            $this->assertEquals('br', $response->headers->get('Content-Encoding'));
        } else {
            $this->assertEquals('gzip', $response->headers->get('Content-Encoding'));
        }
    }

    /**
     * Test non-compressible MIME types (like application/pdf) are never compressed.
     */
    public function test_non_compressible_content_skipped(): void
    {
        $response = $this->withHeaders([
            'Accept-Encoding' => 'zstd, br, gzip',
        ])->get('/test-compression/pdf-mock');

        $response->assertStatus(200);
        $this->assertFalse($response->headers->has('Content-Encoding'));
        $this->assertStringContainsString('%PDF-1.4 Mock Binary PDF', $response->getContent());
    }

    /**
     * Test small response under min_size threshold is not compressed.
     */
    public function test_small_response_under_threshold_skipped(): void
    {
        $response = $this->withHeaders([
            'Accept-Encoding' => 'zstd, br, gzip',
        ])->get('/test-compression/small');

        $response->assertStatus(200);
        $this->assertFalse($response->headers->has('Content-Encoding'));
        $this->assertEquals('Tiny response', $response->getContent());
    }

    /**
     * Test master switch disables all compression dynamically.
     */
    public function test_master_switch_disables_all_compression(): void
    {
        SystemSetting::set('compression_enabled', false);
        CompressionNegotiator::clearRuntimeCache();

        $response = $this->withHeaders([
            'Accept-Encoding' => 'zstd, br, gzip',
        ])->get('/test-compression/json');

        $response->assertStatus(200);
        $this->assertFalse($response->headers->has('Content-Encoding'));
    }

    /**
     * Test admin dashboard page loads successfully.
     */
    public function test_admin_can_view_compression_monitor(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.compression.index'));

        $response->assertStatus(200);
        $response->assertSee('Adaptive Hybrid Compression Monitor', false);
        $response->assertSee('Zstandard (Zstd)');
        $response->assertSee('Brotli (br)');
        $response->assertSee('Gzip / Deflate (zlib)');
        $response->assertSee('Cascading Fallback Hierarchy');
        $response->assertSee('Live Diagnostic Compression Benchmarker');
    }

    /**
     * Test non-admin user is forbidden from viewing compression monitor.
     */
    public function test_non_admin_cannot_access_compression_monitor(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->get(route('admin.compression.index'));
        $response->assertStatus(403);
    }

    /**
     * Test admin can update compression policy.
     */
    public function test_admin_can_update_compression_policy(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.compression.update'), [
            'compression_enabled' => 1,
            'disabled_algorithms' => ['deflate'],
            'priority' => ['zstd', 'br', 'gzip', 'deflate'],
        ]);

        $response->assertRedirect(route('admin.compression.index'));
        $response->assertSessionHas('status');

        $this->assertEquals(['deflate'], SystemSetting::get('compression_disabled_algorithms'));
    }

    /**
     * Test admin can reset compression policy back to defaults.
     */
    public function test_admin_can_reset_compression_policy(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        SystemSetting::set('compression_disabled_algorithms', ['zstd', 'br']);

        $response = $this->actingAs($admin)->post(route('admin.compression.reset'));

        $response->assertRedirect(route('admin.compression.index'));
        $response->assertSessionHas('status');

        $this->assertNull(SystemSetting::where('key', 'compression_disabled_algorithms')->first());
    }

    /**
     * Test diagnostic benchmark endpoint returns valid metrics.
     */
    public function test_diagnostic_endpoint_returns_valid_metrics(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->postJson(route('admin.compression.diagnostic'), [
            'type' => 'json',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'benchmark' => [
                'label',
                'original_size_bytes',
                'algorithms' => [
                    'zstd' => ['available'],
                    'br' => ['available'],
                    'gzip' => ['available'],
                    'deflate' => ['available'],
                ],
            ],
            'summary',
        ]);
    }
}
