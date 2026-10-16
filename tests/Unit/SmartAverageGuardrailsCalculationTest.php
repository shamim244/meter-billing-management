<?php

namespace Tests\Unit;

use App\Services\SmartAverageCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartAverageGuardrailsCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_avg_override_takes_precedence_over_history_and_tuning(): void
    {
        $service = app(SmartAverageCalculationService::class);
        $result = $service->calculateSmartAverage(
            caNumber: '99990001',
            month: 10,
            year: 2026,
            basis: 'OK',
            currentBill: null,
            consumer: null,
            history: collect(),
            tuningPercent: 20,
            tuningSteps: [20],
            meterHistory: collect(),
            guardrails: ['custom_avg_units' => 65]
        );

        $this->assertEquals(65, $result['avg_units']);
        $this->assertTrue($result['is_custom_override']);
        $this->assertStringContainsString('Custom', $result['label']);
    }

    public function test_minimum_floor_and_maximum_ceiling_clamp(): void
    {
        $service = app(SmartAverageCalculationService::class);

        // Test Min Clamp: base 15 clamped to min 30
        $minClamped = $service->calculateSmartAverage(
            caNumber: '99990002',
            month: 10,
            year: 2026,
            basis: 'OK',
            currentBill: null,
            consumer: null,
            history: collect(),
            tuningPercent: 0,
            tuningSteps: [],
            meterHistory: collect(),
            guardrails: ['min_units_floor' => 30, 'custom_avg_units' => 15]
        );
        $this->assertEquals(30, $minClamped['avg_units']);
        $this->assertTrue($minClamped['is_clamped_min']);

        // Test Max Clamp: base 220 clamped to max 180
        $maxClamped = $service->calculateSmartAverage(
            caNumber: '99990003',
            month: 10,
            year: 2026,
            basis: 'OK',
            currentBill: null,
            consumer: null,
            history: collect(),
            tuningPercent: 0,
            tuningSteps: [],
            meterHistory: collect(),
            guardrails: ['max_units_ceiling' => 180, 'custom_avg_units' => 220]
        );
        $this->assertEquals(180, $maxClamped['avg_units']);
        $this->assertTrue($maxClamped['is_clamped_max']);
    }

    public function test_overposted_and_manual_exclusion(): void
    {
        $service = app(SmartAverageCalculationService::class);
        $excluded = $service->calculateSmartAverage(
            caNumber: '99990004',
            month: 10,
            year: 2026,
            basis: 'OK',
            currentBill: null,
            consumer: null,
            history: collect(),
            tuningPercent: 10,
            tuningSteps: [],
            meterHistory: collect(),
            guardrails: ['is_over_posted' => true, 'auto_exclude_overposted' => true]
        );

        $this->assertTrue($excluded['is_excluded']);
        $this->assertStringContainsString('Over-Posted', (string) $excluded['exclude_reason']);
    }
}
