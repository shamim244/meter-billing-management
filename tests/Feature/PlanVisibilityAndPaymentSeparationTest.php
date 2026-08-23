<?php

namespace Tests\Feature;

use App\Enums\PaymentMode;
use App\Enums\PaymentPurpose;
use App\Models\AgentSubscription;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\User;
use App\Services\Plan\PlanService;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlanVisibilityAndPaymentSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected PlanService $planService;
    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);

        $this->planService = app(PlanService::class);
        $this->walletService = app(WalletService::class);
    }

    /**
     * PART A: Create a plan via PlanService::createPlan() with default form values,
     * assert it appears in whatever query/method the user-facing plan page uses.
     */
    public function test_admin_created_plan_appears_on_user_facing_subscription_page(): void
    {
        $agent = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $agent->assignRole('user');

        // 1. Create a dynamic plan with duration pricing (as admin form does)
        $plan = $this->planService->createPlan([
            'name' => 'Super Utility Pro',
            'description' => 'Designed for high volume subdivision audits.',
            'base_price' => 799.00,
            'included_mrus' => 8,
            'included_consumers' => 4000,
            'extra_mru_rate' => 120.00,
            'extra_consumer_rate' => 0.25,
            'is_active' => true,
        ], [
            ['duration_months' => 1, 'discount_percent' => 0, 'final_price' => 799.00],
            ['duration_months' => 3, 'discount_percent' => 10, 'final_price' => 2157.00],
            ['duration_months' => 12, 'discount_percent' => 20, 'final_price' => 7670.00],
        ]);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'Super Utility Pro',
            'is_active' => true,
        ]);

        // 2. Request user-facing subscription page
        $response = $this->actingAs($agent)->get(route('user-panel.subscription'));

        $response->assertStatus(200);
        $response->assertSee('Super Utility Pro');
        $response->assertSee('Designed for high volume subdivision audits.');
        $response->assertSee('799');
        $response->assertSee('8 MRUs');
        $response->assertSee('4,000');
    }

    /**
     * PART B1 & B4: Confirm /payments/create only ever shows the wallet top-up flow,
     * regardless of query parameters passed to it, and redirects purpose=direct_subscription.
     */
    public function test_payments_create_is_strictly_wallet_topup_and_redirects_direct_subscription_param(): void
    {
        $agent = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $agent->assignRole('user');

        // 1. Legacy request with purpose=direct_subscription must redirect to user-panel.subscription
        $legacyResponse = $this->actingAs($agent)->get('/payments/create?purpose=direct_subscription&amount=499');
        $legacyResponse->assertRedirect(route('user-panel.subscription'));
        $legacyResponse->assertSessionHas('info');

        // 2. Normal visit to /payments/create only presents wallet top-up
        $topupResponse = $this->actingAs($agent)->get(route('payments.create'));
        $topupResponse->assertStatus(200);
        $topupResponse->assertSee('Wallet Top-Up');
        $topupResponse->assertDontSee('Direct Subscription Payment');
        $topupResponse->assertDontSee('Activate or renew a plan tier directly');
    }

    /**
     * PART B2: Subscription Purchase Confirmation page derives amount server-side and has no amount input.
     */
    public function test_subscription_purchase_confirmation_derives_amount_server_side_without_amount_input(): void
    {
        $agent = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $agent->assignRole('user');

        $plan = $this->planService->createPlan([
            'name' => 'Growth Plan',
            'base_price' => 599.00,
            'included_mrus' => 5,
            'included_consumers' => 2000,
            'extra_mru_rate' => 100.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ], [
            ['duration_months' => 3, 'discount_percent' => 10, 'final_price' => 1617.00],
        ]);

        $duration = $plan->durations->first();

        // 1. Visit confirmation page
        $response = $this->actingAs($agent)->get(route('subscription.purchase', [
            'plan' => $plan->id,
            'duration' => $duration->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Growth Plan');
        $response->assertSee('1,617.00');
        $response->assertSee('Fixed Pricing Guarantee');
        // Assert there is no editable amount input field
        $response->assertDontSee('name="amount"', false);
    }

    /**
     * PART B3: "Pay from Wallet" in-place subscription activation debits wallet and activates plan.
     */
    public function test_pay_from_wallet_in_place_activates_subscription_without_redirect_to_payments(): void
    {
        $agent = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $agent->assignRole('user');

        // Seed wallet with ₹2,000
        $this->walletService->credit($agent, 2000.00, 'test_seed');

        $plan = $this->planService->createPlan([
            'name' => 'Starter Plan',
            'base_price' => 299.00,
            'included_mrus' => 2,
            'included_consumers' => 500,
            'extra_mru_rate' => 80.00,
            'extra_consumer_rate' => 0.15,
            'is_active' => true,
        ], [
            ['duration_months' => 1, 'discount_percent' => 0, 'final_price' => 299.00],
        ]);

        $duration = $plan->durations->first();

        // Send AJAX in-place wallet payment request
        $response = $this->actingAs($agent)->postJson(route('subscription.subscribe_wallet'), [
            'plan_id' => $plan->id,
            'duration_id' => $duration->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Assert subscription active in database
        $this->assertDatabaseHas('agent_subscriptions', [
            'user_id' => $agent->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'base_price_paid' => 299.00,
        ]);

        // Assert wallet debited exactly ₹299.00
        $agent->refresh();
        $this->assertEquals(1701.00, (float) $this->walletService->getBalance($agent));
    }
}
