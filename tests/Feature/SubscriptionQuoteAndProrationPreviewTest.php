<?php

namespace Tests\Feature;

use App\Models\Mru;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\User;
use App\Services\Plan\PlanService;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionQuoteAndProrationPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\NotificationSystemSeeder::class);
    }

    public function test_quote_endpoint_returns_accurate_upgrade_proration_summary(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        app(WalletService::class)->credit($user, 5000.00, 'test_topup');

        // Starter Plan (₹500/mo)
        $starterPlan = Plan::create([
            'name' => 'Starter Plan',
            'included_mrus' => 2,
            'included_consumers' => 2500,
            'base_price' => 500.00,
            'extra_mru_rate' => 20.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ]);
        $starterDuration = $starterPlan->durations()->create([
            'duration_unit' => 'month',
            'duration_value' => 1,
            'final_price' => 500.00,
            'is_active' => true,
        ]);

        // Pro Plan (₹1,500/mo)
        $proPlan = Plan::create([
            'name' => 'Pro Plan',
            'included_mrus' => 10,
            'included_consumers' => 10000,
            'base_price' => 1500.00,
            'extra_mru_rate' => 20.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ]);
        $proDuration = $proPlan->durations()->create([
            'duration_unit' => 'month',
            'duration_value' => 1,
            'final_price' => 1500.00,
            'is_active' => true,
        ]);

        // Subscribe to Starter
        app(PlanService::class)->subscribeAgent($user, $starterPlan, $starterDuration);

        // Fetch quote for upgrading to Pro
        $response = $this->actingAs($user)->getJson("/subscription/quote/{$proPlan->id}/{$proDuration->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'action_type' => 'upgrade',
            'plan' => [
                'name' => 'Pro Plan',
                'included_mrus' => 10,
                'included_consumers' => 10000,
            ],
            'current_subscription' => [
                'plan_name' => 'Starter Plan',
                'included_mrus' => 2,
            ],
        ]);

        $this->assertGreaterThan(0, $response->json('final_amount'));
        $this->assertNotNull($response->json('proration'));
    }

    public function test_quote_endpoint_returns_accurate_downgrade_refund_and_quota_conflict(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        app(WalletService::class)->credit($user, 5000.00, 'test_topup');

        // Pro Plan (3 MRUs, ₹1,500)
        $proPlan = Plan::create([
            'name' => 'Pro Plan',
            'included_mrus' => 3,
            'included_consumers' => 10000,
            'base_price' => 1500.00,
            'extra_mru_rate' => 20.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ]);
        $proDuration = $proPlan->durations()->create([
            'duration_unit' => 'month',
            'duration_value' => 1,
            'final_price' => 1500.00,
            'is_active' => true,
        ]);

        // Starter Plan (1 MRU, ₹500)
        $starterPlan = Plan::create([
            'name' => 'Starter Plan',
            'included_mrus' => 1,
            'included_consumers' => 2500,
            'base_price' => 500.00,
            'extra_mru_rate' => 20.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ]);
        $starterDuration = $starterPlan->durations()->create([
            'duration_unit' => 'month',
            'duration_value' => 1,
            'final_price' => 500.00,
            'is_active' => true,
        ]);

        // Subscribe to Pro
        app(PlanService::class)->subscribeAgent($user, $proPlan, $proDuration);

        // User creates 2 active MRUs (which exceeds Starter's 1 MRU limit)
        Mru::create(['user_id' => $user->id, 'code' => '001', 'name' => 'Zone 1', 'status' => 'active']);
        Mru::create(['user_id' => $user->id, 'code' => '002', 'name' => 'Zone 2', 'status' => 'active']);

        // Fetch quote for downgrading to Starter
        $response = $this->actingAs($user)->getJson("/subscription/quote/{$starterPlan->id}/{$starterDuration->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'action_type' => 'downgrade',
            'final_amount' => 0,
            'downgrade_eligibility' => [
                'eligible' => false,
                'active_mrus_count' => 2,
                'new_plan_quota' => 1,
                'excess_mrus' => 1,
            ],
        ]);

        $this->assertGreaterThan(0, $response->json('prorated_credit'));
    }
}
