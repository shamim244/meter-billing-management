<?php

namespace Tests\Feature;

use App\Enums\DebitResult;
use App\Events\PlanDowngradedEvent;
use App\Events\PlanUpgradedEvent;
use App\Events\RenewalFailedInsufficientBalanceEvent;
use App\Events\SubscriptionEnteredGracePeriodEvent;
use App\Events\SubscriptionReactivatedEvent;
use App\Events\SubscriptionRenewalDueEvent;
use App\Events\SubscriptionSuspendedEvent;
use App\Models\AgentSubscription;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\PlanUpgradeLog;
use App\Models\RenewalAttempt;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Billing\PlanChangeService;
use App\Services\Billing\SubscriptionLifecycleService;
use App\Services\Plan\MruQuotaService;
use App\Services\Plan\PlanService;
use App\Services\Plan\RenewalService;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

class BillingSubscriptionSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;
    protected PlanService $planService;
    protected SubscriptionLifecycleService $lifecycleService;
    protected PlanChangeService $planChangeService;
    protected RenewalService $renewalService;
    protected MruQuotaService $mruQuotaService;
    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $this->admin = User::where('email', 'admin@nbpdcl-saas.com')->first();
        $this->agent = User::where('email', 'test@example.com')->first();

        $this->planService = app(PlanService::class);
        $this->lifecycleService = app(SubscriptionLifecycleService::class);
        $this->planChangeService = app(PlanChangeService::class);
        $this->renewalService = app(RenewalService::class);
        $this->mruQuotaService = app(MruQuotaService::class);
        $this->walletService = app(WalletService::class);
    }

    /**
     * Helper to create a plan with durations.
     */
    protected function createTestPlan(string $name, float $basePrice, int $mrus, int $consumers, ?int $graceDays = null): Plan
    {
        return $this->planService->createPlan([
            'name' => $name,
            'base_price' => $basePrice,
            'included_mrus' => $mrus,
            'included_consumers' => $consumers,
            'extra_mru_rate' => 100.00,
            'extra_consumer_rate' => 0.20,
            'grace_period_days' => $graceDays,
            'is_active' => true,
        ]);
    }

    /**
     * 1. Downgrade is correctly BLOCKED server-side when active MRU count exceeds new plan's quota.
     */
    public function test_downgrade_is_blocked_server_side_when_active_mrus_exceed_target_quota(): void
    {
        // Starter has 2 MRUs, Pro has 5 MRUs
        $starterPlan = $this->createTestPlan('Starter Tier', 299.00, 2, 1000);
        $proPlan = $this->createTestPlan('Pro Tier', 699.00, 5, 5000);

        $duration = $proPlan->durations()->where('duration_months', 1)->first();
        $sub = $this->planService->subscribeAgent($this->agent, $proPlan, $duration);

        // Agent has 4 active MRUs (fits Pro's 5, but exceeds Starter's 2)
        for ($i = 1; $i <= 4; $i++) {
            Mru::create([
                'user_id' => $this->agent->id,
                'code' => "MRU_ACT_{$i}",
                'name' => "MRU {$i}",
                'status' => 'active',
            ]);
        }

        // Check eligibility directly
        $eligibility = $this->planChangeService->checkDowngradeEligibility($sub, $starterPlan);
        $this->assertFalse($eligibility['eligible']);
        $this->assertEquals(4, $eligibility['active_mrus_count']);
        $this->assertEquals(2, $eligibility['new_plan_quota']);
        $this->assertEquals(2, $eligibility['excess_mrus']);

        // Attempting downgrade directly throws InvalidArgumentException server-side
        $this->expectException(InvalidArgumentException::class);
        $this->planChangeService->downgradePlan($sub, $starterPlan);
    }

    /**
     * 2. Upgrade correctly auto-unlocks a previously-locked MRU when the new plan's quota now covers it.
     */
    public function test_upgrade_auto_unlocks_locked_mrus_covered_by_new_quota(): void
    {
        Event::fake([PlanUpgradedEvent::class]);

        $starterPlan = $this->createTestPlan('Starter Tier', 299.00, 2, 1000);
        $proPlan = $this->createTestPlan('Pro Tier', 699.00, 5, 5000);

        $sub = $this->planService->subscribeAgent($this->agent, $starterPlan, $starterPlan->durations()->first());

        // Fund agent wallet for upgrade proration
        $this->walletService->credit($this->agent, 500.00, 'payment_topup');

        // Agent has 2 active MRUs and 2 LOCKED MRUs
        $mru1 = Mru::create(['user_id' => $this->agent->id, 'code' => 'M1', 'name' => 'MRU 1', 'status' => 'active']);
        $mru2 = Mru::create(['user_id' => $this->agent->id, 'code' => 'M2', 'name' => 'MRU 2', 'status' => 'active']);
        $mru3 = Mru::create(['user_id' => $this->agent->id, 'code' => 'M3', 'name' => 'MRU 3', 'status' => 'locked', 'locked_reason' => 'over_quota_unpaid']);
        $mru4 = Mru::create(['user_id' => $this->agent->id, 'code' => 'M4', 'name' => 'MRU 4', 'status' => 'locked', 'locked_reason' => 'over_quota_unpaid']);

        $this->assertTrue($mru3->isLocked());
        $this->assertTrue($mru4->isLocked());

        // Perform upgrade to Pro (5 MRUs quota)
        $res = $this->planChangeService->upgradePlan($sub, $proPlan);

        $this->assertTrue($res['success']);
        $this->assertCount(2, $res['auto_unlocked_mrus']);

        // Both previously locked MRUs should now be unlocked automatically without paying separate unlock fee!
        $this->assertEquals('active', $mru3->fresh()->status);
        $this->assertEquals('active', $mru4->fresh()->status);
        $this->assertFalse($mru3->fresh()->isLocked());
        $this->assertFalse($mru4->fresh()->isLocked());

        // Assert log recorded
        $this->assertDatabaseHas('plan_upgrade_log', [
            'user_id' => $this->agent->id,
            'from_plan_id' => $starterPlan->id,
            'to_plan_id' => $proPlan->id,
            'action_type' => 'upgrade',
        ]);
    }

    /**
     * 3. Downgrade CREDITS wallet (not debits) — assert the wallet balance INCREASES after a downgrade.
     */
    public function test_downgrade_credits_wallet_balance_increases(): void
    {
        Event::fake([PlanDowngradedEvent::class]);

        $proPlan = $this->createTestPlan('Pro Tier', 600.00, 5, 5000);
        $starterPlan = $this->createTestPlan('Starter Tier', 300.00, 2, 1000);

        // Subscribed with 30 days cycle, 15 days remaining
        $sub = $this->planService->subscribeAgent($this->agent, $proPlan, $proPlan->durations()->first());
        $sub->update([
            'billing_start' => now()->subDays(15),
            'billing_end' => now()->addDays(15),
            'base_price_paid' => 600.00,
        ]);

        $initialBalance = (float) $this->walletService->getBalance($this->agent);
        $this->assertEquals(0.00, $initialBalance);

        // Only 1 active MRU in use -> eligible to downgrade to Starter (2 MRUs)
        Mru::create(['user_id' => $this->agent->id, 'code' => 'M_ONE', 'name' => 'Single MRU', 'status' => 'active']);

        $res = $this->planChangeService->downgradePlan($sub, $starterPlan);

        $this->assertTrue($res['success']);
        $this->assertGreaterThan(0, $res['amount_credited']);

        // Proration: Old credit ≈ 300, New cost ≈ 150, Net credited ≈ 150
        $newBalance = (float) $this->walletService->getBalance($this->agent->fresh());
        $this->assertGreaterThan($initialBalance, $newBalance);
        $this->assertEquals($res['amount_credited'], $newBalance);

        // Assert log recorded
        $this->assertDatabaseHas('plan_upgrade_log', [
            'user_id' => $this->agent->id,
            'from_plan_id' => $proPlan->id,
            'to_plan_id' => $starterPlan->id,
            'action_type' => 'downgrade',
        ]);
    }

    /**
     * 4. Auto-renewal with insufficient balance does NOT attempt any PG mandate call and does NOT change subscription state.
     */
    public function test_auto_renewal_insufficient_balance_does_not_change_state_and_no_pg_call(): void
    {
        Event::fake([RenewalFailedInsufficientBalanceEvent::class]);

        $plan = $this->createTestPlan('Standard', 499.00, 3, 2000);
        $sub = $this->planService->subscribeAgent($this->agent, $plan, $plan->durations()->first());

        $sub->update([
            'lifecycle_status' => 'renewal_due',
            'auto_renewal_enabled' => true,
            'billing_end' => now()->subDay(),
            'grace_period_ends_at' => now()->addDays(2),
        ]);

        // Agent has ₹0 wallet balance
        $this->assertEquals(0.00, $this->walletService->getBalance($this->agent));

        $stats = $this->lifecycleService->runDailyLifecycleProcessor();

        $this->assertEquals(1, $stats['auto_renewal_failed']);
        $this->assertEquals(0, $stats['auto_renewal_success']);

        // Subscription remains in renewal_due (NOT suspended, state unchanged)
        $freshSub = $sub->fresh();
        $this->assertEquals('renewal_due', $freshSub->lifecycle_status);

        // Renewal attempt log recorded as insufficient_balance
        $this->assertDatabaseHas('renewal_attempts', [
            'agent_subscription_id' => $sub->id,
            'user_id' => $this->agent->id,
            'attempt_type' => 'auto',
            'status' => 'insufficient_balance',
        ]);

        Event::assertDispatched(RenewalFailedInsufficientBalanceEvent::class);
    }

    /**
     * 5. Grace period of 0 days (admin-configured) correctly skips straight from RENEWAL_DUE to SUSPENDED with no grace window.
     */
    public function test_zero_days_grace_period_skips_straight_to_suspended(): void
    {
        Event::fake([SubscriptionRenewalDueEvent::class, SubscriptionSuspendedEvent::class]);

        // Plan with 0 grace days override
        $zeroGracePlan = $this->createTestPlan('Zero Grace Plan', 199.00, 1, 500, graceDays: 0);
        $sub = $this->planService->subscribeAgent($this->agent, $zeroGracePlan, $zeroGracePlan->durations()->first());

        $sub->update([
            'billing_end' => now()->subHour(),
            'lifecycle_status' => 'active',
        ]);

        $this->lifecycleService->transitionToRenewalDue($sub);

        $freshSub = $sub->fresh();
        $this->assertEquals('suspended', $freshSub->lifecycle_status);
        $this->assertNotNull($freshSub->suspended_at);
        $this->assertTrue($freshSub->isSuspended());

        Event::assertDispatched(SubscriptionSuspendedEvent::class);
    }

    /**
     * 6. SUSPENDED state blocks write actions but allows full read access to historical data.
     */
    public function test_suspended_state_blocks_write_actions_and_allows_read_access(): void
    {
        $plan = $this->createTestPlan('Test Tier', 299.00, 2, 1000);
        $sub = $this->planService->subscribeAgent($this->agent, $plan, $plan->durations()->first());

        $this->lifecycleService->transitionToSuspended($sub);
        $this->assertTrue($sub->fresh()->isSuspended());

        // 1. Read / View requests are PERMITTED
        $readResponse = $this->actingAs($this->agent)->get(route('dashboard'));
        $readResponse->assertStatus(200);

        $mrusRead = $this->actingAs($this->agent)->get(route('mrus.index'));
        $mrusRead->assertStatus(200);

        // 2. Write / Mutating requests are BLOCKED with 403 / redirect
        $writeResponse = $this->actingAs($this->agent)->postJson(route('mrus.store'), [
            'name' => 'Attempted MRU',
            'code' => 'ATTEMPT01',
        ]);

        $writeResponse->assertStatus(403);
        $writeResponse->assertJsonFragment([
            'error' => 'subscription_suspended',
            'is_suspended' => true,
        ]);
    }

    /**
     * 7. Admin manual state override / reactivation requires mandatory reason.
     */
    public function test_admin_manual_reactivation_requires_mandatory_reason(): void
    {
        $plan = $this->createTestPlan('Test Plan', 299.00, 2, 1000);
        $sub = $this->planService->subscribeAgent($this->agent, $plan, $plan->durations()->first());
        $this->lifecycleService->transitionToSuspended($sub);

        // Attempting reactivation without reason throws InvalidArgumentException
        $this->expectException(InvalidArgumentException::class);
        $this->lifecycleService->reactivate($sub, adminReason: '', admin: $this->admin);
    }
}
