<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkingReadingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
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
        $this->assertGreaterThanOrEqual(475, (int)$item['working_reading']);
        $this->assertEquals('matched', $item['pdf_sync_status']); // Exact match or ahead
    }
}
