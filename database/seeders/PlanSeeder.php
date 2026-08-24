<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanDuration;
use App\Services\Plan\PlanService;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $planService = app(PlanService::class);

        // 1. Starter Plan
        $starter = Plan::firstOrCreate(
            ['name' => 'Starter'],
            [
                'description' => 'Ideal for individual billing agents and small subdivision coverage.',
                'included_mrus' => 5,
                'included_consumers' => 2500,
                'extra_mru_rate' => 20.00,
                'extra_consumer_rate' => 0.20,
                'grace_period_days' => 3,
                'is_active' => true,
            ]
        );

        $starterDurations = [
            [
                'duration_unit' => 'day',
                'duration_value' => 7,
                'name' => '7 Days Starter Trial',
                'discount_percent' => 0.00,
                'final_price' => 99.00,
                'is_active' => true,
            ],
            [
                'duration_unit' => 'month',
                'duration_value' => 1,
                'name' => '1 Month Standard',
                'discount_percent' => 0.00,
                'final_price' => 499.00,
                'is_active' => true,
            ],
            [
                'duration_unit' => 'month',
                'duration_value' => 2,
                'name' => null,
                'discount_percent' => 5.00,
                'final_price' => 948.00,
                'is_active' => true,
            ],
            [
                'duration_unit' => 'month',
                'duration_value' => 3,
                'name' => null,
                'discount_percent' => 10.00,
                'final_price' => 1347.00,
                'is_active' => true,
            ],
            [
                'duration_unit' => 'month',
                'duration_value' => 6,
                'name' => null,
                'discount_percent' => 15.00,
                'final_price' => 2545.00,
                'is_active' => true,
            ],
            [
                'duration_unit' => 'month',
                'duration_value' => 12,
                'name' => 'Annual Savings',
                'discount_percent' => 20.00,
                'final_price' => 4790.00,
                'is_active' => true,
            ],
        ];

        $planService->syncDurations($starter, $starterDurations, 499.00);

        // 2. Business Pro Plan
        $pro = Plan::firstOrCreate(
            ['name' => 'Business Pro'],
            [
                'description' => 'Designed for high-volume billing teams managing large division quotas.',
                'included_mrus' => 15,
                'included_consumers' => 10000,
                'extra_mru_rate' => 15.00,
                'extra_consumer_rate' => 0.15,
                'grace_period_days' => 5,
                'is_active' => true,
            ]
        );

        $proDurations = [
            [
                'duration_unit' => 'month',
                'duration_value' => 1,
                'name' => 'Monthly Pro',
                'discount_percent' => 0.00,
                'final_price' => 1299.00,
                'is_active' => true,
            ],
            [
                'duration_unit' => 'month',
                'duration_value' => 3,
                'name' => null,
                'discount_percent' => 10.00,
                'final_price' => 3507.00,
                'is_active' => true,
            ],
            [
                'duration_unit' => 'month',
                'duration_value' => 6,
                'name' => null,
                'discount_percent' => 15.00,
                'final_price' => 6625.00,
                'is_active' => true,
            ],
            [
                'duration_unit' => 'month',
                'duration_value' => 12,
                'name' => 'Annual Enterprise',
                'discount_percent' => 20.00,
                'final_price' => 12470.00,
                'is_active' => true,
            ],
        ];

        $planService->syncDurations($pro, $proDurations, 1299.00);
    }
}
