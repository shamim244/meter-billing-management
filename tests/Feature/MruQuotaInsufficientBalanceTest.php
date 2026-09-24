<?php

namespace Tests\Feature;

use App\Models\AgentSubscription;
use App\Models\Mru;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MruQuotaInsufficientBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(PlanSeeder::class);
    }

    public function test_mru_creation_over_quota_returns_structured_overage_and_insufficient_balance(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $starterPlan = Plan::where('name', 'like', '%Starter%')->first();
        if (! $starterPlan) {
            $starterPlan = Plan::create([
                'name' => 'Starter',
                'included_mrus' => 2,
                'included_consumers' => 1000,
                'extra_mru_rate' => 20.00,
                'extra_consumer_rate' => 0.20,
                'is_active' => true,
            ]);
            $starterPlan->durations()->create([
                'duration_unit' => 'month',
                'duration_value' => 1,
                'duration_months' => 1,
                'final_price' => 199.00,
                'extra_mru_rate' => 20.00,
                'extra_consumer_rate' => 0.20,
                'is_active' => true,
            ]);
        }

        // Subscribe to plan with 2 included MRUs
        AgentSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $starterPlan->id,
            'duration_unit' => 'month',
            'duration_value' => 1,
            'duration_months' => 1,
            'base_price_paid' => 199.00,
            'included_mrus_locked' => 2,
            'included_consumers_locked' => 1000,
            'extra_mru_rate_locked' => 20.00,
            'extra_consumer_rate_locked' => 0.20,
            'billing_start' => now(),
            'billing_end' => now()->addMonth(),
            'status' => 'active',
            'lifecycle_status' => 'active',
        ]);

        // Create 2 MRUs so quota is 2/2 (fully used)
        Mru::create(['user_id' => $user->id, 'code' => '0401', 'name' => 'Village 1', 'status' => 'active', 'is_over_quota' => false]);
        Mru::create(['user_id' => $user->id, 'code' => '0402', 'name' => 'Village 2', 'status' => 'active', 'is_over_quota' => false]);

        // Case 1: User tries to create 3rd MRU without pay_overage flag -> 402 with structured overage & topup_url
        $response = $this->actingAs($user)->postJson('/mrus', [
            'code' => '0403',
            'name' => 'Village 3',
        ]);

        $response->assertStatus(402);
        $response->assertJson([
            'requires_overage' => true,
            'overage_type' => 'mru_creation',
            'amount_due' => 20.00,
            'is_insufficient_balance' => true,
        ]);
        $json = $response->json();
        $this->assertArrayHasKey('wallet_balance', $json);
        $this->assertArrayHasKey('topup_url', $json);
        $this->assertArrayHasKey('upgrade_url', $json);
        $this->assertStringContainsString('wallet', $json['topup_url']);
        $this->assertStringContainsString('subscription', $json['upgrade_url']);

        // Case 2: User tries to confirm & pay ₹20 with ₹0 wallet balance -> 402 with insufficient balance
        $payResponse = $this->actingAs($user)->postJson('/mrus', [
            'code' => '0403',
            'name' => 'Village 3',
            'pay_overage' => 1,
        ]);

        $payResponse->assertStatus(402);
        $payResponse->assertJson([
            'requires_overage' => true,
            'is_insufficient_balance' => true,
        ]);
        $payJson = $payResponse->json();
        $this->assertArrayHasKey('topup_url', $payJson);
        $this->assertArrayHasKey('upgrade_url', $payJson);
        $this->assertStringContainsString('Insufficient wallet balance', $payJson['message']);

        // Assert 3rd MRU was NOT created
        $this->assertDatabaseMissing('mrus', ['code' => '0403']);
    }

    public function test_user_current_plan_name_reflects_active_subscription(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'plan_tier' => 'free',
        ]);

        $proPlan = Plan::create([
            'name' => 'Business Pro',
            'included_mrus' => 15,
            'included_consumers' => 10000,
            'extra_mru_rate' => 15.00,
            'extra_consumer_rate' => 0.15,
            'is_active' => true,
        ]);

        $this->assertEquals('Free', $user->current_plan_name);

        AgentSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $proPlan->id,
            'duration_unit' => 'month',
            'duration_value' => 1,
            'duration_months' => 1,
            'base_price_paid' => 499.00,
            'included_mrus_locked' => 15,
            'included_consumers_locked' => 10000,
            'extra_mru_rate_locked' => 15.00,
            'extra_consumer_rate_locked' => 0.15,
            'billing_start' => now(),
            'billing_end' => now()->addMonth(),
            'status' => 'active',
            'lifecycle_status' => 'active',
        ]);

        $this->assertEquals('Business Pro', $user->fresh()->current_plan_name);
    }
}
