<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBillingEngineSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        SystemSetting::clearRuntimeCache();
    }

    public function test_non_admin_cannot_access_engine_settings(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->get(route('admin.bills.engine-settings'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_engine_settings(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.bills.engine-settings'));
        $response->assertStatus(200);
        $response->assertSee('NBPDCL Billing & Extraction Engine', false);
        $response->assertSee('Smart Auto-Fallback');
        $response->assertSee('Signature Auto-Detect');
    }

    public function test_admin_can_update_engine_settings(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $payload = [
            'download_driver' => 'legacy',
            'extraction_engine' => 'jasper_unicode',
            'wss_url' => 'https://wss.nbpdcl.co.in/custom-endpoint',
            'aes_key' => 'custom-secret-key@2026',
            'legacy_url' => 'https://api.bsphcl.co.in/custom-asmx',
            'timeout' => 60,
            'concurrency' => 12,
        ];

        $response = $this->actingAs($admin)->post(route('admin.bills.engine-settings.update'), $payload);
        $response->assertRedirect(route('admin.bills.engine-settings'));
        $response->assertSessionHas('status');

        $this->assertEquals('legacy', SystemSetting::get('nbpdcl_download_driver'));
        $this->assertEquals('jasper_unicode', SystemSetting::get('nbpdcl_extraction_engine'));
        $this->assertEquals('https://wss.nbpdcl.co.in/custom-endpoint', SystemSetting::get('nbpdcl_wss_url'));
        $this->assertEquals('custom-secret-key@2026', SystemSetting::get('nbpdcl_aes_key'));
        $this->assertEquals(60, (int) SystemSetting::get('nbpdcl_timeout'));
        $this->assertEquals(12, (int) SystemSetting::get('nbpdcl_concurrency'));
    }

    public function test_admin_can_reset_engine_settings_to_factory_defaults(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        // First modify settings
        SystemSetting::set('nbpdcl_download_driver', 'legacy');
        SystemSetting::set('nbpdcl_extraction_engine', 'legacy_krutidev');

        $response = $this->actingAs($admin)->post(route('admin.bills.engine-settings.reset'));
        $response->assertRedirect(route('admin.bills.engine-settings'));
        $response->assertSessionHas('status');

        $this->assertEquals('auto', SystemSetting::get('nbpdcl_download_driver'));
        $this->assertEquals('auto', SystemSetting::get('nbpdcl_extraction_engine'));
    }

    public function test_diagnostic_endpoint_rejects_non_admin(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->postJson(route('admin.bills.engine-settings.diagnostic'), [
            'ca_number' => '10230041576',
            'driver' => 'auto',
        ]);

        $response->assertStatus(403);
    }
}
