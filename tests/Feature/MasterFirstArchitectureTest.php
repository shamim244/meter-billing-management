<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\Plan;
use App\Models\User;
use App\Services\Plan\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterFirstArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);
    }

    protected function subscribeUser(User $user): void
    {
        $plan = Plan::firstOrCreate(
            ['name' => 'Unlimited Test Plan'],
            [
                'included_mrus' => 50,
                'included_consumers' => 50000,
                'extra_mru_rate' => 0,
                'extra_consumer_rate' => 0,
                'is_active' => true,
            ]
        );

        $duration = $plan->durations()->firstOrCreate(
            ['duration_unit' => 'month', 'duration_value' => 1],
            ['final_price' => 0, 'is_active' => true]
        );

        app(PlanService::class)->subscribeAgent($user, $plan, $duration);
    }

    public function test_user_can_add_consumer_with_tariff_basis_and_baseline_amount(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0477',
            'name' => 'Gerua',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post("/mrus/{$mru->id}/consumers", [
            'ca_number' => '10230046961',
            'consumer_name' => 'Ramesh Kumar',
            'meter_no' => '3808220',
            'mobile' => '9876543210',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'LK',
            'baseline_amount' => 350.50,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230046961',
            'consumer_name' => 'Ramesh Kumar',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'LK',
            'baseline_amount' => 350.50,
        ]);
    }

    public function test_user_can_update_consumer_master_fields(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0477',
            'name' => 'Gerua',
            'status' => 'active',
        ]);

        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230046961',
            'consumer_name' => 'Ramesh Kumar',
            'tariff_category' => 'DS-I',
            'billing_basis' => 'OK',
            'baseline_amount' => 200.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put("/mrus/{$mru->id}/consumers/{$consumer->id}", [
            'consumer_name' => 'Ramesh Kumar Updated',
            'meter_no' => '9988776',
            'mobile' => '9123456789',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'MD',
            'baseline_amount' => 450.75,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('consumer_accounts', [
            'id' => $consumer->id,
            'consumer_name' => 'Ramesh Kumar Updated',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'MD',
            'baseline_amount' => 450.75,
        ]);
    }

    public function test_user_can_bulk_import_consumers_with_master_attributes(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0014',
            'name' => 'Lalpur',
            'status' => 'active',
        ]);

        $importText = "102300783538, Ramesh Kumar, DS-II, OK, 450.00, 3808220, 9876543210, Lalpur Main\n"
                    . "102300783541, Suresh Singh, NDS-I, LK, 620.50, 3808221, 9876543211, Lalpur East";

        $response = $this->actingAs($user)->post("/mrus/{$mru->id}/consumers/import", [
            'ca_data' => $importText,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('consumer_accounts', 2);

        $this->assertDatabaseHas('consumer_accounts', [
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'consumer_name' => 'Ramesh Kumar',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'OK',
            'baseline_amount' => 450.00,
        ]);

        $this->assertDatabaseHas('consumer_accounts', [
            'mru_id' => $mru->id,
            'ca_number' => '102300783541',
            'consumer_name' => 'Suresh Singh',
            'tariff_category' => 'NDS-I',
            'billing_basis' => 'LK',
            'baseline_amount' => 620.50,
        ]);
    }

    public function test_user_can_export_consumers_with_master_attributes(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0477',
            'name' => 'Gerua',
            'status' => 'active',
        ]);

        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230046961',
            'consumer_name' => 'Ramesh Kumar',
            'meter_no' => '3808220',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'LK',
            'baseline_amount' => 520.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get("/mrus/{$mru->id}/consumers/export");
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Tariff Category', $content);
        $this->assertStringContainsString('Billing Basis', $content);
        $this->assertStringContainsString('Baseline Amount', $content);
        $this->assertStringContainsString('DS-II', $content);
        $this->assertStringContainsString('LK', $content);
        $this->assertStringContainsString('520.00', $content);
    }

    public function test_create_cycle_only_seeds_bill_record_with_master_fields_without_pdf(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->subscribeUser($user);

        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0244',
            'name' => 'NISARBHATI',
            'status' => 'active',
        ]);

        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230046961',
            'consumer_name' => 'John Doe',
            'meter_no' => '3808220',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'MD',
            'baseline_amount' => 450.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson("/mrus/{$mru->id}/start-billing", [
            'billing_month' => 9,
            'billing_year' => 2026,
            'action_type' => 'create_only',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('bill_records', [
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230046961',
            'consumer_name' => 'John Doe',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'MD',
            'total_amount' => 450.00,
            'download_status' => 'pending',
            'pdf_path' => null,
        ]);
    }

    public function test_dashboard_uses_master_account_fields_when_pdf_data_is_absent(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0244',
            'name' => 'NISARBHATI',
            'status' => 'active',
        ]);

        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230046961',
            'consumer_name' => 'Master User',
            'meter_no' => 'MTR-12345',
            'tariff_category' => 'NDS-II',
            'billing_basis' => 'PL',
            'baseline_amount' => 880.00,
            'status' => 'active',
        ]);

        // Bill record without PDF and with null tariff/amount in bill record
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230046961',
            'billing_month' => 9,
            'billing_year' => 2026,
            'bill_month_label' => 'SEP, 2026',
            'tariff_category' => null,
            'billing_basis' => null,
            'total_amount' => null,
            'download_status' => 'pending',
        ]);

        $response = $this->actingAs($user)->getJson("/dashboard/data?mru_id={$mru->id}&month=9&year=2026");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $first = $data[0];

        $this->assertEquals('NDS-II', $first['tariff_category']);
        $this->assertEquals('PL', $first['billing_basis']);
        $this->assertEquals(880.00, (float) $first['total_amount']);
    }

    public function test_smart_average_calculation_without_pdf_using_reading_deltas(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0244',
            'name' => 'NISARBHATI',
            'status' => 'active',
        ]);

        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'consumer_name' => 'Delta Consumer',
            'meter_no' => 'DELTA-01',
            'status' => 'active',
        ]);

        // Month 6: Delta = 50 (no units_consumed column set, simulating pure reading inputs)
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 6,
            'billing_year' => 2026,
            'bill_month_label' => 'JUN, 2026',
            'previous_reading' => '100',
            'current_reading' => '150',
            'billing_basis' => 'OK',
            'download_status' => 'pending',
        ]);

        // Month 7: Delta = 60
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 7,
            'billing_year' => 2026,
            'bill_month_label' => 'JUL, 2026',
            'previous_reading' => '150',
            'current_reading' => '210',
            'billing_basis' => 'OK',
            'download_status' => 'pending',
        ]);

        // Month 8: Delta = 70
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 8,
            'billing_year' => 2026,
            'bill_month_label' => 'AUG, 2026',
            'previous_reading' => '210',
            'current_reading' => '280',
            'billing_basis' => 'OK',
            'download_status' => 'pending',
        ]);

        // Month 9 (Active month): previous_reading = 280, current_reading not yet submitted
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 9,
            'billing_year' => 2026,
            'bill_month_label' => 'SEP, 2026',
            'previous_reading' => '280',
            'billing_basis' => 'OK',
            'download_status' => 'pending',
        ]);

        $response = $this->actingAs($user)->getJson("/dashboard/data?mru_id={$mru->id}&month=9&year=2026");

        $response->assertStatus(200);
        $record = $response->json('data.0');
        $this->assertNotNull($record);

        // Deltas are [50, 60, 70], median is 60. Projected reading = 280 + 60 = 340.
        $this->assertEquals(60, $record['smart_avg_units']);
        $this->assertEquals(340, (int) $record['projected_reading']);
    }

    public function test_bulk_project_readings_works_without_pdf_using_reading_deltas(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0244',
            'name' => 'NISARBHATI',
            'status' => 'active',
        ]);

        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'consumer_name' => 'Bulk Project Consumer',
            'status' => 'active',
        ]);

        // Prior Month 6: Delta = 40
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 6,
            'billing_year' => 2026,
            'previous_reading' => '100',
            'current_reading' => '140',
            'billing_basis' => 'OK',
        ]);

        // Prior Month 7: Delta = 40
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 7,
            'billing_year' => 2026,
            'previous_reading' => '140',
            'current_reading' => '180',
            'billing_basis' => 'OK',
        ]);

        // Active Month 8: previous_reading = 180, working_reading = null
        $activeBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '180',
            'billing_basis' => 'OK',
        ]);

        $response = $this->actingAs($user)->postJson('/bills/bulk-project-readings', [
            'mru_id' => $mru->id,
            'month' => 8,
            'year' => 2026,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $activeBill->refresh();
        // 180 + 40 = 220
        $this->assertEquals(220, (int) $activeBill->working_reading);
        $this->assertEquals(40, (int) $activeBill->units_consumed);
        $this->assertEquals(40, (int) $activeBill->calculated_avg_units);
    }
}
