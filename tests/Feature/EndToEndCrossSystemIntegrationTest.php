<?php

namespace Tests\Feature;

use App\Enums\PaymentMode;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\AgentSubscription;
use App\Models\BillingBasisHistory;
use App\Models\BillingCycle;
use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\PlanOverageCharge;
use App\Models\User;
use App\Services\Billing\PlanChangeService;
use App\Services\Billing\SubscriptionLifecycleService;
use App\Services\BillingBasisTrackingService;
use App\Services\Notifications\NotificationDispatchService;
use App\Services\Notifications\NotificationTemplateService;
use App\Services\Payment\OnlinePaymentGatewayService;
use App\Services\Plan\ConsumerQuotaService;
use App\Services\Plan\MruQuotaService;
use App\Services\Plan\PlanService;
use App\Services\Plan\RenewalService;
use App\Services\QuotaUsageReportService;
use App\Services\StatusTagReportService;
use App\Services\UsageSummaryService;
use App\Services\Wallet\WalletService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EndToEndCrossSystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected WalletService $walletService;
    protected PlanService $planService;
    protected MruQuotaService $mruQuotaService;
    protected ConsumerQuotaService $consumerQuotaService;
    protected SubscriptionLifecycleService $lifecycleService;
    protected PlanChangeService $planChangeService;
    protected RenewalService $renewalService;
    protected StatusTagReportService $statusTagService;
    protected QuotaUsageReportService $quotaReportService;
    protected UsageSummaryService $usageSummaryService;
    protected BillingBasisTrackingService $billingBasisService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        app(NotificationTemplateService::class)->resetToDefaults();

        $this->walletService = app(WalletService::class);
        $this->planService = app(PlanService::class);
        $this->mruQuotaService = app(MruQuotaService::class);
        $this->consumerQuotaService = app(ConsumerQuotaService::class);
        $this->lifecycleService = app(SubscriptionLifecycleService::class);
        $this->planChangeService = app(PlanChangeService::class);
        $this->renewalService = app(RenewalService::class);
        $this->statusTagService = app(StatusTagReportService::class);
        $this->quotaReportService = app(QuotaUsageReportService::class);
        $this->usageSummaryService = app(UsageSummaryService::class);
        $this->billingBasisService = app(BillingBasisTrackingService::class);
    }

    /**
     * Helper to create standard test plans with duration pricing.
     */
    protected function createStandardPlans(): array
    {
        // 1. Starter Plan: 2 MRUs, 100 Consumers, ₹299/mo, Extra MRU: ₹100, Extra CA: ₹2.00
        $starter = Plan::create([
            'name' => 'Starter Plan',
            'description' => 'For small operators',
            'included_mrus' => 2,
            'included_consumers' => 100,
            'extra_mru_rate' => 100.00,
            'extra_consumer_rate' => 2.00,
            'grace_period_days' => 3,
            'is_active' => true,
        ]);
        $starterDuration = PlanDuration::create([
            'plan_id' => $starter->id,
            'duration_months' => 1,
            'discount_percent' => 0,
            'final_price' => 299.00,
            'extra_mru_rate' => 100.00,
            'extra_consumer_rate' => 2.00,
        ]);

        // 2. Growth Plan: 5 MRUs, 500 Consumers, ₹599/mo, Extra MRU: ₹80, Extra CA: ₹1.50
        $growth = Plan::create([
            'name' => 'Growth Plan',
            'description' => 'For growing operators',
            'included_mrus' => 5,
            'included_consumers' => 500,
            'extra_mru_rate' => 80.00,
            'extra_consumer_rate' => 1.50,
            'grace_period_days' => 3,
            'is_active' => true,
        ]);
        $growthDuration = PlanDuration::create([
            'plan_id' => $growth->id,
            'duration_months' => 1,
            'discount_percent' => 0,
            'final_price' => 599.00,
            'extra_mru_rate' => 80.00,
            'extra_consumer_rate' => 1.50,
        ]);

        return compact('starter', 'starterDuration', 'growth', 'growthDuration');
    }

    /**
     * JOURNEY 1: New Agent Signup to First Successful Cycle
     */
    public function test_journey_1_new_agent_signup_to_first_successful_cycle(): void
    {
        $plans = $this->createStandardPlans();

        // 1. Agent registers via standard controller
        $registerResponse = $this->post('/register', [
            'name' => 'Agent Ramesh',
            'email' => 'ramesh@example.com',
            'phone' => '9876543210',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
        $registerResponse->assertRedirect('/dashboard');

        $user = User::where('email', 'ramesh@example.com')->firstOrFail();
        $user->markEmailAsVerified();
        $this->assertTrue($user->hasRole('user'));

        // Confirm auth.welcome notification fired
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'event_type' => 'auth.welcome',
        ]);
        $welcomeNotification = Notification::where('user_id', $user->id)->where('event_type', 'auth.welcome')->first();
        $this->assertStringContainsString('Agent Ramesh', $welcomeNotification->body);
        $this->assertStringContainsString('ramesh@example.com', $welcomeNotification->body);

        // Confirm wallet balance safely evaluates to 0.00
        $this->assertEquals(0.00, (float) $this->walletService->getBalance($user));

        // 2. Top up wallet via simulated payment gateway (₹2,000.00)
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

        $payment = Payment::create([
            'user_id' => $user->id,
            'mode' => PaymentMode::PG,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => 2000.00,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING_VERIFICATION,
            'gateway_order_id' => 'order_123',
            'gateway_payment_id' => 'pay_123',
        ]);
        app(\App\Services\Payment\PaymentVerificationService::class)->approve($payment, $admin);

        // Confirm wallet credited and notification fired
        $user->refresh();
        $this->assertEquals(2000.00, (float) $this->walletService->getBalance($user));
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'event_type' => 'payment.manual_approved',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'event_type' => 'wallet.credited',
        ]);

        // 3. Agent subscribes to Starter Plan (₹299.00)
        $subscription = $this->planService->subscribeAgent($user, $plans['starter'], $plans['starterDuration']);
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals(2, $subscription->included_mrus_locked);
        $this->assertEquals(100, $subscription->included_consumers_locked);

        // Confirm agent.subscribed notification fired with real plan data
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'event_type' => 'agent.subscribed',
        ]);
        $subNotification = Notification::where('user_id', $user->id)->where('event_type', 'agent.subscribed')->first();
        $this->assertStringContainsString('Starter Plan', $subNotification->body);
        $this->assertStringContainsString('Included MRUs: 2', $subNotification->body);

        // 4. Create 1st MRU (Slot 1 of 2 included)
        $mru1Response = $this->actingAs($user)->post('/mrus', [
            'code' => '0477',
            'name' => 'Gerua Section',
        ]);
        $mru1Response->assertRedirect();
        $mru1 = Mru::where('user_id', $user->id)->where('code', '0477')->firstOrFail();
        $this->assertEquals('active', $mru1->status);
        $this->assertFalse((bool) $mru1->is_over_quota);

        // Create 2nd MRU (Slot 2 of 2 included)
        $mru2Response = $this->actingAs($user)->post('/mrus', [
            'code' => '0478',
            'name' => 'Belwa Section',
        ]);
        $mru2 = Mru::where('user_id', $user->id)->where('code', '0478')->firstOrFail();
        $this->assertEquals('active', $mru2->status);
        $this->assertFalse((bool) $mru2->is_over_quota);

        // 5. Create 3rd MRU (Exceeds included quota 2 -> triggers pay-gate)
        $mru3PreCheck = $this->actingAs($user)->postJson('/mrus', [
            'code' => '0479',
            'name' => 'Extra MRU Section',
            'pay_overage' => false,
        ]);
        $mru3PreCheck->assertStatus(402);
        $mru3PreCheck->assertJson(['requires_overage' => true, 'amount_due' => 100.00]);

        // Confirm creation with pay_overage = true debits wallet ₹100.00
        $balanceBeforeMru = (float) $this->walletService->getBalance($user);
        $mru3Success = $this->actingAs($user)->postJson('/mrus', [
            'code' => '0479',
            'name' => 'Extra MRU Section',
            'pay_overage' => true,
        ]);
        $mru3Success->assertStatus(201);
        $mru3 = Mru::where('user_id', $user->id)->where('code', '0479')->firstOrFail();
        $this->assertTrue((bool) $mru3->is_over_quota);
        $this->assertEquals($balanceBeforeMru - 100.00, (float) $this->walletService->getBalance($user));

        // Confirm mru.overage_charged notification fired
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'event_type' => 'mru.overage_charged',
        ]);

        // 6. Link 120 Consumers to MRU 1 (Plan includes 100 consumers)
        for ($i = 1; $i <= 120; $i++) {
            ConsumerAccount::create([
                'user_id' => $user->id,
                'mru_id' => $mru1->id,
                'ca_number' => '102300' . str_pad((string)$i, 6, '0', STR_PAD_LEFT),
                'consumer_name' => "Consumer {$i}",
            ]);
        }
        $this->assertEquals(120, ConsumerAccount::where('user_id', $user->id)->where('mru_id', $mru1->id)->count());

        // 7. Create billing cycle for MRU 1 (Month 8, Year 2026) -> 120 consumers vs 100 included = 20 extra @ ₹2.00 = ₹40.00
        $cyclePreCheck = $this->actingAs($user)->postJson('/processing/create-cycle', [
            'mru_id' => $mru1->id,
            'month' => 8,
            'year' => 2026,
            'pay_overage' => false,
        ]);
        $cyclePreCheck->assertStatus(402);
        $cyclePreCheck->assertJson(['requires_overage' => true, 'extra_count' => 20, 'amount_due' => 40.00]);

        // Confirm cycle creation with pay_overage = true debits wallet ₹40.00
        $balanceBeforeCycle = (float) $this->walletService->getBalance($user);
        $cycleSuccess = $this->actingAs($user)->postJson('/processing/create-cycle', [
            'mru_id' => $mru1->id,
            'month' => 8,
            'year' => 2026,
            'pay_overage' => true,
        ]);
        $cycleSuccess->assertStatus(201);
        $this->assertEquals($balanceBeforeCycle - 40.00, (float) $this->walletService->getBalance($user));

        // Confirm BillingCycle record created with exact audit numbers
        $this->assertDatabaseHas('billing_cycles', [
            'user_id' => $user->id,
            'mru_id' => $mru1->id,
            'cycle_month' => 8,
            'cycle_year' => 2026,
            'consumer_count_at_creation' => 120,
            'included_quota_used' => 100,
            'extra_consumer_count' => 20,
            'extra_consumer_charge' => 40.00,
        ]);

        // Confirm consumer.overage_charged notification fired
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'event_type' => 'consumer.overage_charged',
        ]);
        $caNotification = Notification::where('user_id', $user->id)->where('event_type', 'consumer.overage_charged')->first();
        $this->assertStringContainsString('20 extra consumers', $caNotification->body);
        $this->assertStringContainsString('40.00', $caNotification->body);

        // 8. PDF Processing Hook — record billing basis (LK for 2 consecutive cycles)
        $cycle = BillingCycle::where('user_id', $user->id)->where('mru_id', $mru1->id)->firstOrFail();
        $sampleCa = '102300000001';

        // Cycle 7 (July): LK reading
        $this->billingBasisService->recordBillingBasis(
            userId: $user->id,
            caNumber: $sampleCa,
            mruId: $mru1->id,
            month: 7,
            year: 2026,
            basis: 'LK'
        );

        // Cycle 8 (August): LK reading -> triggers consecutive_alert
        $history = $this->billingBasisService->recordBillingBasis(
            userId: $user->id,
            caNumber: $sampleCa,
            mruId: $mru1->id,
            month: 8,
            year: 2026,
            basis: 'LK',
            billingCycleId: $cycle->id
        );

        $this->assertTrue((bool) $history->is_consecutive_alert);
        $this->assertEquals(2, $history->consecutive_count);

        $flagged = $this->billingBasisService->getFlaggedConsumers($user->id);
        $this->assertCount(1, $flagged);
        $this->assertEquals($sampleCa, $flagged->first()->ca_number);
    }

    /**
     * JOURNEY 2: Renewal, Grace Period, Suspension, and Reactivation
     */
    public function test_journey_2_renewal_grace_period_suspension_and_reactivation(): void
    {
        $plans = $this->createStandardPlans();
        $user = User::factory()->create(['status' => 'active']);
        $this->walletService->credit($user, 1000.00, 'test_seed', 'test', '1');

        $subscription = $this->planService->subscribeAgent($user, $plans['starter'], $plans['starterDuration']);

        // 1. Manually set billing_end to yesterday and run daily lifecycle processor
        $subscription->update([
            'billing_end' => Carbon::now()->subDay(),
            'lifecycle_status' => 'active',
        ]);

        $this->lifecycleService->runDailyLifecycleProcessor();
        $subscription->refresh();

        // Moves to renewal_due and calculates grace_period_ends_at (+3 days)
        $this->assertEquals('renewal_due', $subscription->lifecycle_status);
        $this->assertNotNull($subscription->grace_period_ends_at);

        // Confirm subscription.renewal_due notification fired
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'event_type' => 'subscription.renewal_due',
        ]);

        // 2. Advance past grace_period_ends_at and run daily processor
        $subscription->update([
            'lifecycle_status' => 'grace_period',
            'grace_period_ends_at' => Carbon::now()->subHour(),
        ]);

        $this->lifecycleService->runDailyLifecycleProcessor();
        $subscription->refresh();

        // Moves to suspended (read-only mode)
        $this->assertEquals('suspended', $subscription->lifecycle_status);
        $this->assertNotNull($subscription->suspended_at);

        // Confirm subscription.suspended notification fired as CRITICAL
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'event_type' => 'subscription.suspended',
            'priority' => 'critical',
        ]);

        // 3. Confirm EnsureSubscriptionNotSuspended middleware BLOCKS write actions
        $blockedMru = $this->actingAs($user)->postJson('/mrus', [
            'code' => '9999',
            'name' => 'Blocked While Suspended',
        ]);
        $blockedMru->assertStatus(403);
        $blockedMru->assertJson(['error' => 'subscription_suspended', 'is_suspended' => true]);

        // 4. Manually trigger renewal with wallet payment
        $renewalResult = $this->renewalService->processRenewal($user, false);
        $this->assertTrue($renewalResult['success']);

        $subscription->refresh();
        $this->assertEquals('active', $subscription->lifecycle_status);
        $this->assertNull($subscription->suspended_at);

        // Confirm subscription.reactivated notification fired
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'event_type' => 'subscription.reactivated',
        ]);
    }

    /**
     * JOURNEY 3: Mid-Cycle Plan Change (Upgrade & Downgrade)
     */
    public function test_journey_3_mid_cycle_plan_upgrade_and_downgrade(): void
    {
        $plans = $this->createStandardPlans();
        $user = User::factory()->create(['status' => 'active']);
        $this->walletService->credit($user, 2000.00, 'test_seed', 'test', '1');

        // Agent on Starter Plan (2 MRUs included)
        $subscription = $this->planService->subscribeAgent($user, $plans['starter'], $plans['starterDuration']);

        // Create 2 included MRUs + 1 over-quota MRU (locked)
        $mru1 = Mru::create(['user_id' => $user->id, 'code' => '0401', 'name' => 'MRU 1', 'status' => 'active', 'is_over_quota' => false]);
        $mru2 = Mru::create(['user_id' => $user->id, 'code' => '0402', 'name' => 'MRU 2', 'status' => 'active', 'is_over_quota' => false]);
        $mru3 = Mru::create(['user_id' => $user->id, 'code' => '0403', 'name' => 'MRU 3', 'status' => 'locked', 'is_over_quota' => true, 'locked_reason' => 'over_quota_unpaid']);

        // Set cycle to exactly 15 of 30 days used (50% remaining)
        $subscription->update([
            'billing_start' => Carbon::now()->subDays(15),
            'billing_end' => Carbon::now()->addDays(15),
        ]);

        // 1. Upgrade from Starter (₹299) to Growth (₹599)
        // Proration: Old credit = 299 * (15/30) = 149.50. New cost = 599 * (15/30) = 299.50. Due = 150.00
        $balanceBeforeUpgrade = (float) $this->walletService->getBalance($user);
        $upgradeResult = $this->planChangeService->upgradePlan($subscription, $plans['growth'], $plans['growthDuration']);

        $this->assertTrue($upgradeResult['success']);
        $this->assertEquals(150.00, (float) $upgradeResult['amount_charged']);
        $this->assertEquals($balanceBeforeUpgrade - 150.00, (float) $this->walletService->getBalance($user));

        $subscription->refresh();
        $this->assertEquals(5, $subscription->included_mrus_locked);
        $this->assertEquals(500, $subscription->included_consumers_locked);

        // Confirm previously locked MRU 3 auto-unlocked because Growth includes 5 MRUs
        $mru3->refresh();
        $this->assertEquals('active', $mru3->status);
        $this->assertFalse((bool) $mru3->is_over_quota);

        // Confirm subscription.upgraded notification fired
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'event_type' => 'subscription.upgraded',
        ]);
        $upgradeNotification = Notification::where('user_id', $user->id)->where('event_type', 'subscription.upgraded')->first();
        $this->assertStringContainsString('Growth Plan', $upgradeNotification->body);
        $this->assertStringContainsString('150.00', $upgradeNotification->body);

        // 2. Downgrade from Growth (5 MRUs) back to Starter (2 MRUs)
        // User currently has 3 active MRUs -> eligibility check MUST block downgrade
        $eligibility = $this->planChangeService->checkDowngradeEligibility($subscription, $plans['starter']);
        $this->assertFalse($eligibility['eligible']);
        $this->assertEquals(3, $eligibility['active_mrus_count']);
        $this->assertEquals(2, $eligibility['new_plan_quota']);

        // Lock MRU 3 so active count = 2 <= 2
        $mru3->update(['status' => 'locked']);

        $eligibilityAfterLock = $this->planChangeService->checkDowngradeEligibility($subscription, $plans['starter']);
        $this->assertTrue($eligibilityAfterLock['eligible']);

        // Execute downgrade: Proration credit = 150.00 added to wallet
        $balanceBeforeDowngrade = (float) $this->walletService->getBalance($user);
        $downgradeResult = $this->planChangeService->downgradePlan($subscription, $plans['starter'], $plans['starterDuration']);

        $this->assertTrue($downgradeResult['success']);
        $this->assertEquals(150.00, (float) $downgradeResult['amount_credited']);
        $this->assertEquals($balanceBeforeDowngrade + 150.00, (float) $this->walletService->getBalance($user));

        // Confirm subscription.downgraded notification fired
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'event_type' => 'subscription.downgraded',
        ]);
    }

    /**
     * JOURNEY 4: Notification Template Placeholder Integrity Check
     */
    public function test_journey_4_notification_placeholders_render_with_real_values(): void
    {
        $templateService = app(NotificationTemplateService::class);
        $dispatcher = app(NotificationDispatchService::class);

        $user = User::factory()->create(['name' => 'Md Tariq', 'email' => 'tariq@example.com']);
        $defaults = $templateService->getFactoryDefaults();

        $samplePayloads = [
            'payment.success' => ['amount' => '499.00', 'gateway' => 'Razorpay', 'payment_method' => 'UPI', 'transaction_id' => 'pay_888999', 'purpose' => 'wallet_topup'],
            'payment.failed' => ['amount' => '499.00', 'failure_reason' => 'Bank server timeout.'],
            'payment.manual_submitted' => ['amount' => '1000.00', 'utr_number' => '123456789012'],
            'payment.manual_approved' => ['amount' => '1000.00', 'admin_name' => 'Super Admin'],
            'payment.manual_rejected' => ['amount' => '1000.00', 'reason' => 'Invalid UTR proof.', 'admin_name' => 'Super Admin'],
            'payment.mandate_failed' => ['reason' => 'Account balance insufficient on auto-debit.'],
            'wallet.credited' => ['amount' => '500.00', 'balance' => '1500.00', 'description' => 'Direct Top-Up'],
            'wallet.debited' => ['amount' => '100.00', 'balance' => '1400.00', 'description' => 'MRU Creation Fee'],
            'wallet.low_balance' => ['balance' => '450.00', 'threshold' => '500.00'],
            'wallet.critical_balance' => ['balance' => '50.00', 'threshold' => '299.00'],
            'wallet.insufficient_for_renewal' => ['balance' => '150.00', 'required_amount' => '299.00'],
            'wallet.frozen' => ['reason' => 'Audit check', 'admin_name' => 'Super Admin'],
            'wallet.unfrozen' => ['admin_name' => 'Super Admin'],
            'mru.locked' => ['mru_code' => '0477', 'reason' => 'Overage unpaid'],
            'mru.unlocked' => ['mru_code' => '0477', 'method' => 'Self-service payment'],
            'mru.overage_charged' => ['mru_code' => '0478', 'amount' => '100.00'],
            'consumer.overage_charged' => ['extra_count' => '25', 'amount' => '50.00', 'cycle_month' => '8', 'cycle_year' => '2026'],
            'agent.subscribed' => ['plan_name' => 'Pro Plan', 'included_mrus' => '5', 'included_consumers' => '500'],
            'agent.plan_migrated' => ['from_plan' => 'Starter Plan', 'to_plan' => 'Pro Plan', 'admin_name' => 'Super Admin'],
            'subscription.renewal_due' => ['plan_name' => 'Pro Plan', 'days_remaining' => '3'],
            'subscription.grace_period' => ['grace_period_ends_at' => '2026-08-25 12:00'],
            'subscription.suspended' => ['reason' => 'Grace period expired without payment.'],
            'subscription.reactivated' => ['plan_name' => 'Pro Plan', 'method' => 'Wallet auto-renewal'],
            'subscription.renewal_failed' => ['plan_name' => 'Pro Plan', 'required_amount' => '599.00', 'wallet_balance' => '50.00'],
            'subscription.upgraded' => ['old_plan' => 'Starter Plan', 'new_plan' => 'Pro Plan', 'prorated_charge' => '150.00'],
            'subscription.downgraded' => ['old_plan' => 'Pro Plan', 'new_plan' => 'Starter Plan', 'prorated_credit' => '150.00'],
            'usage.monthly_summary_ready' => ['month' => '8', 'year' => '2026', 'bills_processed' => '1,250', 'mrus_active' => '4', 'data_coverage' => '98.5'],
            'auth.welcome' => ['agent_name' => 'Md Tariq', 'email' => 'tariq@example.com'],
            'auth.password_reset' => ['agent_name' => 'Md Tariq', 'reset_url' => 'https://app.nexgenhub.site/reset-password/token123'],
        ];

        foreach ($defaults as $eventType => $templateDef) {
            $payload = $samplePayloads[$eventType] ?? [];
            $notification = $dispatcher->dispatch($eventType, $user, $payload);

            // Assert rendered title and body have ZERO unreplaced {placeholders}
            $this->assertDoesNotMatchRegularExpression('/\{[a-z0-9_]+\}/', $notification->title, "Unreplaced placeholder found in title for event {$eventType}: {$notification->title}");
            $this->assertDoesNotMatchRegularExpression('/\{[a-z0-9_]+\}/', $notification->body, "Unreplaced placeholder found in body for event {$eventType}: {$notification->body}");
        }
    }

    /**
     * JOURNEY 5: Reporting Reflects Reality
     */
    public function test_journey_5_usage_tracking_reports_reflect_actual_database_ledger(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0477', 'name' => 'Gerua', 'status' => 'active']);

        // 1. Create bill records with specific review statuses and tags for month 8, year 2026
        // 3 Submitted with tag 'OK'
        for ($i = 1; $i <= 3; $i++) {
            BillRecord::create([
                'user_id' => $user->id,
                'mru_id' => $mru->id,
                'ca_number' => "10230000000{$i}",
                'billing_month' => 8,
                'billing_year' => 2026,
                'review_status' => 'submitted',
                'tag' => 'OK',
                'download_status' => 'downloaded',
                'parse_status' => 'parsed',
            ]);
        }

        // 2 Critical with tag '24days'
        for ($i = 4; $i <= 5; $i++) {
            BillRecord::create([
                'user_id' => $user->id,
                'mru_id' => $mru->id,
                'ca_number' => "10230000000{$i}",
                'billing_month' => 8,
                'billing_year' => 2026,
                'review_status' => 'critical',
                'tag' => '24days',
                'download_status' => 'downloaded',
                'parse_status' => 'parsed',
            ]);
        }

        // 1 Doubt with tag 'RCQ'
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300000006',
            'billing_month' => 8,
            'billing_year' => 2026,
            'review_status' => 'doubt',
            'tag' => 'RCQ',
            'download_status' => 'downloaded',
            'parse_status' => 'parsed',
        ]);

        // 4 Pending with tag 'UNTAGGED'
        for ($i = 7; $i <= 10; $i++) {
            BillRecord::create([
                'user_id' => $user->id,
                'mru_id' => $mru->id,
                'ca_number' => "10230000000{$i}",
                'billing_month' => 8,
                'billing_year' => 2026,
                'review_status' => 'pending',
                'tag' => 'UNTAGGED',
                'download_status' => 'downloaded',
                'parse_status' => 'parsed',
            ]);
        }

        // 2. Add overage charge records for month 8, year 2026
        PlanOverageCharge::create([
            'user_id' => $user->id,
            'charge_type' => 'mru_creation',
            'reference_type' => 'mru',
            'reference_id' => (string) $mru->id,
            'amount' => 100.00,
            'created_at' => Carbon::create(2026, 8, 15),
        ]);
        PlanOverageCharge::create([
            'user_id' => $user->id,
            'charge_type' => 'consumer_cycle',
            'reference_type' => 'billing_cycle',
            'reference_id' => '1',
            'amount' => 50.00,
            'created_at' => Carbon::create(2026, 8, 16),
        ]);

        // 3. Execute StatusTagReportService and verify EXACT counts
        $statusBreakdown = $this->statusTagService->getMonthlyStatusBreakdown($user->id, 8, 2026);
        $this->assertEquals(3, $statusBreakdown['submitted']);
        $this->assertEquals(2, $statusBreakdown['critical']);
        $this->assertEquals(1, $statusBreakdown['doubt']);
        $this->assertEquals(4, $statusBreakdown['pending']);
        $this->assertEquals(10, $statusBreakdown['total']);

        $tagBreakdown = $this->statusTagService->getMonthlyTagBreakdown($user->id, 8, 2026);
        $tagMap = collect($tagBreakdown['tags'])->keyBy('code');
        $this->assertEquals(3, $tagMap['OK']['count'] ?? 0);
        $this->assertEquals(2, $tagMap['24DAYS']['count'] ?? 0);
        $this->assertEquals(1, $tagMap['RCQ']['count'] ?? 0);

        // 4. Execute QuotaUsageReportService and verify EXACT overage numbers
        $overageTotals = $this->quotaReportService->getOverageChargeTotals($user->id, 8, 2026);
        $this->assertEquals(100.00, (float) $overageTotals['mru_charges']);
        $this->assertEquals(50.00, (float) $overageTotals['consumer_charges']);
        $this->assertEquals(150.00, (float) $overageTotals['total_charges']);

        // 5. Execute UsageSummaryService ROI summary and verify exact calculations
        $summary = $this->usageSummaryService->getMonthlySummary($user->id, 8, 2026);
        $this->assertEquals(10, $summary['roi_summary']['bills_processed']);
        $this->assertEquals(1, $summary['roi_summary']['mrus_active']);
    }
}
