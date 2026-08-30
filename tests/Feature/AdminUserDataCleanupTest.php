<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserDataCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;
    protected Mru $mru1;
    protected Mru $mru2;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('private');

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['email' => 'admin_cleaner@nbpdcl-saas.com', 'status' => 'active']);
        $this->admin->assignRole($adminRole);

        $this->agent = User::factory()->create(['name' => 'Agent Tariq', 'email' => 'tariq_clean@example.com', 'status' => 'active']);
        $this->agent->assignRole($userRole);

        $this->mru1 = Mru::create([
            'user_id' => $this->agent->id,
            'code' => '0477',
            'name' => 'Gerua Section',
            'status' => 'active',
        ]);

        $this->mru2 = Mru::create([
            'user_id' => $this->agent->id,
            'code' => '0478',
            'name' => 'Patahi Section',
            'status' => 'active',
        ]);
    }

    /**
     * 1. Clean All PDFs deletes physical disk files and clears pdf_path,
     *    while safely preserving all database bill records, units, and amounts.
     */
    public function test_clean_all_pdfs_deletes_disk_files_and_clears_pdf_path_while_preserving_database_records(): void
    {
        $path1 = "bills/{$this->agent->id}/0477/102300000001.pdf";
        $path2 = "bills/{$this->agent->id}/0478/102300000002.pdf";
        Storage::disk('local')->put($path1, 'FAKE_PDF_CONTENT_1');
        Storage::disk('local')->put($path2, 'FAKE_PDF_CONTENT_2');

        $bill1 = BillRecord::create([
            'user_id' => $this->agent->id,
            'mru_id' => $this->mru1->id,
            'ca_number' => '102300000001',
            'consumer_name' => 'Ramesh Kumar',
            'billing_month' => 8,
            'billing_year' => 2026,
            'total_amount' => 1250.00,
            'pdf_path' => $path1,
            'download_status' => 'downloaded',
            'review_status' => 'submitted',
        ]);

        $bill2 = BillRecord::create([
            'user_id' => $this->agent->id,
            'mru_id' => $this->mru2->id,
            'ca_number' => '102300000002',
            'consumer_name' => 'Suresh Singh',
            'billing_month' => 8,
            'billing_year' => 2026,
            'total_amount' => 850.00,
            'pdf_path' => $path2,
            'download_status' => 'downloaded',
            'review_status' => 'doubt',
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('admin.users.clean_pdfs', $this->agent), [
            'scope' => 'all',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Physical files must be deleted from storage
        Storage::disk('local')->assertMissing($path1);
        Storage::disk('local')->assertMissing($path2);

        // Database records MUST be preserved!
        $this->assertDatabaseHas('bill_records', [
            'id' => $bill1->id,
            'ca_number' => '102300000001',
            'consumer_name' => 'Ramesh Kumar',
            'total_amount' => 1250.00,
            'pdf_path' => null,
            'download_status' => 'pending',
            'review_status' => 'submitted',
        ]);

        $this->assertDatabaseHas('bill_records', [
            'id' => $bill2->id,
            'ca_number' => '102300000002',
            'consumer_name' => 'Suresh Singh',
            'total_amount' => 850.00,
            'pdf_path' => null,
            'download_status' => 'pending',
            'review_status' => 'doubt',
        ]);

        // User must still exist
        $this->assertDatabaseHas('users', ['id' => $this->agent->id]);
    }

    /**
     * 2. Clean PDFs older than 30 days prunes only stale files.
     */
    public function test_clean_pdfs_older_than_30_days_deletes_only_stale_files(): void
    {
        $oldPath = "bills/{$this->agent->id}/0477/old_bill.pdf";
        $freshPath = "bills/{$this->agent->id}/0477/fresh_bill.pdf";
        Storage::disk('local')->put($oldPath, 'OLD_PDF');
        Storage::disk('local')->put($freshPath, 'FRESH_PDF');

        $oldBill = BillRecord::create([
            'user_id' => $this->agent->id,
            'mru_id' => $this->mru1->id,
            'ca_number' => '102300000010',
            'billing_month' => 6,
            'billing_year' => 2026,
            'pdf_path' => $oldPath,
            'download_status' => 'downloaded',
        ]);
        $oldBill->timestamps = false;
        $oldBill->created_at = now()->subDays(45);
        $oldBill->updated_at = now()->subDays(45);
        $oldBill->save();

        $freshBill = BillRecord::create([
            'user_id' => $this->agent->id,
            'mru_id' => $this->mru1->id,
            'ca_number' => '102300000011',
            'billing_month' => 8,
            'billing_year' => 2026,
            'pdf_path' => $freshPath,
            'download_status' => 'downloaded',
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('admin.users.clean_pdfs', $this->agent), [
            'scope' => 'older_than_30',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Old file deleted, fresh file kept
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($freshPath);

        $this->assertNull($oldBill->fresh()->pdf_path);
        $this->assertEquals($freshPath, $freshBill->fresh()->pdf_path);
    }

    /**
     * 3. Clean PDFs by specific MRU removes PDFs only for the selected MRU.
     */
    public function test_clean_pdfs_by_specific_mru(): void
    {
        $mru1Path = "bills/{$this->agent->id}/0477/mru1.pdf";
        $mru2Path = "bills/{$this->agent->id}/0478/mru2.pdf";
        Storage::disk('local')->put($mru1Path, 'MRU1_PDF');
        Storage::disk('local')->put($mru2Path, 'MRU2_PDF');

        $bill1 = BillRecord::create([
            'user_id' => $this->agent->id,
            'mru_id' => $this->mru1->id,
            'ca_number' => '102300000021',
            'billing_month' => 8,
            'billing_year' => 2026,
            'pdf_path' => $mru1Path,
            'download_status' => 'downloaded',
        ]);

        $bill2 = BillRecord::create([
            'user_id' => $this->agent->id,
            'mru_id' => $this->mru2->id,
            'ca_number' => '102300000022',
            'billing_month' => 8,
            'billing_year' => 2026,
            'pdf_path' => $mru2Path,
            'download_status' => 'downloaded',
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('admin.users.clean_pdfs', $this->agent), [
            'scope' => 'mru',
            'mru_id' => $this->mru1->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Storage::disk('local')->assertMissing($mru1Path);
        Storage::disk('local')->assertExists($mru2Path);

        $this->assertNull($bill1->fresh()->pdf_path);
        $this->assertEquals($mru2Path, $bill2->fresh()->pdf_path);
    }

    /**
     * 4. Clean selected MRUs deletes MRU record, consumers, and files,
     *    while preserving the user account and other MRUs.
     */
    public function test_clean_mrus_deletes_selected_mrus_and_files_while_keeping_user_and_other_mrus(): void
    {
        $mru1Path = "bills/{$this->agent->id}/0477/bill.pdf";
        Storage::disk('local')->put($mru1Path, 'PDF_FOR_MRU1');

        ConsumerAccount::create([
            'user_id' => $this->agent->id,
            'mru_id' => $this->mru1->id,
            'ca_number' => '102300000031',
            'consumer_name' => 'Consumer In MRU 1',
        ]);

        ConsumerAccount::create([
            'user_id' => $this->agent->id,
            'mru_id' => $this->mru2->id,
            'ca_number' => '102300000032',
            'consumer_name' => 'Consumer In MRU 2',
        ]);

        BillRecord::create([
            'user_id' => $this->agent->id,
            'mru_id' => $this->mru1->id,
            'ca_number' => '102300000031',
            'billing_month' => 8,
            'billing_year' => 2026,
            'pdf_path' => $mru1Path,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('admin.users.clean_mrus', $this->agent), [
            'mru_ids' => [$this->mru1->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // MRU 1 deleted
        $this->assertDatabaseMissing('mrus', ['id' => $this->mru1->id]);
        $this->assertDatabaseMissing('consumer_accounts', ['ca_number' => '102300000031']);
        $this->assertDatabaseMissing('bill_records', ['ca_number' => '102300000031']);
        Storage::disk('local')->assertMissing($mru1Path);

        // MRU 2 and User preserved
        $this->assertDatabaseHas('mrus', ['id' => $this->mru2->id]);
        $this->assertDatabaseHas('consumer_accounts', ['ca_number' => '102300000032']);
        $this->assertDatabaseHas('users', ['id' => $this->agent->id]);
    }

    /**
     * 5. Clean consumer bills by cycle flushes bill records for that cycle,
     *    preserving the MRU structure.
     */
    public function test_clean_consumer_bills_by_cycle_deletes_bills_for_month_while_preserving_mru(): void
    {
        $billMonth7 = BillRecord::create([
            'user_id' => $this->agent->id,
            'mru_id' => $this->mru1->id,
            'ca_number' => '102300000041',
            'billing_month' => 7,
            'billing_year' => 2026,
        ]);

        $billMonth8 = BillRecord::create([
            'user_id' => $this->agent->id,
            'mru_id' => $this->mru1->id,
            'ca_number' => '102300000042',
            'billing_month' => 8,
            'billing_year' => 2026,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('admin.users.clean_bills', $this->agent), [
            'mru_id' => $this->mru1->id,
            'billing_month' => 7,
            'billing_year' => 2026,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Month 7 deleted, Month 8 kept
        $this->assertDatabaseMissing('bill_records', ['id' => $billMonth7->id]);
        $this->assertDatabaseHas('bill_records', ['id' => $billMonth8->id]);
        $this->assertDatabaseHas('mrus', ['id' => $this->mru1->id]);
    }

    /**
     * 6. Complete user purge permanently deletes the user account.
     */
    public function test_purge_user_deletes_entire_account(): void
    {
        $this->actingAs($this->admin);

        $response = $this->delete(route('admin.users.purge', $this->agent), [
            'confirm_text' => 'DELETE',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $this->agent->id]);
    }
}
