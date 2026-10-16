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

        $lowHistory = collect([
            (object) ['billing_month' => 9, 'billing_year' => 2026, 'units_consumed' => 15, 'billing_basis' => 'OK'],
            (object) ['billing_month' => 8, 'billing_year' => 2026, 'units_consumed' => 15, 'billing_basis' => 'OK'],
        ]);

        // Default mode (tweaking OFF): base dynamic average (15 kWh) is returned untouched with NO floor clamping
        $dynamicDefault = $service->calculateSmartAverage(
            caNumber: '99990002',
            month: 10,
            year: 2026,
            basis: 'OK',
            currentBill: null,
            consumer: null,
            history: $lowHistory,
            tuningPercent: 0,
            tuningSteps: [],
            meterHistory: collect(),
            guardrails: ['min_units_floor' => 30, 'tuning_enabled' => false]
        );
        $this->assertEquals(15, $dynamicDefault['avg_units'], 'Base calculation must preserve pure dynamic median when tweaking is off');
        $this->assertFalse($dynamicDefault['is_clamped_min']);

        // Tweaking mode (tuning_enabled ON): base 15 is clamped to min 30
        $minClamped = $service->calculateSmartAverage(
            caNumber: '99990002',
            month: 10,
            year: 2026,
            basis: 'OK',
            currentBill: null,
            consumer: null,
            history: $lowHistory,
            tuningPercent: 0,
            tuningSteps: [],
            meterHistory: collect(),
            guardrails: ['min_units_floor' => 30, 'tuning_enabled' => true]
        );
        $this->assertEquals(30, $minClamped['avg_units']);
        $this->assertTrue($minClamped['is_clamped_min']);

        $highHistory = collect([
            (object) ['billing_month' => 9, 'billing_year' => 2026, 'units_consumed' => 220, 'billing_basis' => 'OK'],
            (object) ['billing_month' => 8, 'billing_year' => 2026, 'units_consumed' => 220, 'billing_basis' => 'OK'],
        ]);

        // Default mode (tweaking OFF): high history (220 kWh) is returned untouched with NO ceiling clamping
        $dynamicDefaultHigh = $service->calculateSmartAverage(
            caNumber: '99990003',
            month: 10,
            year: 2026,
            basis: 'OK',
            currentBill: null,
            consumer: null,
            history: $highHistory,
            tuningPercent: 0,
            tuningSteps: [],
            meterHistory: collect(),
            guardrails: ['max_units_ceiling' => 180, 'tuning_enabled' => false]
        );
        $this->assertEquals(220, $dynamicDefaultHigh['avg_units'], 'Base calculation must preserve pure dynamic median when tweaking is off');
        $this->assertFalse($dynamicDefaultHigh['is_clamped_max']);

        // Tweaking mode (tuning_enabled ON): base 220 is clamped to max 180
        $maxClamped = $service->calculateSmartAverage(
            caNumber: '99990003',
            month: 10,
            year: 2026,
            basis: 'OK',
            currentBill: null,
            consumer: null,
            history: $highHistory,
            tuningPercent: 0,
            tuningSteps: [],
            meterHistory: collect(),
            guardrails: ['max_units_ceiling' => 180, 'tuning_enabled' => true]
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
