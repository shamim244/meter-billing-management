<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSmartAvgGuardrailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_tuning_persists_min_max_and_exclusion_settings_in_session(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/dashboard/tuning', [
            'tuning_enabled' => true,
            'percent' => 10,
            'steps' => [10],
            'base_units' => 50,
            'min_units_floor' => 30,
            'max_units_ceiling' => 150,
            'auto_exclude_overposted' => true,
            'exclude_critical_doubt' => true,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertTrue(session('dashboard_tuning_enabled'));
        $this->assertEquals(30, session('dashboard_tuning_min_floor'));
        $this->assertEquals(150, session('dashboard_tuning_max_ceiling'));
        $this->assertTrue(session('dashboard_tuning_auto_exclude_overposted'));
        $this->assertTrue(session('dashboard_tuning_exclude_critical_doubt'));

        // Reset tuning back to baseline normal
        $resetRes = $this->actingAs($user)->postJson('/dashboard/tuning/reset');
        $resetRes->assertOk();
        $this->assertNull(session('dashboard_tuning_enabled'));
        $this->assertNull(session('dashboard_tuning_min_floor'));
    }

    public function test_individual_ca_average_tuning_endpoint_updates_consumer_profile(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990099',
            'consumer_name' => 'Test Isolated Consumer',
        ]);

        $response = $this->actingAs($user)->postJson("/consumers/{$consumer->ca_number}/average-tuning", [
            'custom_avg_units' => 75,
            'min_avg_units' => 30,
            'max_avg_units' => 120,
            'is_avg_excluded' => true,
            'avg_exclude_reason' => 'Defective slow meter under dispute',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'consumer' => [
                'ca_number' => '99990099',
                'custom_avg_units' => 75,
                'min_avg_units' => 30,
                'max_avg_units' => 120,
                'is_avg_excluded' => true,
            ],
        ]);

        $consumer->refresh();
        $this->assertEquals(75, $consumer->custom_avg_units);
        $this->assertEquals(30, $consumer->min_avg_units);
        $this->assertEquals(120, $consumer->max_avg_units);
        $this->assertTrue($consumer->is_avg_excluded);
        $this->assertEquals('Defective slow meter under dispute', $consumer->avg_exclude_reason);
    }

    public function test_individual_tuning_rejects_min_greater_than_max(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990098',
            'consumer_name' => 'Test Inverted Bounds Consumer',
        ]);

        $response = $this->actingAs($user)->postJson("/consumers/{$consumer->ca_number}/average-tuning", [
            'min_avg_units' => 100,
            'max_avg_units' => 40,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('cannot be greater than Max Ceiling', (string) $response->json('message'));
    }

    public function test_bulk_project_skips_excluded_and_overposted_accounts(): void
    {
        $user = User::factory()->create();
        $mru = Mru::create(['user_id' => $user->id, 'code' => 'MRU_G1', 'name' => 'Guardrail MRU']);

        // Account 1: Normal account (should be projected)
        $c1 = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990011',
            'consumer_name' => 'Normal Account',
            'tariff_category' => 'DS1D',
            'billing_basis' => 'OK',
        ]);
        $b1 = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $c1->ca_number,
            'billing_month' => 10,
            'billing_year' => 2026,
            'previous_reading' => '500',
            'working_reading' => null,
            'reading_source' => 'auto',
            'review_status' => 'pending',
        ]);

        // Account 2: Manually Excluded account (should be skipped)
        $c2 = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990022',
            'consumer_name' => 'Manually Excluded Account',
            'tariff_category' => 'DS1D',
            'billing_basis' => 'OK',
            'is_avg_excluded' => true,
            'avg_exclude_reason' => 'Disputed meter',
        ]);
        $b2 = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $c2->ca_number,
            'billing_month' => 10,
            'billing_year' => 2026,
            'previous_reading' => '800',
            'working_reading' => null,
            'reading_source' => 'auto',
            'review_status' => 'pending',
            'is_avg_excluded' => true,
        ]);

        // Account 3: Over-posted account (Physical < Posted, should be skipped)
        $c3 = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990033',
            'consumer_name' => 'Over-Posted Account',
            'tariff_category' => 'DS1D',
            'billing_basis' => 'OK',
            'last_physical_reading' => '950', // Physical 950 < Posted 1000
        ]);
        $b3 = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $c3->ca_number,
            'billing_month' => 10,
            'billing_year' => 2026,
            'previous_reading' => '1000', // Posted 1000 > Physical 950
            'working_reading' => null,
            'reading_source' => 'auto',
            'review_status' => 'pending',
        ]);

        $res = $this->actingAs($user)->postJson('/bills/bulk-project-readings', [
            'month' => 10,
            'year' => 2026,
            'mru_id' => $mru->id,
            'tuning_enabled' => true,
            'min_units_floor' => 30,
            'max_units_ceiling' => 150,
            'auto_exclude_overposted' => true,
        ]);

        $res->assertOk();
        $res->assertJson([
            'success' => true,
            'projected_count' => 1,
            'skipped_excluded_count' => 2,
        ]);

        $b1->refresh();
        $b2->refresh();
        $b3->refresh();

        // b1 projected: previous 500 + avg
        $this->assertNotNull($b1->working_reading);
        $this->assertGreaterThan(500, (int) $b1->working_reading);

        // b2 & b3 must remain null (not auto-filled!)
        $this->assertNull($b2->working_reading);
        $this->assertNull($b3->working_reading);
    }

    public function test_tuning_excludes_submitted_and_doubt_consumers_in_dashboard_data(): void
    {
        $user = User::factory()->create();
        $mru = Mru::create(['user_id' => $user->id, 'code' => 'MRU_G2', 'name' => 'Guardrail MRU 2']);

        // Account 1: Normal pending account
        $c1 = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990101',
            'consumer_name' => 'Pending Consumer',
            'tariff_category' => 'DS1D',
            'billing_basis' => 'OK',
        ]);
        $b1 = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $c1->ca_number,
            'billing_month' => 10,
            'billing_year' => 2026,
            'previous_reading' => '1000',
            'working_reading' => '1050',
            'units_consumed' => 50,
            'reading_source' => 'auto',
            'review_status' => 'pending',
        ]);

        // Account 2: Submitted account (locked)
        $c2 = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990102',
            'consumer_name' => 'Submitted Consumer',
            'tariff_category' => 'DS1D',
            'billing_basis' => 'OK',
        ]);
        $b2 = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $c2->ca_number,
            'billing_month' => 10,
            'billing_year' => 2026,
            'previous_reading' => '2000',
            'working_reading' => '2045',
            'units_consumed' => 45,
            'reading_source' => 'auto',
            'review_status' => 'submitted',
        ]);

        // Account 3: Doubt account
        $c3 = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990103',
            'consumer_name' => 'Doubt Consumer',
            'tariff_category' => 'DS1D',
            'billing_basis' => 'OK',
        ]);
        $b3 = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $c3->ca_number,
            'billing_month' => 10,
            'billing_year' => 2026,
            'previous_reading' => '3000',
            'working_reading' => '3060',
            'units_consumed' => 60,
            'reading_source' => 'auto',
            'review_status' => 'doubt',
        ]);

        // Apply +50% tuning with exclude_submitted=1 and exclude_critical_doubt=1
        $res = $this->actingAs($user)->getJson('/dashboard/data?month=10&year=2026&tuning_enabled=1&adjustment_percent=50&exclude_submitted=1&exclude_critical_doubt=1');
        $res->assertOk();

        $data = collect($res->json('data'))->keyBy('ca_number');

        $itemPending = $data->get('99990101');
        $itemSubmitted = $data->get('99990102');
        $itemDoubt = $data->get('99990103');

        // Pending account gets tuned (+50% tuning applied: 50 -> 75 kWh)
        $this->assertFalse($itemPending['is_avg_excluded']);
        $this->assertEquals(75, $itemPending['smart_avg_units']);

        // Submitted account is excluded from tuning, preserved at baseline and working reading unmodified
        $this->assertTrue($itemSubmitted['is_avg_excluded']);
        $this->assertStringContainsString('Submitted', (string) $itemSubmitted['avg_exclude_reason']);
        $this->assertEquals('2045', (string) $itemSubmitted['working_reading']);

        // Doubt account is excluded from tuning
        $this->assertTrue($itemDoubt['is_avg_excluded']);
        $this->assertStringContainsString('Critical / Doubt', (string) $itemDoubt['avg_exclude_reason']);
    }
}
