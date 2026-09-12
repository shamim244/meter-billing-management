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

    public function test_user_can_add_consumer_with_initial_baseline_reading(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0477',
            'name' => 'Gerua',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post("/mrus/{$mru->id}/consumers", [
            'ca_number' => '10230046999',
            'consumer_name' => 'Vijay Kumar',
            'meter_no' => '5544332',
            'mobile' => '9876543210',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'OK',
            'baseline_amount' => 420.00,
            'baseline_previous_reading' => 1250,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230046999',
            'consumer_name' => 'Vijay Kumar',
            'baseline_previous_reading' => 1250,
        ]);
    }

    public function test_bulk_import_handles_6_and_7_column_rows_without_corrupting_columns(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0014',
            'name' => 'Lalpur',
            'status' => 'active',
        ]);

        // 6 columns: CA, Name, Tariff, Basis, Amount, Meter
        // 7 columns: CA, Name, Tariff, Basis, Amount, Meter, Initial Reading
        $importText = "102300990001, Six Col User, DS-II, LK, 350.00, MTR-6COL\n"
                    . "102300990002, Seven Col User, NDS-I, MD, 500.00, MTR-7COL, 2400";

        $response = $this->actingAs($user)->post("/mrus/{$mru->id}/consumers/import", [
            'ca_data' => $importText,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('consumer_accounts', [
            'ca_number' => '102300990001',
            'consumer_name' => 'Six Col User',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'LK',
            'baseline_amount' => 350.00,
            'meter_no' => 'MTR-6COL',
        ]);

        $this->assertDatabaseHas('consumer_accounts', [
            'ca_number' => '102300990002',
            'consumer_name' => 'Seven Col User',
            'tariff_category' => 'NDS-I',
            'billing_basis' => 'MD',
            'baseline_amount' => 500.00,
            'meter_no' => 'MTR-7COL',
            'baseline_previous_reading' => 2400,
        ]);
    }

    public function test_create_cycle_only_populates_working_reading_units_consumed_and_calculated_avg(): void
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
            'ca_number' => '10230088888',
            'consumer_name' => 'Cycle Seeder User',
            'meter_no' => 'MTR-SEED',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'OK',
            'baseline_amount' => 300.00,
            'baseline_previous_reading' => 200,
            'status' => 'active',
        ]);

        // Prior Month bill
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230088888',
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '200',
            'current_reading' => '260',
            'units_consumed' => 60,
            'billing_basis' => 'OK',
        ]);

        $response = $this->actingAs($user)->postJson("/mrus/{$mru->id}/start-billing", [
            'billing_month' => 9,
            'billing_year' => 2026,
            'action_type' => 'create_only',
        ]);

        $response->assertStatus(200);

        $createdBill = BillRecord::where('user_id', $user->id)
            ->where('ca_number', '10230088888')
            ->where('billing_month', 9)
            ->where('billing_year', 2026)
            ->first();

        $this->assertNotNull($createdBill);
        $this->assertEquals(260, (int) $createdBill->previous_reading);
        $this->assertEquals(60, (int) $createdBill->calculated_avg_units);
        $this->assertEquals(60, (int) $createdBill->units_consumed);
        $this->assertEquals(320, (int) $createdBill->working_reading);
    }

    public function test_update_working_reading_falls_back_to_baseline_reading_when_previous_is_missing(): void
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
            'ca_number' => '10230077777',
            'consumer_name' => 'Fallback Reading User',
            'meter_no' => 'MTR-FB',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'OK',
            'baseline_previous_reading' => 500,
            'status' => 'active',
        ]);

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230077777',
            'billing_month' => 9,
            'billing_year' => 2026,
            'previous_reading' => null,
            'billing_basis' => 'OK',
        ]);

        $response = $this->actingAs($user)->postJson('/bills/update-working-reading', [
            'id' => $bill->id,
            'working_reading' => '575',
        ]);

        $response->assertStatus(200);
        $bill->refresh();

        $this->assertEquals('575', $bill->working_reading);
        $this->assertEquals(75, (int) $bill->units_consumed);
    }

    public function test_bill_export_csv_includes_master_tariff_basis_and_working_reading(): void
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
            'ca_number' => '10230066666',
            'consumer_name' => 'Export Master User',
            'meter_no' => 'MTR-EXP',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'LK',
            'baseline_amount' => 750.00,
            'status' => 'active',
        ]);

        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230066666',
            'billing_month' => 9,
            'billing_year' => 2026,
            'working_reading' => '820',
            'previous_reading' => '780',
            'units_consumed' => 40,
            'total_amount' => null, // empty total amount, should fall back to baseline
            'tariff_category' => null, // empty, should fall back to master
            'billing_basis' => null, // empty, should fall back to master
        ]);

        $response = $this->actingAs($user)->get('/bills/export-csv?month=9&year=2026');
        $response->assertStatus(200);

        $content = $response->streamedContent();
        $this->assertStringContainsString('Tariff Category', $content);
        $this->assertStringContainsString('Billing Basis', $content);
        $this->assertStringContainsString('Working Reading', $content);
        $this->assertStringContainsString('Export Master User', $content);
        $this->assertStringContainsString('DS-II', $content);
        $this->assertStringContainsString('LK', $content);
        $this->assertStringContainsString('820', $content);
        $this->assertStringContainsString('750.00', $content);
    }

    public function test_agent_backup_registry_csv_includes_billing_basis_and_baseline_values(): void
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
            'ca_number' => '10230055555',
            'consumer_name' => 'Backup Master User',
            'meter_no' => 'MTR-BAK',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'MD',
            'baseline_amount' => 999.50,
            'baseline_previous_reading' => 3120,
            'status' => 'active',
        ]);

        $tempZip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_backup_' . uniqid() . '.zip';
        $exportService = app(\App\Services\Backup\AgentWorkspaceExportService::class);
        $exportService->export($user, $tempZip);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($tempZip));
        $csvContent = $zip->getFromName('ledger/02_consumers_registry.csv');
        $this->assertNotEmpty($csvContent);
        $zip->close();
        @unlink($tempZip);

        $this->assertStringContainsString('Billing Basis', $csvContent);
        $this->assertStringContainsString('Baseline Amount', $csvContent);
        $this->assertStringContainsString('Baseline Reading', $csvContent);
        $this->assertStringContainsString('MD', $csvContent);
        $this->assertStringContainsString('999.50', $csvContent);
        $this->assertStringContainsString('3120', $csvContent);
    }
}
