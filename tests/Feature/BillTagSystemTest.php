<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\Mru;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\BillTagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillTagSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;
    protected BillTagService $tagService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $this->admin = User::where('email', 'admin@nbpdcl-saas.com')->first();
        $this->agent = User::where('email', 'test@example.com')->first();
        $this->tagService = app(BillTagService::class);
    }

    /**
     * 1. Default tag is 'OK' on newly created bill records.
     */
    public function test_default_tag_is_ok_on_new_bill_records(): void
    {
        $mru = Mru::create([
            'user_id' => $this->agent->id,
            'code' => 'MRU_TAG_01',
            'name' => 'MRU Tag Test',
            'status' => 'active',
        ]);

        $bill = BillRecord::create([
            'user_id' => $this->agent->id,
            'ca_number' => '100200300400',
            'mru_id' => $mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
            'consumer_name' => 'Rajesh Kumar',
            'total_amount' => 540.00,
            'current_reading' => '1250',
            'previous_reading' => '1150',
            'units_consumed' => 100,
        ]);

        $this->assertEquals('OK', $bill->fresh()->tag);
    }

    /**
     * 2. Tag can be updated via POST /bills/tag and synchronizes in bill_records and bill_statuses.
     */
    public function test_tag_updates_cleanly_via_ajax_endpoint(): void
    {
        $bill = BillRecord::create([
            'user_id' => $this->agent->id,
            'ca_number' => '100200300401',
            'billing_month' => 8,
            'billing_year' => 2026,
            'consumer_name' => 'Amit Sharma',
            'total_amount' => 300.00,
            'tag' => 'OK',
        ]);

        $response = $this->actingAs($this->agent)->postJson(route('bills.tag'), [
            'id' => $bill->id,
            'ca_number' => $bill->ca_number,
            'billing_month' => 8,
            'billing_year' => 2026,
            'tag' => 'BQC',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'tag' => 'BQC',
            'display_tag' => 'BQC',
        ]);

        $this->assertEquals('BQC', $bill->fresh()->tag);

        $this->assertDatabaseHas('bill_statuses', [
            'user_id' => $this->agent->id,
            'ca_number' => '100200300401',
            'billing_month' => 8,
            'billing_year' => 2026,
            'tag' => 'BQC',
        ]);
    }

    /**
     * 3. Long tag 'NOT_APPROVED_PREV_BQC_RQC' resolves clean display and full labels.
     */
    public function test_long_tag_handles_clean_short_and_full_labels(): void
    {
        $bill = BillRecord::create([
            'user_id' => $this->agent->id,
            'ca_number' => '100200300402',
            'billing_month' => 8,
            'billing_year' => 2026,
            'consumer_name' => 'Suman Devi',
            'total_amount' => 450.00,
            'tag' => 'OK',
        ]);

        $response = $this->actingAs($this->agent)->postJson(route('bills.tag'), [
            'id' => $bill->id,
            'ca_number' => $bill->ca_number,
            'billing_month' => 8,
            'billing_year' => 2026,
            'tag' => 'NOT_APPROVED_PREV_BQC_RQC',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'tag' => 'NOT_APPROVED_PREV_BQC_RQC',
            'display_tag' => 'Not-Apprv Prev BQC/RQC',
            'full_tag' => 'Not-approved Previous BQC and RQC',
        ]);

        $this->assertEquals('NOT_APPROVED_PREV_BQC_RQC', $bill->fresh()->tag);
    }

    /**
     * 4. CSV export includes Tag column with accurate values.
     */
    public function test_csv_export_includes_tag_column(): void
    {
        BillRecord::create([
            'user_id' => $this->agent->id,
            'ca_number' => '100200300403',
            'billing_month' => 8,
            'billing_year' => 2026,
            'consumer_name' => 'Pooja Singh',
            'total_amount' => 250.00,
            'tag' => '24days',
        ]);

        $response = $this->actingAs($this->agent)->get(route('bills.export-csv', [
            'month' => 8,
            'year' => 2026,
        ]));

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('Tag', $content);
        $this->assertStringContainsString('24 Days', $content);
        $this->assertStringContainsString('100200300403', $content);
    }

    /**
     * 5. Dashboard data endpoint correctly filters bills by Tag.
     */
    public function test_dashboard_data_filters_by_tag(): void
    {
        BillRecord::create([
            'user_id' => $this->agent->id,
            'ca_number' => '100200300404',
            'billing_month' => 8,
            'billing_year' => 2026,
            'consumer_name' => 'Vikram Roy',
            'total_amount' => 150.00,
            'tag' => 'RCQ',
        ]);

        BillRecord::create([
            'user_id' => $this->agent->id,
            'ca_number' => '100200300405',
            'billing_month' => 8,
            'billing_year' => 2026,
            'consumer_name' => 'Kiran Mehta',
            'total_amount' => 180.00,
            'tag' => 'OK',
        ]);

        // Filter RCQ
        $response = $this->actingAs($this->agent)->getJson(route('dashboard.data', [
            'month' => 8,
            'year' => 2026,
            'tag_filter' => 'RCQ',
        ]));

        $response->assertStatus(200);
        $items = $response->json('data');
        $this->assertCount(1, $items);
        $this->assertEquals('100200300404', $items[0]['ca_number']);
        $this->assertEquals('RCQ', $items[0]['tag']);
    }

    /**
     * 6. Admin can manage tags in Admin Panel and reset to factory defaults.
     */
    public function test_admin_can_manage_tags(): void
    {
        // Admin index
        $indexRes = $this->actingAs($this->admin)->get(route('admin.tags.index'));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('Bill Review Tags Manager');

        // Admin adds new custom tag
        $storeRes = $this->actingAs($this->admin)->post(route('admin.tags.store'), [
            'code' => 'DOOR_LOCKED',
            'label' => 'Door Locked Premise',
            'short_label' => 'Door Locked',
            'color' => 'amber',
        ]);
        $storeRes->assertRedirect(route('admin.tags.index'));

        $this->assertNotNull($this->tagService->getTagByCode('DOOR_LOCKED'));

        // Admin factory reset
        $resetRes = $this->actingAs($this->admin)->post(route('admin.tags.reset_factory'));
        $resetRes->assertRedirect(route('admin.tags.index'));

        $this->assertNull($this->tagService->getTagByCode('DOOR_LOCKED'));
        $this->assertNotNull($this->tagService->getTagByCode('OK'));
    }

    /**
     * 7. Admin can delete a custom/non-default tag, but cannot delete the active default tag.
     */
    public function test_admin_can_delete_tag_and_cannot_delete_default_tag(): void
    {
        // Add a tag first
        $this->actingAs($this->admin)->post(route('admin.tags.store'), [
            'code' => 'TEMPORARY_TAG',
            'label' => 'Temporary Tag',
            'short_label' => 'Temp',
            'color' => 'slate',
        ]);

        $this->assertNotNull($this->tagService->getTagByCode('TEMPORARY_TAG'));

        // Admin deletes TEMPORARY_TAG
        $delRes = $this->actingAs($this->admin)->delete(route('admin.tags.destroy', 'TEMPORARY_TAG'));
        $delRes->assertRedirect(route('admin.tags.index'));
        $delRes->assertSessionHas('success');

        $this->assertNull($this->tagService->getTagByCode('TEMPORARY_TAG'));

        // Admin tries to delete default tag 'OK'
        $delDefaultRes = $this->actingAs($this->admin)->delete(route('admin.tags.destroy', 'OK'));
        $delDefaultRes->assertRedirect(route('admin.tags.index'));
        $delDefaultRes->assertSessionHasErrors('delete');

        $this->assertNotNull($this->tagService->getTagByCode('OK'));
    }
}
