<?php

namespace Tests\Feature;

use App\Enums\PaymentMode;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\AgentSubscription;
use App\Models\CouponCode;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\ReferralPayout;
use App\Models\ReferralSignup;
use App\Models\User;
use App\Services\Billing\PlanChangeService;
use App\Services\Payment\PaymentVerificationService;
use App\Services\Referral\ReferralService;
use App\Services\Referral\ReferralSettingsService;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReferralSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $referrer;
    protected User $referee;
    protected ReferralService $referralService;
    protected ReferralSettingsService $settingsService;
    protected WalletService $walletService;
    protected Plan $plan;
    protected PlanDuration $duration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->referralService = app(ReferralService::class);
        $this->settingsService = app(ReferralSettingsService::class);
        $this->walletService = app(WalletService::class);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['email' => 'admin_ref@nbpdcl-saas.com', 'status' => 'active']);
        $this->admin->assignRole($adminRole);

        $this->referrer = User::factory()->create(['name' => 'Referrer Agent', 'email' => 'referrer@example.com', 'status' => 'active']);
        $this->referrer->assignRole($userRole);

        $this->referee = User::factory()->create(['name' => 'Referee Agent', 'email' => 'referee@example.com', 'status' => 'active']);
        $this->referee->assignRole($userRole);

        // Standard test plan
        $this->plan = Plan::create([
            'name' => 'Growth Pro',
            'slug' => 'growth-pro',
            'base_price' => 500.00,
            'included_mrus' => 5,
            'included_consumers' => 500,
            'extra_mru_rate' => 100.00,
            'extra_consumer_rate' => 1.00,
            'is_active' => true,
        ]);

        $this->duration = PlanDuration::create([
            'plan_id' => $this->plan->id,
            'duration_months' => 1,
            'duration_value' => 1,
            'duration_unit' => 'month',
            'discount_percentage' => 0,
            'final_price' => 500.00,
            'is_active' => true,
        ]);

        // Ensure clean default settings
        $this->settingsService->updateSettings([
            'is_enabled' => true,
            'reward_trigger' => 'subscription',
            'reward_kind' => 'percentage',
            'reward_value' => 10.0,
            'minimum_qualifying_amount' => 100.0,
            'hold_period_days' => 7,
        ]);
    }

    /**
     * 1. Self-referral is blocked (same person can't refer themselves).
     */
    public function test_self_referral_is_blocked(): void
    {
        $code = $this->referralService->generateCodeForNewAgent($this->referrer);

        $validation = $this->referralService->validateReferralCode($code->code, $this->referrer->id);

        $this->assertFalse($validation['valid']);
        $this->assertEquals('You cannot use your own referral code.', $validation['message']);

        // Recording signup with own code returns null
        $signup = $this->referralService->recordReferralSignup($code->code, $this->referrer->id);
        $this->assertNull($signup);
    }

    /**
     * 2. A referee below minimum_qualifying_amount does NOT trigger a pending payout.
     */
    public function test_referee_below_minimum_qualifying_amount_does_not_trigger_pending_payout(): void
    {
        $code = $this->referralService->generateCodeForNewAgent($this->referrer);
        $this->referralService->recordReferralSignup($code->code, $this->referee->id);

        // Minimum threshold is ₹100. Payment is ₹50
        $payout = $this->referralService->checkAndCreatePendingPayout(
            user: $this->referee,
            paymentReferenceType: 'subscription_payment',
            paymentReferenceId: 'sub_test_small',
            paymentAmount: 50.00
        );

        $this->assertNull($payout);
        $this->assertDatabaseMissing('referral_payouts', ['referee_user_id' => $this->referee->id]);
    }

    /**
     * 3. Reward_trigger setting correctly determines whether a subscription payment
     *    or a top-up is the one that counts, when both could theoretically apply.
     */
    public function test_reward_trigger_setting_correctly_determines_qualifying_payment_type(): void
    {
        $code = $this->referralService->generateCodeForNewAgent($this->referrer);
        $this->referralService->recordReferralSignup($code->code, $this->referee->id);

        // Trigger is 'subscription'. Attempt a topup of ₹500
        $topupPayout = $this->referralService->checkAndCreatePendingPayout(
            user: $this->referee,
            paymentReferenceType: 'topup',
            paymentReferenceId: 'pay_topup_123',
            paymentAmount: 500.00
        );

        $this->assertNull($topupPayout);

        // Now referee makes subscription payment of ₹500
        $subPayout = $this->referralService->checkAndCreatePendingPayout(
            user: $this->referee,
            paymentReferenceType: 'subscription_payment',
            paymentReferenceId: 'payment_sub_456',
            paymentAmount: 500.00
        );

        $this->assertNotNull($subPayout);
        $this->assertEquals('pending', $subPayout->status);
        $this->assertEquals(50.00, (float)$subPayout->reward_amount); // 10% of 500 = ₹50
        $this->assertEquals($this->referrer->id, $subPayout->referrer_user_id);
    }

    /**
     * 4. Refund/downgrade DURING hold period -> payout cancelled, zero wallet action ever occurred.
     */
    public function test_refund_or_downgrade_during_hold_period_cancels_payout_with_zero_wallet_action(): void
    {
        $code = $this->referralService->generateCodeForNewAgent($this->referrer);
        $this->referralService->recordReferralSignup($code->code, $this->referee->id);

        $payment = Payment::create([
            'user_id' => $this->referee->id,
            'mode' => PaymentMode::PG,
            'purpose' => PaymentPurpose::DIRECT_SUBSCRIPTION,
            'amount' => 500.00,
            'currency' => 'INR',
            'status' => PaymentStatus::SUCCESS,
        ]);

        $payout = $this->referralService->checkAndCreatePendingPayout(
            user: $this->referee,
            paymentReferenceType: 'subscription_payment',
            paymentReferenceId: 'payment_' . $payment->id,
            paymentAmount: 500.00
        );

        $this->assertEquals('pending', $payout->status);
        $initialReferrerBalance = $this->walletService->getBalance($this->referrer);

        // Admin refunds payment during hold period
        $verificationService = app(PaymentVerificationService::class);
        $verificationService->refund($payment, $this->admin, 'Referee requested refund due to duplicate billing');

        $this->assertEquals('cancelled', $payout->fresh()->status);
        $this->assertStringContainsString('Payment #' . $payment->id . ' refunded', $payout->fresh()->clawback_reason);

        // Wallet balance must be untouched
        $this->assertEquals($initialReferrerBalance, $this->walletService->getBalance($this->referrer));
    }

    /**
     * 5. Refund/downgrade AFTER payout already 'paid' -> clawback correctly reverses the wallet credit.
     */
    public function test_refund_or_downgrade_after_payout_paid_correctly_claws_back_wallet_credit(): void
    {
        $code = $this->referralService->generateCodeForNewAgent($this->referrer);
        $this->referralService->recordReferralSignup($code->code, $this->referee->id);

        $payment = Payment::create([
            'user_id' => $this->referee->id,
            'mode' => PaymentMode::PG,
            'purpose' => PaymentPurpose::DIRECT_SUBSCRIPTION,
            'amount' => 1000.00,
            'currency' => 'INR',
            'status' => PaymentStatus::SUCCESS,
        ]);

        $payout = $this->referralService->checkAndCreatePendingPayout(
            user: $this->referee,
            paymentReferenceType: 'subscription_payment',
            paymentReferenceId: 'payment_' . $payment->id,
            paymentAmount: 1000.00
        );

        // Fast-forward hold period: set hold_expires_at to past
        $payout->update(['hold_expires_at' => now()->subMinute()]);

        // Process matured hold period
        $count = $this->referralService->processExpiredHoldPeriods();
        $this->assertEquals(1, $count);
        $this->assertEquals('paid', $payout->fresh()->status);
        $this->assertEquals(100.00, $this->walletService->getBalance($this->referrer)); // 10% of 1000 = 100

        // Now referee's payment is refunded -> Trigger clawback
        $verificationService = app(PaymentVerificationService::class);
        $verificationService->refund($payment, $this->admin, 'Disputed transaction');

        $payout->refresh();
        $this->assertEquals('clawed_back', $payout->status);
        $this->assertEquals(0.00, $this->walletService->getBalance($this->referrer)); // Reversal completed
    }

    /**
     * 6. Regenerating a code does NOT affect an already-in-progress 'pending' payout tied to the old code.
     */
    public function test_regenerating_code_does_not_affect_pending_payout_tied_to_old_code(): void
    {
        $oldCode = $this->referralService->generateCodeForNewAgent($this->referrer);
        $this->referralService->recordReferralSignup($oldCode->code, $this->referee->id);

        $payout = $this->referralService->checkAndCreatePendingPayout(
            user: $this->referee,
            paymentReferenceType: 'subscription_payment',
            paymentReferenceId: 'sub_idem_test',
            paymentAmount: 500.00
        );

        $this->assertEquals('pending', $payout->status);
        $this->assertEquals($oldCode->id, $payout->referral_coupon_code_id);

        // Referrer regenerates code
        $newCode = $this->referralService->regenerateCode($this->referrer);

        $this->assertNotEquals($oldCode->code, $newCode->code);
        $this->assertFalse($oldCode->fresh()->is_active);
        $this->assertTrue($newCode->is_active);

        // Fast-forward hold period and process
        $payout->update(['hold_expires_at' => now()->subMinute()]);
        $processed = $this->referralService->processExpiredHoldPeriods();

        $this->assertEquals(1, $processed);
        $this->assertEquals('paid', $payout->fresh()->status);
        $this->assertEquals(50.00, $this->walletService->getBalance($this->referrer));
    }

    /**
     * 7. Referrer account deleted while a payout is 'pending' -> cancelled with the correct reason, no wallet action attempted.
     */
    public function test_referrer_account_deleted_while_payout_pending_cancels_with_correct_reason(): void
    {
        $code = $this->referralService->generateCodeForNewAgent($this->referrer);
        $this->referralService->recordReferralSignup($code->code, $this->referee->id);

        $payout = $this->referralService->checkAndCreatePendingPayout(
            user: $this->referee,
            paymentReferenceType: 'subscription_payment',
            paymentReferenceId: 'sub_del_test',
            paymentAmount: 500.00
        );

        $this->assertEquals('pending', $payout->status);

        // Referrer account is deleted / purged
        $this->referralService->handleReferrerAccountDeleted($this->referrer->id);

        $payout->refresh();
        $this->assertEquals('cancelled', $payout->status);
        $this->assertEquals('referrer_account_deleted', $payout->clawback_reason);
    }

    /**
     * 8. A referrer with an admin-set override receives THEIR override amount, not the platform default.
     */
    public function test_referrer_with_admin_override_receives_override_amount_not_platform_default(): void
    {
        // Referrer has code with 25% custom override (platform default is 10%)
        $code = $this->referralService->generateCodeForNewAgent($this->referrer);
        $this->referralService->setAdminOverride($this->referrer, 'percentage', 25.0);

        $this->referralService->recordReferralSignup($code->code, $this->referee->id);

        $payout = $this->referralService->checkAndCreatePendingPayout(
            user: $this->referee,
            paymentReferenceType: 'subscription_payment',
            paymentReferenceId: 'sub_custom_override',
            paymentAmount: 1000.00
        );

        $this->assertNotNull($payout);
        $this->assertEquals(250.00, (float)$payout->reward_amount); // 25% of 1000 = ₹250 (not ₹100 default)
    }

    /**
     * 9. Registration auto-generates referral code and links referee via URL.
     */
    public function test_registration_auto_generates_code_and_links_referee(): void
    {
        $referrerCode = $this->referralService->generateCodeForNewAgent($this->referrer);

        $response = $this->post(route('register'), [
            'name' => 'Invited Friend',
            'email' => 'friend@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'referral_code' => $referrerCode->code,
        ]);

        $response->assertRedirect(route('dashboard'));

        $newAgent = User::where('email', 'friend@example.com')->first();
        $this->assertNotNull($newAgent);

        // New agent got their own active referral code
        $ownCode = CouponCode::where('owner_user_id', $newAgent->id)->where('type', 'referral')->first();
        $this->assertNotNull($ownCode);
        $this->assertTrue($ownCode->is_active);

        // Referral signup record was created linking them
        $signup = ReferralSignup::where('referee_user_id', $newAgent->id)->first();
        $this->assertNotNull($signup);
        $this->assertEquals($this->referrer->id, $signup->referrer_user_id);
    }
}
