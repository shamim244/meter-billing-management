<?php

namespace Tests\Unit;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\User;
use App\Services\Reading\ReadingGapReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingGapReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReadingGapReconciliationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReadingGapReconciliationService::class);
    }

    public function test_over_posted_bill_recommends_hold_mode_and_catchup_timeline(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990002222',
            'last_physical_reading' => '1200',
            'last_inspected_at' => now(),
            'tariff_category' => 'DS1D',
        ]);

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '99990002222',
            'billing_month' => 4,
            'billing_year' => 2026,
            'current_reading' => '1300',
            'previous_reading' => '1300',
        ]);

        $recommendation = $this->service->calculateRecommendation($bill, $consumer, smartAvg: 50);

        $this->assertEquals('over_posted', $recommendation['gap_status']);
        $this->assertEquals(-100, $recommendation['net_gap']);
        $this->assertEquals('1300', $recommendation['recommended_working_reading']);
        $this->assertEquals(2, $recommendation['estimated_catchup_cycles']);
    }

    public function test_over_posted_bill_with_advance_one_mode(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990002223',
            'last_physical_reading' => '1200',
            'last_inspected_at' => now(),
            'tariff_category' => 'DS1D',
            'gap_recovery_mode' => 'hold_advance',
        ]);

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '99990002223',
            'billing_month' => 4,
            'billing_year' => 2026,
            'previous_reading' => '1300',
        ]);

        $recommendation = $this->service->calculateRecommendation($bill, $consumer, smartAvg: 50);

        $this->assertEquals('over_posted', $recommendation['gap_status']);
        $this->assertEquals(-100, $recommendation['net_gap']);
        $this->assertEquals('1301', $recommendation['recommended_working_reading']);
        $this->assertEquals(1, $recommendation['billed_units']);
    }

    public function test_under_posted_bill_applies_safe_monthly_ceiling_slice(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990003333',
            'last_physical_reading' => '7000',
            'last_inspected_at' => now(),
            'tariff_category' => 'DS1D',
        ]);

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '99990003333',
            'billing_month' => 4,
            'billing_year' => 2026,
            'previous_reading' => '5000',
        ]);

        // Monthly average is 100 kWh, safe ceiling is 220 kWh -> gap slice should be 120 kWh -> total units 220 kWh -> reading 5220
        $recommendation = $this->service->calculateRecommendation($bill, $consumer, smartAvg: 100);

        $this->assertEquals('under_posted', $recommendation['gap_status']);
        $this->assertEquals(2000, $recommendation['net_gap']);
        $this->assertEquals(220, $recommendation['billed_units']);
        $this->assertEquals('5220', $recommendation['recommended_working_reading']);
        $this->assertEquals(120, $recommendation['gap_slice_applied']);
    }

    public function test_under_posted_respects_custom_safe_ceiling(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990003334',
            'last_physical_reading' => '5500',
            'last_inspected_at' => now(),
            'tariff_category' => 'DS1D',
            'custom_safe_ceiling' => 300,
        ]);

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '99990003334',
            'billing_month' => 4,
            'billing_year' => 2026,
            'previous_reading' => '5000',
        ]);

        // Smart avg = 80, custom safe ceiling = 300 -> slice = min(500, 300 - 80) = 220 -> billed units = 300
        $recommendation = $this->service->calculateRecommendation($bill, $consumer, smartAvg: 80);

        $this->assertEquals('under_posted', $recommendation['gap_status']);
        $this->assertEquals(500, $recommendation['net_gap']);
        $this->assertEquals(300, $recommendation['billed_units']);
        $this->assertEquals('5300', $recommendation['recommended_working_reading']);
        $this->assertEquals(220, $recommendation['gap_slice_applied']);
    }

    public function test_record_physical_inspection_persists_audit_and_updates_consumer(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990003335',
            'last_physical_reading' => '4000',
        ]);

        $inspection = $this->service->recordPhysicalInspection(
            $consumer,
            '4500',
            now()->subHours(2),
            'Physical check on meter',
            $user
        );

        $this->assertEquals('4500', $inspection->physical_reading);
        $this->assertEquals('Physical check on meter', $inspection->notes);
        $this->assertDatabaseHas('consumer_accounts', [
            'ca_number' => '99990003335',
            'last_physical_reading' => '4500',
        ]);
        $this->assertDatabaseHas('meter_inspections', [
            'ca_number' => '99990003335',
            'physical_reading' => '4500',
        ]);
    }
}
