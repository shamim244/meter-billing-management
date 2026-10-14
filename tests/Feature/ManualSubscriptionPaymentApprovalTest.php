<?php

namespace Tests\Feature;

use App\Enums\PaymentMode;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\User;
use App\Services\Payment\PaymentVerificationService;
use App\Services\Plan\PlanService;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManualSubscriptionPaymentApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected PlanService $planService;

    protected WalletService $walletService;

    protected PaymentVerificationService $verificationService;

    protected Plan $freePlan;

    protected PlanDuration $freeDuration;

    protected Plan $proPlan;

    protected PlanDuration $proDuration;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);

        $this->planService = app(PlanService::class);
        $this->walletService = app(WalletService::class);
        $this->verificationService = app(PaymentVerificationService::class);

        // 1. Create Free Tier Plan
        $this->freePlan = $this->planService->createPlan([
            'name' => 'Free Plan',
            'description' => 'Default starter tier',
            'base_price' => 0.00,
            'included_mrus' => 1,
            'included_consumers' => 100,
            'extra_mru_rate' => 100.00,
            'extra_consumer_rate' => 0.50,
            'is_active' => true,
        ], [
            ['duration_months' => 1, 'discount_percent' => 0, 'final_price' => 0.00],
        ]);
        $this->freeDuration = $this->freePlan->durations->first();

        // 2. Create Pro Plan
        $this->proPlan = $this->planService->createPlan([
            'name' => 'Pro Plan',
            'description' => 'For professional agents',
            'base_price' => 499.00,
            'included_mrus' => 5,
            'included_consumers' => 2000,
            'extra_mru_rate' => 80.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ], [
            ['duration_months' => 1, 'discount_percent' => 0, 'final_price' => 499.00],
            ['duration_months' => 3, 'discount_percent' => 10, 'final_price' => 1347.00],
        ]);
        $this->proDuration = $this->proPlan->durations->first();
    }

    /**
     * Scenario 1: User has existing Free Plan with ₹0 wallet balance.
     * User purchases Pro Plan directly via Manual UPI.
     * Admin approves payment -> Pro plan activates cleanly without wallet debit!
     */
    public function test_user_on_free_plan_with_zero_wallet_balance_upgrades_via_manual_upi_on_admin_approval(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $admin->assignRole('admin');

        $agent = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $agent->assignRole('user');

        // Give agent an active Free Plan subscription (standard new user state)
        $freeSub = $this->planService->subscribeAgent($agent, $this->freePlan, $this->freeDuration);
        $this->assertEquals('active', $freeSub->status);
        $this->assertEquals(0.0, (float) $this->walletService->getBalance($agent));

        // Agent submits manual UPI payment for Pro Plan
        $response = $this->actingAs($agent)->post(route('subscription.purchase.process', [
            'plan' => $this->proPlan->id,
            'duration' => $this->proDuration->id,
        ]), [
            'mode' => PaymentMode::MANUAL_UPI->value,
            'action_mode' => 'shift',
            'utr_number' => '423112233445',
        ]);

        $response->assertRedirect(route('payments.index'));

        $payment = Payment::where('utr_number', '423112233445')->firstOrFail();
        $this->assertEquals(PaymentStatus::PENDING_VERIFICATION, $payment->status);
        $this->assertEquals(PaymentPurpose::DIRECT_SUBSCRIPTION, $payment->purpose);
        $this->assertEquals($this->proPlan->id, $payment->meta['plan_id']);
        $this->assertEquals($this->proDuration->id, $payment->meta['duration_id']);

        // Admin approves the payment
        $approvalResponse = $this->actingAs($admin)->post("/admin/payments/{$payment->id}/approve", [
            'notes' => 'Verified in bank passbook.',
        ]);

        $approvalResponse->assertRedirect();
        $approvalResponse->assertSessionHas('success');

        // 1. Payment status must be SUCCESS
        $payment->refresh();
        $this->assertEquals(PaymentStatus::SUCCESS, $payment->status);
        $this->assertEquals($admin->id, $payment->verified_by);

        // 2. Old subscription must be marked UPGRADED
        $freeSub->refresh();
        $this->assertEquals('upgraded', $freeSub->status);

        // 3. New active subscription for Pro Plan must exist
        $agent->refresh();
        $activeSub = $agent->activeSubscription;
        $this->assertNotNull($activeSub);
        $this->assertEquals($this->proPlan->id, $activeSub->plan_id);
        $this->assertEquals('active', $activeSub->status);
        $this->assertEquals(499.00, (float) $activeSub->base_price_paid);
        $this->assertEquals('pro plan', strtolower($agent->plan_tier));

        // 4. Wallet balance must still be 0 (NOT debited into negative, NOT charged twice)
        $this->assertEquals(0.0, (float) $this->walletService->getBalance($agent));

        // 5. Payment Audit Log must record subscription activation
        $auditLog = PaymentAuditLog::where('payment_id', $payment->id)
            ->where('notes', 'like', '[SUBSCRIPTION_ACTIVATED]%')
            ->first();
        $this->assertNotNull($auditLog);
        $this->assertStringContainsString('Switched / Upgraded to plan Pro Plan', $auditLog->notes);
    }

    /**
     * Scenario 2: User on Free Plan with ₹0 wallet balance purchases Pro Plan via Bank Transfer.
     * Admin approves payment -> Pro plan activates cleanly.
     */
    public function test_user_on_free_plan_with_zero_wallet_balance_upgrades_via_bank_transfer_on_admin_approval(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $admin->assignRole('admin');

        $agent = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $agent->assignRole('user');

        $freeSub = $this->planService->subscribeAgent($agent, $this->freePlan, $this->freeDuration);
        $this->assertEquals(0.0, (float) $this->walletService->getBalance($agent));

        // 3-month duration
        $threeMonthDuration = $this->proPlan->durations()->where('duration_months', 3)->firstOrFail();

        $response = $this->actingAs($agent)->post(route('subscription.purchase.process', [
            'plan' => $this->proPlan->id,
            'duration' => $threeMonthDuration->id,
        ]), [
            'mode' => PaymentMode::BANK_TRANSFER->value,
            'action_mode' => 'shift',
            'bank_reference' => 'NEFT-HDFC-991188',
        ]);

        $response->assertRedirect(route('payments.index'));

        $payment = Payment::where('bank_reference', 'NEFT-HDFC-991188')->firstOrFail();
        $this->assertEquals(PaymentStatus::PENDING_VERIFICATION, $payment->status);

        // Admin approves
        $approvalResponse = $this->actingAs($admin)->post("/admin/payments/{$payment->id}/approve", [
            'notes' => 'NEFT confirmed.',
        ]);

        $approvalResponse->assertRedirect();
        $approvalResponse->assertSessionHas('success');

        $payment->refresh();
        $this->assertEquals(PaymentStatus::SUCCESS, $payment->status);

        $agent->refresh();
        $activeSub = $agent->activeSubscription;
        $this->assertNotNull($activeSub);
        $this->assertEquals($this->proPlan->id, $activeSub->plan_id);
        $this->assertEquals('active', $activeSub->status);
        $this->assertEquals(1347.00, (float) $activeSub->base_price_paid);
        $this->assertEquals(0.0, (float) $this->walletService->getBalance($agent));
    }

    /**
     * Scenario 3: User already has Pro Plan, and extends validity by 1 month via Manual UPI.
     * Admin approves payment -> Existing subscription is extended.
     */
    public function test_user_extends_same_plan_via_manual_upi_on_admin_approval(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $admin->assignRole('admin');

        $agent = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $agent->assignRole('user');

        $proSub = $this->planService->subscribeAgent($agent, $this->proPlan, $this->proDuration);
        $originalEnd = $proSub->billing_end->copy();

        // Agent submits renewal/extension
        $response = $this->actingAs($agent)->post(route('subscription.purchase.process', [
            'plan' => $this->proPlan->id,
            'duration' => $this->proDuration->id,
        ]), [
            'mode' => PaymentMode::MANUAL_UPI->value,
            'action_mode' => 'extend',
            'utr_number' => '423998877665',
        ]);

        $response->assertRedirect(route('payments.index'));

        $payment = Payment::where('utr_number', '423998877665')->firstOrFail();

        // Admin approves
        $this->actingAs($admin)->post("/admin/payments/{$payment->id}/approve");

        $proSub->refresh();
        $this->assertEquals('active', $proSub->status);
        $this->assertTrue($proSub->billing_end->isAfter($originalEnd));

        $auditLog = PaymentAuditLog::where('payment_id', $payment->id)
            ->where('notes', 'like', '[SUBSCRIPTION_ACTIVATED]%')
            ->first();
        $this->assertNotNull($auditLog);
        $this->assertStringContainsString('Extended plan Pro Plan', $auditLog->notes);
    }

    /**
     * Scenario 4: Admin rejects manual payment.
     * Subscription remains untouched on previous plan, payment is REJECTED.
     */
    public function test_admin_rejection_does_not_activate_subscription(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $admin->assignRole('admin');

        $agent = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $agent->assignRole('user');

        $freeSub = $this->planService->subscribeAgent($agent, $this->freePlan, $this->freeDuration);

        // Agent submits manual payment
        $this->actingAs($agent)->post(route('subscription.purchase.process', [
            'plan' => $this->proPlan->id,
            'duration' => $this->proDuration->id,
        ]), [
            'mode' => PaymentMode::MANUAL_UPI->value,
            'utr_number' => '423000000001',
        ]);

        $payment = Payment::where('utr_number', '423000000001')->firstOrFail();

        // Admin rejects payment
        $rejectResponse = $this->actingAs($admin)->post("/admin/payments/{$payment->id}/reject", [
            'rejection_reason' => 'Invalid UTR reference number not found in statement.',
        ]);

        $rejectResponse->assertRedirect();
        $rejectResponse->assertSessionHas('success');

        $payment->refresh();
        $this->assertEquals(PaymentStatus::REJECTED, $payment->status);
        $this->assertEquals('Invalid UTR reference number not found in statement.', $payment->rejection_reason);

        // Subscription must STILL be Free Plan
        $agent->refresh();
        $this->assertEquals($this->freePlan->id, $agent->activeSubscription->plan_id);
        $this->assertEquals('active', $agent->activeSubscription->status);
    }

    /**
     * Scenario 5: User tops up wallet with auto_activate flag and plan details.
     * Admin approves payment -> Wallet is credited, then plan amount is debited and plan is activated!
     */
    public function test_wallet_topup_with_auto_activate_flag_activates_subscription_on_admin_approval(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $admin->assignRole('admin');

        $agent = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $agent->assignRole('user');

        $freeSub = $this->planService->subscribeAgent($agent, $this->freePlan, $this->freeDuration);

        // Submit wallet topup with auto_activate
        $response = $this->actingAs($agent)->post(route('payments.store'), [
            'mode' => PaymentMode::MANUAL_UPI->value,
            'purpose' => PaymentPurpose::WALLET_TOPUP->value,
            'amount' => 499.00,
            'plan_id' => $this->proPlan->id,
            'duration_id' => $this->proDuration->id,
            'auto_activate' => '1',
            'utr_number' => '425000000005',
        ]);

        $response->assertRedirect(route('payments.index'));

        $payment = Payment::where('utr_number', '425000000005')->firstOrFail();
        $this->assertEquals(PaymentStatus::PENDING_VERIFICATION, $payment->status);
        $this->assertEquals(1, $payment->meta['auto_activate'] ?? null);
        $this->assertEquals($this->proPlan->id, $payment->meta['plan_id'] ?? null);

        // Admin approves payment
        $approveResponse = $this->actingAs($admin)->post("/admin/payments/{$payment->id}/approve", [
            'notes' => 'Topup verified and approved',
        ]);

        $approveResponse->assertRedirect();
        $payment->refresh();
        $this->assertEquals(PaymentStatus::SUCCESS, $payment->status);

        // Verify agent's subscription is now upgraded to Pro Plan
        $agent->refresh();
        $activeSub = $agent->activeSubscription;
        $this->assertNotNull($activeSub);
        $this->assertEquals($this->proPlan->id, $activeSub->plan_id);
        $this->assertEquals('active', $activeSub->status);
    }
}
