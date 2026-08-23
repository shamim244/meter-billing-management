<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfManagementAndDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    public function test_mru_code_renaming_migrates_physical_pdf_folders_and_db_paths(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        $pdfPath = "users/{$user->id}/pdfs/2026/8/0244/102300783538.pdf";
        Storage::disk('local')->put($pdfPath, '%PDF-1.4 dummy content');

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 8,
            'billing_year' => 2026,
            'pdf_path' => $pdfPath,
            'pdf_filename' => '102300783538.pdf',
            'download_status' => 'downloaded',
        ]);

        // Update MRU code from 0244 to 0245
        $response = $this->actingAs($user)->put("/mrus/{$mru->id}", [
            'code' => '0245',
            'name' => 'NISARBHATI NEW',
            'status' => 'active',
        ]);

        $response->assertRedirect();
        
        // Assert old file moved to new path
        $newPdfPath = "users/{$user->id}/pdfs/2026/8/0245/102300783538.pdf";
        Storage::disk('local')->assertMissing($pdfPath);
        Storage::disk('local')->assertExists($newPdfPath);

        // Assert DB record updated
        $this->assertEquals($newPdfPath, $bill->fresh()->pdf_path);
        $this->assertEquals('0245', $mru->fresh()->code);
    }

    public function test_delete_monthly_session_purges_db_and_physical_disk_folder(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        $pdfPath = "users/{$user->id}/pdfs/2026/8/0244/102300783538.pdf";
        Storage::disk('local')->put($pdfPath, '%PDF-1.4 dummy content');

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 8,
            'billing_year' => 2026,
            'pdf_path' => $pdfPath,
            'pdf_filename' => '102300783538.pdf',
            'download_status' => 'downloaded',
        ]);

        $response = $this->actingAs($user)->delete("/mrus/{$mru->id}/sessions/8/2026");

        $response->assertRedirect();

        // Physical folder on disk must be purged
        Storage::disk('local')->assertMissing("users/{$user->id}/pdfs/2026/8/0244");
        
        // DB records for that month must be gone
        $this->assertDatabaseMissing('bill_records', ['id' => $bill->id]);
    }

    public function test_mru_destroy_purges_all_physical_folders_and_cascades(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'consumer_name' => 'ABDUL ANNAN',
        ]);

        $fileJuly = "users/{$user->id}/pdfs/2026/7/0244/102300783538.pdf";
        $fileAug = "users/{$user->id}/pdfs/2026/8/0244/102300783538.pdf";
        Storage::disk('local')->put($fileJuly, '%PDF-1.4 content July');
        Storage::disk('local')->put($fileAug, '%PDF-1.4 content Aug');

        $billJuly = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 7,
            'billing_year' => 2026,
            'pdf_path' => $fileJuly,
        ]);

        $billAug = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 8,
            'billing_year' => 2026,
            'pdf_path' => $fileAug,
        ]);

        $response = $this->actingAs($user)->delete("/mrus/{$mru->id}");

        $response->assertRedirect('/mrus');

        // All physical folders must be removed
        Storage::disk('local')->assertMissing("users/{$user->id}/pdfs/2026/7/0244");
        Storage::disk('local')->assertMissing("users/{$user->id}/pdfs/2026/8/0244");

        // All database records must be cleanly deleted
        $this->assertDatabaseMissing('mrus', ['id' => $mru->id]);
        $this->assertDatabaseMissing('consumer_accounts', ['id' => $consumer->id]);
        $this->assertDatabaseMissing('bill_records', ['id' => $billJuly->id]);
        $this->assertDatabaseMissing('bill_records', ['id' => $billAug->id]);
    }

    public function test_can_delete_and_reset_single_bill_pdf(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        $pdfPath = "users/{$user->id}/pdfs/2026/8/0244/102300783538.pdf";
        Storage::disk('local')->put($pdfPath, '%PDF-1.4 dummy content');

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 8,
            'billing_year' => 2026,
            'pdf_path' => $pdfPath,
            'pdf_filename' => '102300783538.pdf',
            'download_status' => 'downloaded',
            'current_reading' => '450',
            'total_amount' => 500,
        ]);

        $response = $this->actingAs($user)->postJson('/bills/delete-pdf', [
            'id' => $bill->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // File must be deleted from storage
        Storage::disk('local')->assertMissing($pdfPath);

        // BillRecord must be reset to pending
        $fresh = $bill->fresh();
        $this->assertNull($fresh->pdf_path);
        $this->assertNull($fresh->pdf_filename);
        $this->assertEquals('pending', $fresh->download_status);
        $this->assertEquals(0, $fresh->total_amount);
    }
}
