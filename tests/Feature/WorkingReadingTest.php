<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\Mru;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkingReadingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_dashboard_data_includes_4_box_reading_metrics(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        // 1. July Bill Record (Previous Month in DB)
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 7,
            'billing_year' => 2026,
            'bill_month_label' => 'JUL, 2026',
            'current_reading' => '400',
            'previous_reading' => '350',
            'units_consumed' => 50,
            'billing_basis' => 'OK',
        ]);

        // 2. August Bill Record (Active Month)
        $augBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 8,
            'billing_year' => 2026,
            'bill_month_label' => 'AUG, 2026',
            'current_reading' => '452',
            'previous_reading' => '400',
            'units_consumed' => 52,
            'billing_basis' => 'OK',
            'working_reading' => '452',
        ]);

        $response = $this->actingAs($user)->getJson("/dashboard/data?mru_id={$mru->id}&month=8&year=2026");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $item = $data[0];

        // Verify Box 1: Working Reading
        $this->assertEquals('452', $item['working_reading']);
        $this->assertEquals(52, $item['working_diff_units']);

        // Verify Box 2: Previous Reading from DB
        $this->assertEquals('400', $item['db_prev_reading']);

        // Verify Box 3: Smart Average (median of 50 and 52)
        $this->assertEquals(51, $item['smart_avg_units']);

        // Verify Box 4: Official PDF Reading & Sync Match
        $this->assertEquals('452', $item['official_pdf_reading']);
        $this->assertEquals('matched', $item['pdf_sync_status']);
    }

    public function test_can_update_working_reading_via_ajax(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 8,
            'billing_year' => 2026,
            'working_reading' => '400',
        ]);

        $response = $this->actingAs($user)->postJson('/bills/update-working-reading', [
            'id' => $bill->id,
            'working_reading' => '475',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'working_reading' => '475',
        ]);

        $this->assertDatabaseHas('bill_records', [
            'id' => $bill->id,
            'working_reading' => '475',
        ]);
    }

    public function test_can_bulk_project_working_readings(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        $bill1 = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1001',
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '500',
            'units_consumed' => 60,
        ]);

        $bill2 = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1002',
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '800',
            'units_consumed' => 45,
        ]);

        $response = $this->actingAs($user)->postJson('/bills/bulk-project-readings', [
            'month' => 8,
            'year' => 2026,
            'mru_id' => $mru->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 2,
        ]);

        $this->assertEquals('560', $bill1->fresh()->working_reading);
        $this->assertEquals('845', $bill2->fresh()->working_reading);
    }

    public function test_previous_reading_prioritizes_previous_month_working_reading(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        // July has PDF reading 400, but user entered Working Reading 415 in July
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 7,
            'billing_year' => 2026,
            'current_reading' => '400',
            'previous_reading' => '350',
            'units_consumed' => 50,
            'working_reading' => '415',
        ]);

        // August is newly created with no working reading yet
        $augBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '400',
        ]);

        $response = $this->actingAs($user)->getJson("/dashboard/data?mru_id={$mru->id}&month=8&year=2026");
        $response->assertStatus(200);

        $item = $response->json('data')[0];

        // Must get '415' from July's WORKING READING, not 400!
        $this->assertEquals('415', $item['db_prev_reading']);
        // Projected must be 415 + 50 = 465
        $this->assertEquals('465', $item['working_reading']);
    }

    public function test_working_reading_never_less_than_official_pdf_reading(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        // July has working reading 400
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 7,
            'billing_year' => 2026,
            'working_reading' => '400',
            'units_consumed' => 40,
        ]);

        // August PDF came with high official reading 475 (while 400 + 40 = 440)
        $augBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 8,
            'billing_year' => 2026,
            'current_reading' => '475',
            'units_consumed' => 75,
        ]);

        $response = $this->actingAs($user)->getJson("/dashboard/data?mru_id={$mru->id}&month=8&year=2026");
        $response->assertStatus(200);

        $item = $response->json('data')[0];

        // Invariant: Working Reading MUST NEVER be < PDF Reading (475)!
        $this->assertGreaterThanOrEqual(475, (int) $item['working_reading']);
        $this->assertEquals('matched', $item['pdf_sync_status']); // Exact match or ahead
    }

    public function test_bulk_project_readings_never_overwrites_existing_readings_or_submitted_bills(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        // 1. Bill with no working reading and pending status (should be projected: 500 + 60 = 560)
        $bill1 = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1001',
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '500',
            'units_consumed' => 60,
            'review_status' => 'pending',
        ]);

        // 2. Bill with manual override reading 190 (must NOT be touched)
        $bill2 = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1002',
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '100',
            'units_consumed' => 50,
            'working_reading' => '190',
            'reading_source' => 'manual',
            'review_status' => 'pending',
        ]);

        // 3. Bill marked submitted on BillRecord (must NOT be touched)
        $bill3 = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1003',
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '300',
            'units_consumed' => 40,
            'review_status' => 'submitted',
        ]);

        // 4. Bill marked submitted in BillStatus table (must NOT be touched)
        $bill4 = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1004',
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '400',
            'units_consumed' => 40,
            'review_status' => 'pending',
        ]);
        BillStatus::create([
            'user_id' => $user->id,
            'ca_number' => '1004',
            'billing_month' => 8,
            'billing_year' => 2026,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($user)->postJson('/bills/bulk-project-readings', [
            'month' => 8,
            'year' => 2026,
            'mru_id' => $mru->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 1,
        ]);

        // Bill 1 was projected
        $this->assertEquals('560', $bill1->fresh()->working_reading);

        // Bill 2 manual override 190 was preserved (not overwritten by 150)
        $this->assertEquals('190', $bill2->fresh()->working_reading);

        // Bill 3 submitted was untouched
        $this->assertNull($bill3->fresh()->working_reading);

        // Bill 4 submitted via BillStatus was untouched
        $this->assertNull($bill4->fresh()->working_reading);
    }

    public function test_single_update_working_reading_requires_force_flag_on_submitted_bills(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 8,
            'billing_year' => 2026,
            'working_reading' => '190',
            'review_status' => 'submitted',
        ]);

        // Attempt update without force flag -> rejected with 422
        $response = $this->actingAs($user)->postJson('/bills/update-working-reading', [
            'id' => $bill->id,
            'working_reading' => '150',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'requires_override' => true,
            'is_submitted' => true,
        ]);
        $this->assertEquals('190', $bill->fresh()->working_reading);

        // Attempt update WITH force flag -> allowed
        $responseWithForce = $this->actingAs($user)->postJson('/bills/update-working-reading', [
            'id' => $bill->id,
            'working_reading' => '195',
            'force' => true,
        ]);

        $responseWithForce->assertStatus(200);
        $responseWithForce->assertJson([
            'success' => true,
            'working_reading' => '195',
        ]);
        $this->assertEquals('195', $bill->fresh()->working_reading);
    }

    public function test_cascade_protection_does_not_overwrite_submitted_future_bills(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        // July bill: initial working reading 100
        $julyBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 7,
            'billing_year' => 2026,
            'working_reading' => '100',
            'units_consumed' => 50,
            'review_status' => 'pending',
        ]);

        // August bill: user has manually entered 190 and submitted the bill
        $augBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '100',
            'working_reading' => '190',
            'units_consumed' => 50,
            'review_status' => 'submitted',
        ]);

        // September bill: pending cycle with units 50
        $septBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 9,
            'billing_year' => 2026,
            'previous_reading' => '190',
            'working_reading' => '240',
            'units_consumed' => 50,
            'review_status' => 'pending',
        ]);

        // Operator updates July reading from 100 to 120
        $response = $this->actingAs($user)->postJson('/bills/update-working-reading', [
            'id' => $julyBill->id,
            'working_reading' => '120',
        ]);

        $response->assertStatus(200);

        // July bill is updated
        $this->assertEquals('120', $julyBill->fresh()->working_reading);

        // August bill is SUBMITTED, so it MUST NOT be overwritten (remains 190, not 120 + 50 = 170)
        $this->assertEquals('190', $augBill->fresh()->working_reading);

        // September bill is pending, so it chains forward from August's protected 190 + 50 = 240
        $this->assertEquals('240', $septBill->fresh()->working_reading);
    }

    public function test_dashboard_data_identifies_manual_versus_projected_readings(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        // Previous reading is 100, units 50 -> projected would be 150
        // But working reading is 190 (manual override)
        $bill1 = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1001',
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '100',
            'units_consumed' => 50,
            'working_reading' => '190',
            'reading_source' => 'manual',
            'billing_basis' => 'OK',
        ]);

        // Bill 2 has no working reading yet
        $bill2 = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1002',
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '200',
            'units_consumed' => 40,
            'billing_basis' => 'OK',
        ]);

        $response = $this->actingAs($user)->getJson("/dashboard/data?mru_id={$mru->id}&month=8&year=2026");
        $response->assertStatus(200);

        $items = collect($response->json('data'))->keyBy('ca_number');

        $this->assertTrue($items['1001']['is_manual']);
        $this->assertFalse($items['1001']['is_projected']);
        $this->assertEquals('manual', $items['1001']['reading_source']);

        $this->assertFalse($items['1002']['is_manual']);
        $this->assertTrue($items['1002']['is_projected']);
        $this->assertEquals('auto', $items['1002']['reading_source']);
    }

    public function test_single_update_requires_force_flag_when_submitted_via_bill_status_table(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783599',
            'billing_month' => 8,
            'billing_year' => 2026,
            'working_reading' => '190',
            'review_status' => 'pending',
        ]);

        BillStatus::create([
            'user_id' => $user->id,
            'ca_number' => '102300783599',
            'billing_month' => 8,
            'billing_year' => 2026,
            'status' => 'submitted',
        ]);

        // Attempt update without force flag -> rejected with 422
        $response = $this->actingAs($user)->postJson('/bills/update-working-reading', [
            'id' => $bill->id,
            'working_reading' => '150',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'requires_override' => true,
            'is_submitted' => true,
        ]);
        $this->assertEquals('190', $bill->fresh()->working_reading);

        // Attempt update WITH force flag -> allowed
        $responseWithForce = $this->actingAs($user)->postJson('/bills/update-working-reading', [
            'id' => $bill->id,
            'working_reading' => '195',
            'force' => true,
        ]);

        $responseWithForce->assertStatus(200);
        $responseWithForce->assertJson([
            'success' => true,
            'working_reading' => '195',
        ]);
        $this->assertEquals('195', $bill->fresh()->working_reading);
    }

    public function test_cascade_protection_protects_bills_marked_submitted_in_bill_status_table(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        // July bill: initial working reading 100
        $julyBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783598',
            'billing_month' => 7,
            'billing_year' => 2026,
            'working_reading' => '100',
            'units_consumed' => 50,
            'review_status' => 'pending',
        ]);

        // August bill: user has entered 190, review_status is pending in bill_records, BUT submitted in BillStatus
        $augBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783598',
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '100',
            'working_reading' => '190',
            'units_consumed' => 50,
            'review_status' => 'pending',
        ]);

        BillStatus::create([
            'user_id' => $user->id,
            'ca_number' => '102300783598',
            'billing_month' => 8,
            'billing_year' => 2026,
            'status' => 'submitted',
        ]);

        // September bill: pending cycle with units 50
        $septBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783598',
            'billing_month' => 9,
            'billing_year' => 2026,
            'previous_reading' => '190',
            'working_reading' => '240',
            'units_consumed' => 50,
            'review_status' => 'pending',
        ]);

        // Operator updates July reading from 100 to 120
        $response = $this->actingAs($user)->postJson('/bills/update-working-reading', [
            'id' => $julyBill->id,
            'working_reading' => '120',
        ]);

        $response->assertStatus(200);

        // July bill is updated
        $this->assertEquals('120', $julyBill->fresh()->working_reading);

        // August bill is marked submitted in BillStatus, so it MUST NOT be overwritten (remains 190)
        $this->assertEquals('190', $augBill->fresh()->working_reading);

        // September bill chains forward from August's protected 190 + 50 = 240
        $this->assertEquals('240', $septBill->fresh()->working_reading);
    }

    public function test_dashboard_data_provides_default_reading_properties_when_projection_is_zero(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1003',
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '0',
            'units_consumed' => 0,
            'working_reading' => '0',
            'billing_basis' => 'OK',
        ]);

        $response = $this->actingAs($user)->getJson("/dashboard/data?mru_id={$mru->id}&month=8&year=2026");
        $response->assertStatus(200);

        $items = collect($response->json('data'))->keyBy('ca_number');
        $item = $items['1003'];

        $this->assertArrayHasKey('is_manual', $item);
        $this->assertArrayHasKey('is_projected', $item);
        $this->assertArrayHasKey('reading_source', $item);
        $this->assertFalse($item['is_manual']);
        $this->assertEquals('auto', $item['reading_source']);
    }

    public function test_new_cycle_defaults_to_auto_and_is_not_manual(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        // Previous August bill
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783999',
            'billing_month' => 8,
            'billing_year' => 2026,
            'working_reading' => '3560',
            'review_status' => 'submitted',
            'units_consumed' => 20,
            'billing_basis' => 'OK',
        ]);

        // September bill in new cycle (reading_source auto, working_reading was old or null)
        $septBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783999',
            'billing_month' => 9,
            'billing_year' => 2026,
            'previous_reading' => '3560',
            'working_reading' => null, // Fresh cycle starts with null working reading
            'reading_source' => 'auto',
            'review_status' => 'pending',
            'units_consumed' => 20,
            'billing_basis' => 'OK',
        ]);

        $response = $this->actingAs($user)->getJson("/dashboard/data?mru_id={$mru->id}&month=9&year=2026");
        $response->assertStatus(200);

        $items = collect($response->json('data'))->keyBy('ca_number');
        $item = $items['102300783999'];

        // Must be primary AUTO, NOT manual!
        $this->assertFalse($item['is_manual']);
        $this->assertTrue($item['is_projected']);
        $this->assertEquals('auto', $item['reading_source']);
        $this->assertEquals('3580', $item['working_reading']); // Automatically 3560 + 20
    }

    public function test_manual_update_sets_reading_source_manual_and_bulk_projection_skips_it(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783888',
            'billing_month' => 9,
            'billing_year' => 2026,
            'previous_reading' => '100',
            'working_reading' => '120',
            'reading_source' => 'auto',
            'review_status' => 'pending',
            'units_consumed' => 20,
            'billing_basis' => 'OK',
        ]);

        // Operator explicitly updates reading to 190 manually
        $response = $this->actingAs($user)->postJson('/bills/update-working-reading', [
            'id' => $bill->id,
            'working_reading' => '190',
            'source' => 'manual',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('190', $bill->fresh()->working_reading);
        $this->assertEquals('manual', $bill->fresh()->reading_source);

        // Run bulk project readings
        $bulkResponse = $this->actingAs($user)->postJson('/bills/bulk-project-readings', [
            'mru_id' => $mru->id,
            'month' => 9,
            'year' => 2026,
        ]);

        $bulkResponse->assertStatus(200);
        // The manual bill should NOT have been projected
        $this->assertEquals(0, $bulkResponse->json('count'));
        $this->assertEquals('190', $bill->fresh()->working_reading);
        $this->assertEquals('manual', $bill->fresh()->reading_source);
    }

    public function test_bulk_project_supports_percentage_tuning_increase_and_decrease(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        // Account 1: Test +20% adjustment (Summer / Peak)
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300999111',
            'billing_month' => 8,
            'billing_year' => 2026,
            'current_reading' => '200',
            'previous_reading' => '150',
            'units_consumed' => 50,
            'billing_basis' => 'OK',
        ]);

        $sepBillPlus = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300999111',
            'billing_month' => 9,
            'billing_year' => 2026,
            'previous_reading' => '200',
            'working_reading' => null,
            'reading_source' => 'auto',
            'review_status' => 'pending',
            'billing_basis' => 'OK',
        ]);

        // Account 2: Test -20% adjustment (Winter / Low)
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300999222',
            'billing_month' => 8,
            'billing_year' => 2026,
            'current_reading' => '300',
            'previous_reading' => '250',
            'units_consumed' => 50,
            'billing_basis' => 'OK',
        ]);

        $sepBillMinus = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300999222',
            'billing_month' => 9,
            'billing_year' => 2026,
            'previous_reading' => '300',
            'working_reading' => null,
            'reading_source' => 'auto',
            'review_status' => 'pending',
            'billing_basis' => 'OK',
        ]);

        // 1. Bulk project with +20% adjustment -> 50 * 1.2 = 60 units => 200 + 60 = 260
        $resp = $this->actingAs($user)->postJson('/bills/bulk-project-readings', [
            'mru_id' => $mru->id,
            'month' => 9,
            'year' => 2026,
            'adjustment_percent' => 20,
        ]);

        $resp->assertStatus(200);
        $sepBillPlus->refresh();
        $this->assertEquals(60, $sepBillPlus->units_consumed);
        $this->assertEquals('260', $sepBillPlus->working_reading);

        // Reset Account 2 and test with -20%
        $sepBillMinus->update(['working_reading' => null, 'units_consumed' => null]);
        $respMinus = $this->actingAs($user)->postJson('/bills/bulk-project-readings', [
            'mru_id' => $mru->id,
            'month' => 9,
            'year' => 2026,
            'adjustment_percent' => -20,
        ]);

        $respMinus->assertStatus(200);
        $sepBillMinus->refresh();
        $this->assertEquals(40, $sepBillMinus->units_consumed);
        $this->assertEquals('340', $sepBillMinus->working_reading);

        // 3. User switches directly from -20% to +10% (WITHOUT manual reset)
        // Must calculate from normal 50 kWh base -> 50 * 1.1 = 55 units (reading = 355), NEVER 40 * 1.1 = 44!
        $respSwitch = $this->actingAs($user)->postJson('/bills/bulk-project-readings', [
            'mru_id' => $mru->id,
            'month' => 9,
            'year' => 2026,
            'adjustment_percent' => 10,
        ]);

        $respSwitch->assertStatus(200);
        $sepBillMinus->refresh();
        $this->assertEquals(55, $sepBillMinus->units_consumed);
        $this->assertEquals('355', $sepBillMinus->working_reading);
    }
}
