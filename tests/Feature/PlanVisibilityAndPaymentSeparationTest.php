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

    /**
     * Test direct online PG payment success activates subscription via ActivateSubscriptionOnPaymentSuccess listener.
     */
    public function test_direct_pg_payment_activates_subscription_upon_success_event(): void
    {
        $agent = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $agent->assignRole('user');

        $plan = $this->planService->createPlan([
            'name' => 'Online PG Plan',
            'base_price' => 999.00,
            'included_mrus' => 10,
            'included_consumers' => 5000,
            'extra_mru_rate' => 100.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ], [
            ['duration_months' => 1, 'discount_percent' => 0, 'final_price' => 999.00],
        ]);

        $duration = $plan->durations->first();

        // 1. Initiate PG checkout process
        $response = $this->actingAs($agent)->postJson(route('subscription.purchase.process', [
            'plan' => $plan->id,
            'duration' => $duration->id,
        ]), [
            'mode' => 'pg',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $paymentId = $response->json('payment_id');

        $payment = \App\Models\Payment::findOrFail($paymentId);
        $this->assertEquals(PaymentPurpose::DIRECT_SUBSCRIPTION, $payment->purpose);
        $this->assertEquals($plan->id, $payment->meta['plan_id']);
        $this->assertEquals($duration->id, $payment->meta['duration_id']);

        // 2. Simulate gateway success event
        $payment->update([
            'status' => \App\Enums\PaymentStatus::SUCCESS,
            'gateway_payment_id' => 'pay_mock_123',
            'verified_at' => now(),
        ]);
        event(new \App\Events\PaymentSuccessEvent($payment, 'pay_mock_123'));

        // 3. Assert subscription active in database
        $this->assertDatabaseHas('agent_subscriptions', [
            'user_id' => $agent->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'base_price_paid' => 999.00,
        ]);
    }

    /**
     * Test Manual UPI direct subscription activates when admin approves payment.
     */
    public function test_manual_upi_direct_subscription_activates_on_admin_approval(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $admin->assignRole('admin');

        $agent = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $agent->assignRole('user');

        $plan = $this->planService->createPlan([
            'name' => 'UPI Pro Plan',
            'base_price' => 499.00,
            'included_mrus' => 3,
            'included_consumers' => 1500,
            'extra_mru_rate' => 90.00,
            'extra_consumer_rate' => 0.18,
            'is_active' => true,
        ], [
            ['duration_months' => 1, 'discount_percent' => 0, 'final_price' => 499.00],
        ]);

        $duration = $plan->durations->first();

        // 1. Submit manual UPI payment
        $response = $this->actingAs($agent)->post(route('subscription.purchase.process', [
            'plan' => $plan->id,
            'duration' => $duration->id,
        ]), [
            'mode' => 'manual_upi',
            'utr_number' => '423987654321',
        ]);

        $response->assertRedirect(route('payments.index'));

        $payment = \App\Models\Payment::where('utr_number', '423987654321')->firstOrFail();
        $this->assertEquals(\App\Enums\PaymentStatus::PENDING_VERIFICATION, $payment->status);
        $this->assertEquals($plan->id, $payment->meta['plan_id']);

        // 2. Admin approves payment
        $verificationService = app(\App\Services\Payment\PaymentVerificationService::class);
        $verificationService->approve($payment, $admin, 'Verified in bank account');

        // 3. Assert subscription active
        $this->assertDatabaseHas('agent_subscriptions', [
            'user_id' => $agent->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'base_price_paid' => 499.00,
        ]);
    }

    /**
     * Test Bank Transfer direct subscription activates when admin approves payment.
     */
    public function test_bank_transfer_direct_subscription_activates_on_admin_approval(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $admin->assignRole('admin');

        $agent = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $agent->assignRole('user');

        $plan = $this->planService->createPlan([
            'name' => 'Bank Transfer Plan',
            'base_price' => 1299.00,
            'included_mrus' => 15,
            'included_consumers' => 6000,
            'extra_mru_rate' => 110.00,
            'extra_consumer_rate' => 0.22,
            'is_active' => true,
        ], [
            ['duration_months' => 3, 'discount_percent' => 10, 'final_price' => 3507.00],
        ]);

        $duration = $plan->durations->first();

        // 1. Submit bank transfer payment
        $response = $this->actingAs($agent)->post(route('subscription.purchase.process', [
            'plan' => $plan->id,
            'duration' => $duration->id,
        ]), [
            'mode' => 'bank_transfer',
            'bank_reference' => 'NEFT-SBIN-998877',
        ]);

        $response->assertRedirect(route('payments.index'));

        $payment = \App\Models\Payment::where('bank_reference', 'NEFT-SBIN-998877')->firstOrFail();
        $this->assertEquals(\App\Enums\PaymentStatus::PENDING_VERIFICATION, $payment->status);

        // 2. Admin approves payment
        $verificationService = app(\App\Services\Payment\PaymentVerificationService::class);
        $verificationService->approve($payment, $admin, 'Bank credit confirmed');

        // 3. Assert subscription active
        $this->assertDatabaseHas('agent_subscriptions', [
            'user_id' => $agent->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'base_price_paid' => 3507.00,
        ]);
    }

    /**
     * Test suspended agent can access subscription purchase and activate plan without being blocked.
     */
    public function test_suspended_agent_can_renew_and_activate_plan_via_wallet(): void
    {
        $agent = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $agent->assignRole('user');

        $this->walletService->credit($agent, 1000.00, 'test_seed');

        $plan = $this->planService->createPlan([
            'name' => 'Renewal Tier',
            'base_price' => 350.00,
            'included_mrus' => 3,
            'included_consumers' => 1000,
            'extra_mru_rate' => 90.00,
            'extra_consumer_rate' => 0.18,
            'is_active' => true,
        ], [
            ['duration_months' => 1, 'discount_percent' => 0, 'final_price' => 350.00],
        ]);

        $duration = $plan->durations->first();

        // Create expired / suspended subscription
        $sub = AgentSubscription::create([
            'user_id' => $agent->id,
            'plan_id' => $plan->id,
            'status' => 'suspended',
            'billing_start' => now()->subMonths(2),
            'billing_end' => now()->subDays(10),
            'grace_period_end' => now()->subDays(3),
            'duration_months' => 1,
            'base_price_paid' => 350.00,
            'included_mrus_locked' => 3,
            'included_consumers_locked' => 1000,
            'extra_mru_rate_locked' => 90.00,
            'extra_consumer_rate_locked' => 0.18,
        ]);

        // Ensure agent can access subscription page and purchase page
        $pageResp = $this->actingAs($agent)->get(route('subscription.purchase', [
            'plan' => $plan->id,
            'duration' => $duration->id,
        ]));
        $pageResp->assertStatus(200);

        // Ensure agent can POST to subscribe_wallet
        $walletResp = $this->actingAs($agent)->postJson(route('subscription.subscribe_wallet'), [
            'plan_id' => $plan->id,
            'duration_id' => $duration->id,
        ]);

        $walletResp->assertStatus(200);
        $walletResp->assertJson(['success' => true]);

        // Subscription is now active!
        $this->assertDatabaseHas('agent_subscriptions', [
            'user_id' => $agent->id,
            'status' => 'active',
        ]);
    }
}
