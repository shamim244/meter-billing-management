<?php

namespace Tests\Unit;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use App\Services\Reading\ReadingGapReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingGapAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReadingGapReconciliationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReadingGapReconciliationService::class);
    }

    public function test_detects_dial_rollover_when_reading_wraps(): void
    {
        // Posted is 99950, Physical on wall is 50 -> net advancement is +100 kWh, NOT -99900!
        $rollover = $this->service->detectMeterRollover(50, 99950, 100000);
        $this->assertTrue($rollover['is_rollover']);
        $this->assertEquals(100, $rollover['adjusted_gap']);

        // Normal reading advancement (no rollover)
        $normal = $this->service->detectMeterRollover(1500, 1200, 100000);
        $this->assertFalse($normal['is_rollover']);
        $this->assertEquals(300, $normal['adjusted_gap']);
    }

    public function test_records_meter_replacement_event(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990009991',
            'meter_no' => 'OLD_MTR_123',
            'last_physical_reading' => '8500',
        ]);

        $inspection = $this->service->recordMeterReplacement(
            $consumer,
            5,
            now(),
            '8500',
            'NEW_MTR_456',
            'Burned display replaced by JE',
            $user
        );

        $consumer->refresh();
        $this->assertEquals('NEW_MTR_456', $consumer->meter_no);
        $this->assertEquals('5', $consumer->last_physical_reading);
        $this->assertStringContainsString('Meter Replacement', $inspection->notes);
        $this->assertStringContainsString('NEW_MTR_456', $inspection->notes);
    }

    public function test_calculates_aggregated_mru_gap_analytics(): void
    {
        $user = User::factory()->create();
        $mru = Mru::create(['user_id' => $user->id, 'code' => 'MRU_ANALYTICS_1', 'name' => 'Analytics MRU']);

        // Consumer 1: Under-posted (+500 gap)
        $c1 = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990008881',
            'last_physical_reading' => '5500',
            'last_inspected_at' => now(),
            'tariff_category' => 'DS1D',
        ]);
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990008881',
            'previous_reading' => '5000',
            'billing_month' => 10,
            'billing_year' => 2026,
            'units_consumed' => 100,
        ]);

        // Consumer 2: Over-posted (-100 gap)
        $c2 = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990008882',
            'last_physical_reading' => '1200',
            'last_inspected_at' => now(),
            'tariff_category' => 'DS1D',
        ]);
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990008882',
            'previous_reading' => '1300',
            'billing_month' => 10,
            'billing_year' => 2026,
            'units_consumed' => 80,
        ]);

        $analytics = $this->service->getMruGapAnalytics($user->id, $mru->id);

        $this->assertArrayHasKey('total_unbilled_kwh', $analytics);
        $this->assertArrayHasKey('estimated_financial_gap_rs', $analytics);
        $this->assertArrayHasKey('over_posted_count', $analytics);
        $this->assertArrayHasKey('under_posted_count', $analytics);
        $this->assertEquals(500, $analytics['total_unbilled_kwh']);
        $this->assertEquals(1, $analytics['under_posted_count']);
        $this->assertEquals(1, $analytics['over_posted_count']);
        $this->assertGreaterThan(0, $analytics['estimated_financial_gap_rs']);
    }
}
