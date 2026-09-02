<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\SystemBackup;
use App\Models\User;
use App\Services\Backup\AgentWorkspaceExportService;
use App\Services\Backup\BackupRetentionService;
use App\Services\Backup\BackupService;
use App\Services\Backup\DatabaseDumpService;
use App\Services\Backup\StorageBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BackupSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $agentUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'agent']);

        $this->adminUser = User::factory()->create([
            'email' => 'admin_backup_test@example.com',
            'status' => 'active',
        ]);
        $this->adminUser->assignRole('admin');

        $this->agentUser = User::factory()->create([
            'email' => 'agent_backup_test@example.com',
            'status' => 'active',
        ]);
        $this->agentUser->assignRole('agent');
    }

    public function test_database_dump_service_generates_valid_gzipped_sql(): void
    {
        $dumper = app(DatabaseDumpService::class);
        $outputPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_dump_' . uniqid() . '.sql.gz';

        $summary = $dumper->dump($outputPath);

        $this->assertFileExists($outputPath);
        $this->assertGreaterThan(0, filesize($outputPath));
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('users', $summary);

        // Verify it can be decompressed
        $gz = gzopen($outputPath, 'rb');
        $content = gzread($gz, 4096);
        gzclose($gz);

        $this->assertStringContainsString('NBPDCL SaaS Pro Database Backup', $content);
        $this->assertStringContainsString('CREATE TABLE', $content);

        @unlink($outputPath);
    }

    public function test_storage_backup_service_creates_zip_with_exclusions(): void
    {
        $storageDumper = app(StorageBackupService::class);
        $outputPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_storage_' . uniqid() . '.zip';

        // Create a dummy file in storage/app/bills
        Storage::disk('local')->put('bills/test_bill.txt', 'Mock Bill PDF Content');

        $result = $storageDumper->archive($outputPath);

        $this->assertFileExists($outputPath);
        $this->assertGreaterThan(0, filesize($outputPath));
        $this->assertGreaterThanOrEqual(1, $result['file_count']);

        @unlink($outputPath);
    }

    public function test_backup_service_creates_db_only_backup_and_record(): void
    {
        $backupService = app(BackupService::class);

        $backup = $backupService->createBackup('db_only', $this->adminUser->id);

        $this->assertInstanceOf(SystemBackup::class, $backup);
        $this->assertEquals('completed', $backup->status);
        $this->assertEquals('db_only', $backup->type);
        $this->assertGreaterThan(0, $backup->size_bytes);
        $this->assertNotEmpty($backup->sha256_hash);
        $this->assertEquals(64, strlen($backup->sha256_hash));
        $this->assertTrue($backup->existsOnDisk());

        // Cleanup
        $backupService->deleteBackup($backup);
    }

    public function test_backup_service_creates_full_backup_bundle(): void
    {
        $backupService = app(BackupService::class);

        $backup = $backupService->createBackup('full', $this->adminUser->id);

        $this->assertEquals('completed', $backup->status);
        $this->assertEquals('full', $backup->type);
        $this->assertStringEndsWith('.zip', $backup->filename);
        $this->assertTrue($backup->existsOnDisk());

        // Cleanup
        $backupService->deleteBackup($backup);
    }

    public function test_agent_workspace_export_service_creates_tenant_isolated_zip(): void
    {
        // Setup agent data
        $mru = Mru::create([
            'user_id' => $this->agentUser->id,
            'code' => 'MRU_TEST_99',
            'name' => 'Rural Feeder 99',
            'current_cycle' => '2026-08',
            'is_locked' => false,
        ]);

        $consumer = ConsumerAccount::create([
            'user_id' => $this->agentUser->id,
            'mru_id' => $mru->id,
            'mru_code' => 'MRU_TEST_99',
            'ca_number' => '1000998877',
            'consumer_name' => 'Ramesh Kumar',
            'tariff_category' => 'DS-II',
        ]);

        $bill = BillRecord::create([
            'user_id' => $this->agentUser->id,
            'mru_id' => $mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
            'bill_month_label' => 'AUG, 2026',
            'ca_number' => '1000998877',
            'consumer_name' => 'Ramesh Kumar',
            'previous_reading' => 500,
            'working_reading' => 550,
            'current_reading' => 550,
            'units_consumed' => 50,
            'total_amount' => 350.00,
            'billing_basis' => 'OK',
            'review_status' => 'submitted',
        ]);

        $exportService = app(AgentWorkspaceExportService::class);
        $outputZip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_agent_export_' . uniqid() . '.zip';

        $manifest = $exportService->export($this->agentUser, $outputZip);

        $this->assertFileExists($outputZip);
        $this->assertEquals('AGENT_WORKSPACE_DATA_PORTABILITY', $manifest['export_type']);
        $this->assertEquals(1, $manifest['statistics']['total_mrus']);
        $this->assertEquals(1, $manifest['statistics']['total_consumers']);
        $this->assertEquals(1, $manifest['statistics']['total_bill_records']);

        // Inspect zip content
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($outputZip));
        $this->assertNotEmpty($zip->getFromName('ledger/01_mrus_master.csv'));
        $this->assertNotEmpty($zip->getFromName('ledger/02_consumers_registry.csv'));
        $this->assertNotEmpty($zip->getFromName('ledger/03_monthly_reading_ledger.csv'));
        $this->assertNotEmpty($zip->getFromName('manifest.json'));
        $zip->close();

        @unlink($outputZip);
    }

    public function test_artisan_backup_commands(): void
    {
        $this->artisan('saas:backup', ['--type' => 'db_only'])
            ->assertSuccessful();

        $this->artisan('saas:backup-list')
            ->assertSuccessful();

        $this->artisan('saas:backup-clean', ['--dry-run' => true])
            ->assertSuccessful();
    }

    public function test_admin_can_view_backups_cockpit_and_trigger_backup(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.backups.index'));
        $response->assertStatus(200);
        $response->assertSee('Disaster Recovery & Backups Cockpit');

        // Trigger backup via web
        $postResponse = $this->actingAs($this->adminUser)->post(route('admin.backups.store'), [
            'type' => 'db_only',
        ]);
        $postResponse->assertRedirect();
        $postResponse->assertSessionHas('success');

        $latestBackup = SystemBackup::latest()->first();
        $this->assertNotNull($latestBackup);

        // Fetch manifest JSON
        $manifestResponse = $this->actingAs($this->adminUser)->get(route('admin.backups.manifest', $latestBackup));
        $manifestResponse->assertStatus(200);
        $manifestResponse->assertJsonStructure(['backup_code', 'type', 'filename', 'size', 'sha256_hash']);

        // Download archive
        $downloadResponse = $this->actingAs($this->adminUser)->get(route('admin.backups.download', $latestBackup));
        $downloadResponse->assertStatus(200);

        // Delete backup
        $deleteResponse = $this->actingAs($this->adminUser)->delete(route('admin.backups.destroy', $latestBackup));
        $deleteResponse->assertRedirect();
        $deleteResponse->assertSessionHas('success');
    }

    public function test_agent_can_access_workspace_export_page_and_download_zip(): void
    {
        $response = $this->actingAs($this->agentUser)->get(route('user-panel.backup'));
        $response->assertStatus(200);
        $response->assertSee('Data Portability');
        $response->assertSee('Download Complete Workspace Package');

        $downloadResponse = $this->actingAs($this->agentUser)->post(route('user-panel.backup.download'));
        $downloadResponse->assertStatus(200);
        $this->assertTrue(str_contains($downloadResponse->headers->get('content-type'), 'zip') || str_contains($downloadResponse->headers->get('content-type'), 'octet-stream'));
    }

    public function test_non_admin_cannot_access_admin_backups(): void
    {
        $response = $this->actingAs($this->agentUser)->get(route('admin.backups.index'));
        $response->assertStatus(403);
    }
}
