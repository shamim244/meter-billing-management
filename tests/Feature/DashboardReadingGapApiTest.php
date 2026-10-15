<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReadingGapApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_data_includes_reading_gap_payload(): void
    {
        $user = User::factory()->create();
        $mru = Mru::create(['user_id' => $user->id, 'code' => 'MRU_GAP_1', 'name' => 'Gap MRU']);
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990004444',
            'last_physical_reading' => '6000',
            'last_inspected_at' => now()->subDays(45),
            'tariff_category' => 'DS1D',
        ]);
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990004444',
            'billing_month' => 4,
            'billing_year' => 2026,
            'previous_reading' => '5000',
            'units_consumed' => 80,
        ]);

        $response = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026');
        $response->assertOk();
        $item = $response->json('data.0');
        $this->assertNotNull($item);
        $this->assertArrayHasKey('reading_gap_info', $item);
        $this->assertEquals('under_posted', $item['reading_gap_info']['gap_status']);
        $this->assertEquals(1000, $item['reading_gap_info']['net_gap']);
    }

    public function test_recording_physical_inspection_updates_consumer_and_logs_audit(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990005555',
            'last_physical_reading' => '5000',
        ]);

        $response = $this->actingAs($user)->postJson('/api/reading-gap/record-inspection', [
            'ca_number' => '99990005555',
            'physical_reading' => '7200',
            'notes' => 'Wall meter checked in field',
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('success'));
        $this->assertDatabaseHas('consumer_accounts', [
            'ca_number' => '99990005555',
            'last_physical_reading' => '7200',
        ]);
        $this->assertDatabaseHas('meter_inspections', [
            'ca_number' => '99990005555',
            'physical_reading' => '7200',
            'notes' => 'Wall meter checked in field',
        ]);
    }

    public function test_update_gap_mode_updates_consumer_recovery_preferences(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990006666',
            'last_physical_reading' => '5000',
        ]);

        $response = $this->actingAs($user)->postJson('/api/reading-gap/update-mode', [
            'ca_number' => '99990006666',
            'gap_recovery_mode' => 'hold_advance',
            'custom_safe_ceiling' => 350,
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('success'));
        $this->assertDatabaseHas('consumer_accounts', [
            'ca_number' => '99990006666',
            'gap_recovery_mode' => 'hold_advance',
            'custom_safe_ceiling' => 350,
        ]);
    }
}
