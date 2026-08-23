<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    public function test_pdf_manager_index_renders_with_storage_analytics_and_filters(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0477', 'name' => 'Gerua', 'status' => 'active']);

        $pdfPath = "users/{$user->id}/pdfs/2026/8/0477/102300783538.pdf";
        Storage::disk('local')->put($pdfPath, '%PDF-1.4 sample bill content of some length');

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'consumer_name' => 'MD TARIQ',
            'billing_month' => 8,
            'billing_year' => 2026,
            'pdf_path' => $pdfPath,
            'pdf_filename' => '102300783538.pdf',
            'download_status' => 'downloaded',
            'parse_status' => 'parsed',
            'total_amount' => 1250.00,
            'units_consumed' => 150,
        ]);

        $response = $this->actingAs($user)->get(route('pdf-manager.index'));

        $response->assertStatus(200);
        $response->assertSee('PDF Document Management Center');
        $response->assertSee('102300783538');
        $response->assertSee('MD TARIQ');
        $response->assertSee('0477');
        $response->assertSee('Storage Health');
    }

    public function test_batch_zip_download_exports_selected_pdfs_with_mru_structure(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0477', 'name' => 'Gerua', 'status' => 'active']);

        $pdfPath = "users/{$user->id}/pdfs/2026/8/0477/102300783538.pdf";
        Storage::disk('local')->put($pdfPath, '%PDF-1.4 dummy bill content');

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 8,
            'billing_year' => 2026,
            'pdf_path' => $pdfPath,
            'download_status' => 'downloaded',
        ]);

        $response = $this->actingAs($user)->post(route('pdf-manager.batch-download'), [
            'bill_ids' => [$bill->id],
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/zip');
    }

    public function test_storage_health_check_detects_missing_corrupt_and_orphaned_files(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0477', 'name' => 'Gerua', 'status' => 'active']);

        // 1. Missing file in storage
        $missingBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1001',
            'billing_month' => 8,
            'billing_year' => 2026,
            'pdf_path' => "users/{$user->id}/pdfs/2026/8/0477/1001.pdf",
            'download_status' => 'downloaded',
        ]);

        // 2. Corrupt file (<500 bytes)
        $corruptPath = "users/{$user->id}/pdfs/2026/8/0477/1002.pdf";
        Storage::disk('local')->put($corruptPath, 'tiny');
        $corruptBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1002',
            'billing_month' => 8,
            'billing_year' => 2026,
            'pdf_path' => $corruptPath,
            'download_status' => 'downloaded',
        ]);

        // 3. Orphaned file on disk without DB record
        $orphanPath = "users/{$user->id}/pdfs/2026/8/0477/9999.pdf";
        Storage::disk('local')->put($orphanPath, '%PDF-1.4 orphan content');

        $response = $this->actingAs($user)->getJson(route('pdf-manager.health-check'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_healthy' => false,
            'missing_count' => 1,
            'corrupted_count' => 1,
            'orphaned_count' => 1,
        ]);
    }

    public function test_storage_sync_auto_heals_missing_and_registers_orphans(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0477', 'name' => 'Gerua', 'status' => 'active']);

        // Missing record
        $missingBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1001',
            'billing_month' => 8,
            'billing_year' => 2026,
            'pdf_path' => "users/{$user->id}/pdfs/2026/8/0477/1001.pdf",
            'download_status' => 'downloaded',
        ]);

        // Orphan file on disk
        $orphanPath = "users/{$user->id}/pdfs/2026/8/0477/102300783538.pdf";
        Storage::disk('local')->put($orphanPath, '%PDF-1.4 orphan content');

        $response = $this->actingAs($user)->postJson(route('pdf-manager.sync-storage'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'healed_missing' => 1,
            'registered_orphans' => 1,
        ]);

        // Missing record was reset
        $this->assertEquals('pending', $missingBill->fresh()->download_status);
        $this->assertNull($missingBill->fresh()->pdf_path);

        // Orphan record was created in DB
        $this->assertDatabaseHas('bill_records', [
            'user_id' => $user->id,
            'ca_number' => '102300783538',
            'billing_month' => 8,
            'billing_year' => 2026,
            'download_status' => 'downloaded',
        ]);
    }

    public function test_batch_delete_purges_physical_files_and_resets_db_records(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0477', 'name' => 'Gerua', 'status' => 'active']);

        $pdfPath = "users/{$user->id}/pdfs/2026/8/0477/1001.pdf";
        Storage::disk('local')->put($pdfPath, '%PDF-1.4 dummy content');

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1001',
            'billing_month' => 8,
            'billing_year' => 2026,
            'pdf_path' => $pdfPath,
            'download_status' => 'downloaded',
            'parse_status' => 'parsed',
            'total_amount' => 500,
        ]);

        $response = $this->actingAs($user)->postJson(route('pdf-manager.batch-delete'), [
            'bill_ids' => [$bill->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'deleted_files' => 1,
            'reset_records' => 1,
        ]);

        Storage::disk('local')->assertMissing($pdfPath);
        $fresh = $bill->fresh();
        $this->assertEquals('pending', $fresh->download_status);
        $this->assertNull($fresh->pdf_path);
        $this->assertEquals(0, $fresh->total_amount);
    }

    public function test_user_isolation_prevents_user_from_accessing_or_deleting_other_user_pdfs(): void
    {
        $userA = User::factory()->create(['status' => 'active']);
        $userB = User::factory()->create(['status' => 'active']);

        $mruB = Mru::create(['user_id' => $userB->id, 'code' => '0477', 'name' => 'Gerua', 'status' => 'active']);
        $pdfPathB = "users/{$userB->id}/pdfs/2026/8/0477/2001.pdf";
        Storage::disk('local')->put($pdfPathB, '%PDF-1.4 user B secret');

        $billB = BillRecord::create([
            'user_id' => $userB->id,
            'mru_id' => $mruB->id,
            'ca_number' => '2001',
            'billing_month' => 8,
            'billing_year' => 2026,
            'pdf_path' => $pdfPathB,
            'download_status' => 'downloaded',
        ]);

        // User A tries to view User B's PDF
        $viewResponse = $this->actingAs($userA)->get(route('bills.pdf', $billB));
        $viewResponse->assertNotFound();

        // User A tries to batch delete User B's bill
        $deleteResponse = $this->actingAs($userA)->postJson(route('pdf-manager.batch-delete'), [
            'bill_ids' => [$billB->id],
        ]);

        // User B's file should remain untouched
        Storage::disk('local')->assertExists($pdfPathB);
        $this->assertEquals('downloaded', $billB->fresh()->download_status);
    }

    public function test_manual_pdf_upload_saves_file_and_creates_bill_record(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0477', 'name' => 'Gerua', 'status' => 'active']);

        $uploadedFile = UploadedFile::fake()->createWithContent('102300783538.pdf', '%PDF-1.4 fake uploaded bill');

        $response = $this->actingAs($user)->postJson(route('pdf-manager.upload'), [
            'files' => [$uploadedFile],
            'mru_id' => $mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'uploaded_count' => 1,
        ]);

        $expectedPath = "users/{$user->id}/pdfs/2026/8/0477/102300783538.pdf";
        Storage::disk('local')->assertExists($expectedPath);

        $this->assertDatabaseHas('bill_records', [
            'user_id' => $user->id,
            'ca_number' => '102300783538',
            'billing_month' => 8,
            'billing_year' => 2026,
            'pdf_path' => $expectedPath,
            'download_status' => 'downloaded',
        ]);
    }
}
