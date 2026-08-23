<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\Mru;
use App\Models\User;
use App\Services\BillDownloadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageQuotaAndCyclePurgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    public function test_purge_cycle_pdfs_removes_physical_files_while_preserving_database_ledger(): void
    {
        $user = User::factory()->create(['status' => 'active', 'storage_limit_mb' => 100]);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0477', 'name' => 'Gerua', 'status' => 'active']);

        $pdfPath = "users/{$user->id}/pdfs/2026/6/0477/102300783538.pdf";
        Storage::disk('local')->put($pdfPath, '%PDF-1.4 sample bill content for June');

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'consumer_name' => 'RAMESH PRASAD',
            'billing_month' => 6,
            'billing_year' => 2026,
            'current_reading' => 1500,
            'previous_reading' => 1350,
            'working_reading' => 1500,
            'units_consumed' => 150,
            'total_amount' => 1250.00,
            'meter_no' => 'MTR998877',
            'review_status' => 'submit_ok',
            'remark' => 'Verified with supervisor',
            'pdf_path' => $pdfPath,
            'pdf_filename' => '102300783538.pdf',
            'download_status' => 'downloaded',
            'parse_status' => 'parsed',
        ]);

        Storage::disk('local')->assertExists($pdfPath);

        $response = $this->actingAs($user)->postJson(route('pdf-manager.purge-cycle'), [
            'month' => 6,
            'year' => 2026,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'deleted_files' => 1,
            'preserved_records' => 1,
        ]);

        // 1. Physical PDF MUST be deleted to reclaim disk space
        Storage::disk('local')->assertMissing($pdfPath);

        // 2. Database record MUST be preserved with all audit data
        $fresh = $bill->fresh();
        $this->assertNull($fresh->pdf_path);
        $this->assertNull($fresh->pdf_filename);
        $this->assertEquals('pending', $fresh->download_status);

        // Crucial: Historical ledger data retained intact
        $this->assertEquals('RAMESH PRASAD', $fresh->consumer_name);
        $this->assertEquals(1500, $fresh->current_reading);
        $this->assertEquals(1350, $fresh->previous_reading);
        $this->assertEquals(150, $fresh->units_consumed);
        $this->assertEquals(1250.00, (float)$fresh->total_amount);
        $this->assertEquals('MTR998877', $fresh->meter_no);
        $this->assertEquals('submit_ok', $fresh->review_status);
        $this->assertEquals('Verified with supervisor', $fresh->remark);
    }

    public function test_purge_older_than_current_cleans_historical_pdfs_and_keeps_current_cycle(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0477', 'name' => 'Gerua', 'status' => 'active']);

        $currentMonth = (int) now()->month;
        $currentYear = (int) now()->year;
        $oldMonth = $currentMonth > 1 ? $currentMonth - 1 : 12;
        $oldYear = $currentMonth > 1 ? $currentYear : $currentYear - 1;

        // Old Cycle Bill & PDF
        $oldPdfPath = "users/{$user->id}/pdfs/{$oldYear}/{$oldMonth}/0477/old_bill.pdf";
        Storage::disk('local')->put($oldPdfPath, '%PDF-1.4 old content');
        $oldBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1001',
            'billing_month' => $oldMonth,
            'billing_year' => $oldYear,
            'pdf_path' => $oldPdfPath,
            'download_status' => 'downloaded',
        ]);

        // Current Cycle Bill & PDF
        $curPdfPath = "users/{$user->id}/pdfs/{$currentYear}/{$currentMonth}/0477/cur_bill.pdf";
        Storage::disk('local')->put($curPdfPath, '%PDF-1.4 current active content');
        $curBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '2001',
            'billing_month' => $currentMonth,
            'billing_year' => $currentYear,
            'pdf_path' => $curPdfPath,
            'download_status' => 'downloaded',
        ]);

        $response = $this->actingAs($user)->postJson(route('pdf-manager.purge-cycle'), [
            'older_than_current' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'deleted_files' => 1,
        ]);

        // Old PDF purged
        Storage::disk('local')->assertMissing($oldPdfPath);
        $this->assertNull($oldBill->fresh()->pdf_path);

        // Current cycle PDF preserved
        Storage::disk('local')->assertExists($curPdfPath);
        $this->assertEquals($curPdfPath, $curBill->fresh()->pdf_path);
    }

    public function test_user_storage_quota_calculation_and_percentage(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'storage_limit_mb' => 10, // 10 MB
            'plan_tier' => 'starter',
        ]);

        // Put 2MB file
        $pdfPath = "users/{$user->id}/pdfs/2026/8/0477/test.pdf";
        $twoMbContent = str_repeat('A', 2 * 1024 * 1024);
        Storage::disk('local')->put($pdfPath, $twoMbContent);

        $this->assertEquals(2 * 1024 * 1024, $user->getStorageUsedBytes());
        $this->assertEquals(10 * 1024 * 1024, $user->getStorageLimitBytes());
        $this->assertEquals(20.0, $user->getStorageUsagePercent());
        $this->assertFalse($user->isStorageLimitExceeded());
        $this->assertEquals(1, $user->getPdfCount());
    }

    public function test_storage_quota_guard_blocks_downloads_when_limit_exceeded(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'storage_limit_mb' => 1, // 1 MB limit
            'plan_tier' => 'free',
        ]);

        // Fill storage beyond 1MB
        $pdfPath = "users/{$user->id}/pdfs/2026/8/0477/big.pdf";
        $twoMbContent = str_repeat('A', 2 * 1024 * 1024);
        Storage::disk('local')->put($pdfPath, $twoMbContent);

        $this->assertTrue($user->isStorageLimitExceeded());

        // Attempt batch download
        $downloadService = app(BillDownloadService::class);
        $result = $downloadService->download(['102300783538'], $user->id, 8, 2026);

        $this->assertEquals(1, $result['failed']);
        $this->assertStringContainsString('Storage Quota Exceeded', $result['error']);
    }

    public function test_admin_can_update_user_storage_quota_and_plan(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $user = User::factory()->create([
            'status' => 'active',
            'storage_limit_mb' => 100,
            'plan_tier' => 'free',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.users.update-quota', $user), [
            'storage_limit_mb' => 500,
            'plan_tier' => 'pro',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $user->fresh();
        $this->assertEquals(500, $fresh->storage_limit_mb);
        $this->assertEquals('pro', $fresh->plan_tier);
    }
}
