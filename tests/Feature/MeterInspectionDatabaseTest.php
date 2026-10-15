<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\MeterInspection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeterInspectionDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_meter_inspections_table_and_relationships_work(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990001111',
            'consumer_name' => 'Test Consumer',
            'last_physical_reading' => '7000',
            'last_inspected_at' => now(),
            'gap_recovery_mode' => 'auto',
            'custom_safe_ceiling' => 250,
        ]);

        $inspection = MeterInspection::create([
            'user_id' => $user->id,
            'consumer_account_id' => $consumer->id,
            'ca_number' => '99990001111',
            'physical_reading' => '7000',
            'posted_reading_at_inspection' => '5000',
            'gap_at_inspection' => 2000,
            'notes' => 'Site inspection',
            'inspected_at' => now(),
        ]);

        $this->assertDatabaseHas('meter_inspections', ['ca_number' => '99990001111', 'gap_at_inspection' => 2000]);
        $this->assertEquals($consumer->id, $inspection->consumerAccount->id);
        $this->assertTrue($consumer->inspections->contains($inspection));

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '99990001111',
            'billing_month' => 4,
            'billing_year' => 2026,
            'physical_reading' => '7000',
            'reading_gap' => 2000,
            'gap_slice_applied' => 120,
            'gap_action_applied' => 'amortize',
        ]);

        $this->assertDatabaseHas('bill_records', [
            'id' => $bill->id,
            'reading_gap' => 2000,
            'gap_slice_applied' => 120,
            'gap_action_applied' => 'amortize',
        ]);
    }
}
