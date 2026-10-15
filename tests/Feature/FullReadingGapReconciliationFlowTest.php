<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FullReadingGapReconciliationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_reading_gap_reconciliation_lifecycle(): void
    {
        $user = User::factory()->create();
        $mru = Mru::create(['user_id' => $user->id, 'code' => 'MRU_GAP_E2E', 'name' => 'E2E Gap MRU']);

        // 1. Create consumer with initial baseline reading
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990009999',
            'tariff_category' => 'DS1D',
            'last_physical_reading' => null,
            'last_inspected_at' => null,
        ]);

        $billMonth1 = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990009999',
            'billing_month' => 1,
            'billing_year' => 2026,
            'previous_reading' => '5000',
            'units_consumed' => 100,
        ]);

        // 2. Initial state: uninspected
        $response1 = $this->actingAs($user)->getJson('/dashboard/data?month=1&year=2026');
        $response1->assertOk();
        $data1 = $response1->json('data.0.reading_gap_info');
        $this->assertEquals('uninspected', $data1['gap_status']);

        // 3. Field visit occurs: Meter reader records physical reading of 7000 kWh (gap: +2000)
        $inspectResponse = $this->actingAs($user)->postJson('/api/reading-gap/record-inspection', [
            'ca_number' => '99990009999',
            'physical_reading' => '7000',
            'notes' => 'Field inspection after 4 months',
        ]);
        $inspectResponse->assertOk();
        $this->assertTrue($inspectResponse->json('success'));

        // 4. Verify updated dashboard data recommendation
        $response2 = $this->actingAs($user)->getJson('/dashboard/data?month=1&year=2026');
        $response2->assertOk();
        $data2 = $response2->json('data.0.reading_gap_info');
        $this->assertEquals('under_posted', $data2['gap_status']);
        $this->assertEquals(2000, $data2['net_gap']);
        $this->assertEquals(220, $data2['safe_ceiling']);
        $this->assertEquals(120, $data2['gap_slice_applied']);
        $this->assertEquals('5220', $data2['recommended_working_reading']);

        // 5. Admin updates safe ceiling via settings to 300 kWh
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('admin.bills.engine-settings.update'), [
            'gap_safe_ceiling_domestic' => 300,
        ]);
        $this->assertEquals(300, (int) SystemSetting::get('gap_safe_ceiling_domestic'));

        // 6. Verify dashboard immediately recalculates slice with new 300 safe ceiling
        $response3 = $this->actingAs($user)->getJson('/dashboard/data?month=1&year=2026');
        $response3->assertOk();
        $data3 = $response3->json('data.0.reading_gap_info');
        $this->assertEquals(300, $data3['safe_ceiling']);
        // Slice is min(2000, 300 - 100) = 200 units -> billed units = 300 -> reading 5300
        $this->assertEquals(200, $data3['gap_slice_applied']);
        $this->assertEquals('5300', $data3['recommended_working_reading']);

        // 7. Case 1 Over-Posted verification: Physical 1200, Posted 1300
        $consumer->update([
            'last_physical_reading' => '1200',
            'last_inspected_at' => now(),
        ]);
        $billMonth1->update([
            'previous_reading' => '1300',
            'current_reading' => '1300',
        ]);

        $response4 = $this->actingAs($user)->getJson('/dashboard/data?month=1&year=2026');
        $response4->assertOk();
        $data4 = $response4->json('data.0.reading_gap_info');
        $this->assertEquals('over_posted', $data4['gap_status']);
        $this->assertEquals(-100, $data4['net_gap']);
        $this->assertEquals('1300', $data4['recommended_working_reading']);
        $this->assertEquals(1, $data4['estimated_catchup_cycles']); // 100 units gap / 100 avg = 1 cycle
    }
}
