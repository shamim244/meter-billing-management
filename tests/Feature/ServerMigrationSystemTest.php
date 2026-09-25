<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Migration\ServerMigrationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ServerMigrationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        SystemSetting::clearRuntimeCache();
    }

    public function test_preflight_check_returns_valid_structure_and_detects_environment(): void
    {
        /** @var ServerMigrationService $service */
        $service = app(ServerMigrationService::class);
        $preflight = $service->runPreflightCheck();

        $this->assertIsArray($preflight);
        $this->assertArrayHasKey('ready', $preflight);
        $this->assertArrayHasKey('php_version', $preflight);
        $this->assertArrayHasKey('php_satisfies', $preflight);
        $this->assertArrayHasKey('extensions', $preflight);
        $this->assertArrayHasKey('writable_paths', $preflight);
        $this->assertArrayHasKey('database', $preflight);
        $this->assertArrayHasKey('redis', $preflight);
        $this->assertArrayHasKey('memory_limit', $preflight);

        $this->assertTrue($preflight['php_satisfies']);
        $this->assertTrue($preflight['database']['connected']);
    }

    public function test_server_preflight_artisan_command_executes_successfully(): void
    {
        $exitCode = Artisan::call('app:preflight-check');
        $this->assertSame(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('Running Full Server Environment Audit', $output);
        $this->assertStringContainsString('PHP Version', $output);
    }

    public function test_app_discovery_api_endpoint_returns_expected_configuration(): void
    {
        $response = $this->getJson('/api/v1/app/config');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'app_name',
            'api_version',
            'active_server_url',
            'api_base_url',
            'fallback_server_urls',
            'min_app_version',
            'latest_app_version',
            'maintenance_mode',
            'qr_connect_code',
            'compression_supported',
            'timestamp',
        ]);

        $json = $response->json();
        $this->assertTrue($json['success']);
        $this->assertIsArray($json['fallback_server_urls']);
        $this->assertNotEmpty($json['api_base_url']);
        $this->assertStringContainsString('/api/v1', $json['qr_connect_code']);
    }

    public function test_migration_service_creates_and_inspects_package_with_manifest_and_sha256(): void
    {
        /** @var ServerMigrationService $service */
        $service = app(ServerMigrationService::class);

        $tempOutputDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'migration_test_out_'.uniqid();
        File::ensureDirectoryExists($tempOutputDir);

        try {
            $result = $service->createMigrationPackage([
                'output_dir' => $tempOutputDir,
                'skip_storage' => true,
            ]);

            $this->assertFileExists($result['bundle_path']);
            $this->assertSame('universal_cross_cloud', $result['manifest']['export_type']);
            $this->assertNotEmpty($result['manifest']['checksums']['database_sql_gz']);
            $this->assertSame(64, strlen($result['manifest']['checksums']['database_sql_gz']));
            $this->assertIsArray($result['manifest']['table_counts']);

            // Inspect the created package
            $inspection = $service->inspectPackage($result['bundle_path']);
            $this->assertTrue($inspection['valid']);
            $this->assertIsArray($inspection['manifest']);
            $this->assertSame($result['manifest']['checksums']['database_sql_gz'], $inspection['manifest']['checksums']['database_sql_gz']);
        } finally {
            File::deleteDirectory($tempOutputDir);
        }
    }

    public function test_integrity_audit_verifies_matching_and_detects_discrepancies(): void
    {
        /** @var ServerMigrationService $service */
        $service = app(ServerMigrationService::class);

        // Matching audit
        $expectedCounts = [
            'users' => User::count(),
        ];
        $audit = $service->runIntegrityAudit($expectedCounts);
        $this->assertTrue($audit['verified']);
        $this->assertEmpty($audit['discrepancies']);

        // Discrepancy audit
        $discrepantCounts = [
            'users' => User::count() + 999,
        ];
        $auditFail = $service->runIntegrityAudit($discrepantCounts);
        $this->assertFalse($auditFail['verified']);
        $this->assertArrayHasKey('users', $auditFail['discrepancies']);
    }

    public function test_non_admin_cannot_access_migration_dashboard(): void
    {
        // Guest redirects to login
        $guestResponse = $this->get(route('admin.server_migration.index'));
        $guestResponse->assertRedirect(route('login'));

        // Normal user gets 403
        $user = User::factory()->create(['status' => 'active']);
        $userResponse = $this->actingAs($user)->get(route('admin.server_migration.index'));
        $userResponse->assertStatus(403);
    }

    public function test_admin_can_view_migration_dashboard(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.server_migration.index'));
        $response->assertStatus(200);
        $response->assertSee('Zero-Vendor-Lock-in Migration Engine', false);
        $response->assertSee('Type 1: Shared Hosting', false);
        $response->assertSee('Type 2: Docker', false);
        $response->assertSee('Type 3: Native VPS', false);
    }

    public function test_admin_can_export_migration_package(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.server_migration.export'), [
            'skip_storage' => '1',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('attachment;', $response->headers->get('content-disposition', ''));
        $this->assertStringContainsString('.zip', $response->headers->get('content-disposition', ''));
    }

    public function test_admin_can_download_and_destroy_migration_package(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $migrationsDir = storage_path('app/migrations');
        File::ensureDirectoryExists($migrationsDir);
        $testFile = $migrationsDir.DIRECTORY_SEPARATOR.'test_dummy_bundle.zip';
        file_put_contents($testFile, 'dummy content');

        // Test download
        $downloadResponse = $this->actingAs($admin)->get(route('admin.server_migration.download', ['filename' => 'test_dummy_bundle.zip']));
        $downloadResponse->assertStatus(200);

        // Test destroy
        $destroyResponse = $this->actingAs($admin)->delete(route('admin.server_migration.destroy', ['filename' => 'test_dummy_bundle.zip']));
        $destroyResponse->assertRedirect();
        $destroyResponse->assertSessionHas('success');

        $this->assertFileDoesNotExist($testFile);
    }
}
