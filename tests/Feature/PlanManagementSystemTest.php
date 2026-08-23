<?php

namespace Tests\Feature;

use App\Events\AgentPlanMigratedEvent;
use App\Events\AgentSubscribedEvent;
use App\Events\ConsumerOverageChargedEvent;
use App\Events\MruLockedEvent;
use App\Events\MruOverageChargedEvent;
use App\Events\MruUnlockedEvent;
use App\Events\PlanCreatedEvent;
use App\Events\PlanDeletedEvent;
use App\Events\PlanUpdatedEvent;
use App\Models\AgentSubscription;
use App\Models\BillingCycle;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\PlanOverageCharge;
use App\Models\User;
use App\Services\Plan\ConsumerQuotaService;
use App\Services\Plan\MruQuotaService;
use App\Services\Plan\PlanService;
use App\Services\Plan\RenewalService;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

class PlanManagementSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;
    protected PlanService $planService;
    protected MruQuotaService $mruQuotaService;
    protected ConsumerQuotaService $consumerQuotaService;
    protected RenewalService $renewalService;
    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $this->admin = User::where('email', 'admin@nbpdcl-saas.com')->first();
        $this->agent = User::where('email', 'test@example.com')->first();

        $this->planService = app(PlanService::class);
        $this->mruQuotaService = app(MruQuotaService::class);
        $this->consumerQuotaService = app(ConsumerQuotaService::class);
        $this->renewalService = app(RenewalService::class);
        $this->walletService = app(WalletService::class);
    }

    /**
     * (a) Locked-at-purchase pricing and quotas are NEVER affected by a later Plan edit.
     */
    public function test_locked_at_purchase_is_never_affected_by_later_plan_edit(): void
    {
        Event::fake([PlanCreatedEvent::class, PlanUpdatedEvent::class, AgentSubscribedEvent::class]);

        // 1. Create a Plan with initial numbers
        $plan = $this->planService->createPlan([
            'name' => 'Starter Tier',
            'description' => 'Original Starter description',
            'base_price' => 399.00,
            'included_mrus' => 5,
            'included_consumers' => 2000,
            'extra_mru_rate' => 100.00,
            'extra_consumer_rate' => 0.25,
            'is_active' => true,
        ]);

        $duration = $plan->durations()->where('duration_months', 1)->first();

        // 2. Agent subscribes to the plan
        $subscription = $this->planService->subscribeAgent($this->agent, $plan, $duration);

        $this->assertEquals(399.00, (float)$subscription->base_price_paid);
        $this->assertEquals(5, $subscription->included_mrus_locked);
        $this->assertEquals(2000, $subscription->included_consumers_locked);
        $this->assertEquals(100.00, (float)$subscription->extra_mru_rate_locked);
        $this->assertEquals(0.25, (float)$subscription->extra_consumer_rate_locked);

        // 3. Admin modifies the Plan (doubles price and halves quotas)
        $this->planService->updatePlan($plan, [
            'name' => 'Starter Tier V2',
            'base_price' => 799.00,
            'included_mrus' => 2,
            'included_consumers' => 1000,
            'extra_mru_rate' => 200.00,
            'extra_consumer_rate' => 0.50,
        ]);

        // 4. Verify that existing subscriber's locked record remains completely unchanged
        $freshSub = $subscription->fresh();
        $this->assertEquals(399.00, (float)$freshSub->base_price_paid);
        $this->assertEquals(5, $freshSub->included_mrus_locked);
        $this->assertEquals(2000, $freshSub->included_consumers_locked);
        $this->assertEquals(100.00, (float)$freshSub->extra_mru_rate_locked);
        $this->assertEquals(0.25, (float)$freshSub->extra_consumer_rate_locked);
    }

    /**
     * (b) MRU and Consumer overage gates fire independently and correctly in sequence.
     */
    public function test_independent_sequential_pay_gates_mru_unlock_then_consumer_overage(): void
    {
        Event::fake([MruUnlockedEvent::class, ConsumerOverageChargedEvent::class, MruOverageChargedEvent::class]);

        // Setup plan: 1 MRU, 100 Consumers, Extra MRU = ₹100, Extra Consumer = ₹1.00
        $plan = $this->planService->createPlan([
            'name' => 'Test Sequential Plan',
            'base_price' => 499.00,
            'included_mrus' => 1,
            'included_consumers' => 100,
            'extra_mru_rate' => 100.00,
            'extra_consumer_rate' => 1.00,
        ]);
        $duration = $plan->durations()->where('duration_months', 1)->first();
        $this->planService->subscribeAgent($this->agent, $plan, $duration);

        // Fund agent wallet with ₹500
        $this->walletService->credit($this->agent, 500.00, 'payment_topup');

        // Create 1 MRU (uses included quota)
        $mru1 = Mru::create(['user_id' => $this->agent->id, 'code' => 'MRU01', 'name' => 'MRU One', 'status' => 'active']);
        $this->mruQuotaService->consumeMruSlot($this->agent, $mru1);

        // Create 2nd MRU and Lock it
        $mru2 = Mru::create(['user_id' => $this->agent->id, 'code' => 'MRU02', 'name' => 'MRU Two', 'status' => 'active']);
        $this->mruQuotaService->lockMru($mru2, 'over_quota_unpaid');
        $this->assertTrue($mru2->isLocked());

        // Attach 150 consumers to MRU 2 (50 consumers over quota)
        for ($i = 1; $i <= 150; $i++) {
            ConsumerAccount::create([
                'user_id' => $this->agent->id,
                'mru_id' => $mru2->id,
                'ca_number' => "CA_SEQ_{$i}",
                'consumer_name' => "Consumer {$i}",
                'status' => 'active',
            ]);
        }

        // STEP 1: Attempt cycle on locked MRU -> BLOCKED by MRU lock gate
        $cycleResult1 = $this->consumerQuotaService->consumeConsumerQuota(
            user: $this->agent,
            mru: $mru2,
            month: 8,
            year: 2026,
            consumerCount: 150,
            payOverage: false
        );
        $this->assertFalse($cycleResult1['allowed']);
        $this->assertTrue($cycleResult1['mru_locked']);

        // STEP 2: Agent pays to UNLOCK MRU (MRU pay-gate: ₹100 deducted)
        $unlockResult = $this->mruQuotaService->unlockMru($mru2, payOverage: true);
        $this->assertTrue($unlockResult['success']);
        $this->assertEquals(400.00, $this->walletService->getBalance($this->agent));
        $this->assertFalse($mru2->fresh()->isLocked());

        // Verify MRU overage charge recorded
        $this->assertDatabaseHas('plan_overage_charges', [
            'user_id' => $this->agent->id,
            'charge_type' => 'mru_unlock',
            'amount' => 100.00,
        ]);

        // STEP 3: Agent retries cycle creation -> MRU gate passes, but CONSUMER gate now fires!
        // 150 consumers vs 100 included = 50 extra @ ₹1.00 = ₹50.00
        $cycleResult2 = $this->consumerQuotaService->consumeConsumerQuota(
            user: $this->agent,
            mru: $mru2,
            month: 8,
            year: 2026,
            consumerCount: 150,
            payOverage: false
        );
        $this->assertFalse($cycleResult2['allowed']);
        $this->assertTrue($cycleResult2['requires_payment']);
        $this->assertEquals(50, $cycleResult2['extra_count']);
        $this->assertEquals(50.00, $cycleResult2['amount_due']);

        // STEP 4: Agent pays consumer overage -> cycle created, ₹50 deducted
        $cycleResult3 = $this->consumerQuotaService->consumeConsumerQuota(
            user: $this->agent,
            mru: $mru2,
            month: 8,
            year: 2026,
            consumerCount: 150,
            payOverage: true
        );
        $this->assertTrue($cycleResult3['allowed']);
        $this->assertEquals(350.00, $this->walletService->getBalance($this->agent));

        // Verify Consumer overage charge recorded
        $this->assertDatabaseHas('plan_overage_charges', [
            'user_id' => $this->agent->id,
            'charge_type' => 'consumer_cycle',
            'amount' => 50.00,
        ]);
    }

    /**
     * (c) Consumer quota does NOT silently recalculate without an explicit sync call.
     */
    public function test_consumer_quota_does_not_silently_recalculate_without_explicit_sync(): void
    {
        $plan = $this->planService->createPlan([
            'name' => 'Sync Test Plan',
            'base_price' => 299.00,
            'included_mrus' => 5,
            'included_consumers' => 500,
            'extra_mru_rate' => 100.00,
            'extra_consumer_rate' => 0.50,
        ]);
        $duration = $plan->durations()->where('duration_months', 1)->first();
        $this->planService->subscribeAgent($this->agent, $plan, $duration);
        $this->walletService->credit($this->agent, 500.00, 'payment_topup');

        $mru = Mru::create(['user_id' => $this->agent->id, 'code' => 'MRUSYNC', 'name' => 'Sync MRU', 'status' => 'active']);

        // Add 50 consumers and create cycle
        for ($i = 1; $i <= 50; $i++) {
            ConsumerAccount::create([
                'user_id' => $this->agent->id,
                'mru_id' => $mru->id,
                'ca_number' => "CA_SYNC_{$i}",
                'consumer_name' => "Consumer {$i}",
                'status' => 'active',
            ]);
        }

        $res = $this->consumerQuotaService->consumeConsumerQuota($this->agent, $mru, 8, 2026, 50, false);
        $cycle = $res['cycle'];
        $this->assertEquals(50, $cycle->consumer_count_at_creation);

        // Add 20 more consumers to the MRU passively
        for ($i = 51; $i <= 70; $i++) {
            ConsumerAccount::create([
                'user_id' => $this->agent->id,
                'mru_id' => $mru->id,
                'ca_number' => "CA_SYNC_{$i}",
                'consumer_name' => "Consumer {$i}",
                'status' => 'active',
            ]);
        }

        // Assert cycle has NOT changed passively
        $this->assertEquals(50, $cycle->fresh()->consumer_count_at_creation);

        // Explicitly trigger syncCycleConsumerCount
        $syncRes = $this->consumerQuotaService->syncCycleConsumerCount($cycle, payOverage: true);
        $this->assertTrue($syncRes['synced']);
        $this->assertEquals(70, $syncRes['current_count']);
        $this->assertEquals(70, $cycle->fresh()->consumer_count_at_creation);
    }

    /**
     * (d) Renewal calculation NEVER double-charges consumer overage.
     */
    public function test_renewal_calculation_never_includes_consumer_overage(): void
    {
        $plan = $this->planService->createPlan([
            'name' => 'Renewal Plan',
            'base_price' => 500.00,
            'included_mrus' => 2,
            'included_consumers' => 100,
            'extra_mru_rate' => 150.00,
            'extra_consumer_rate' => 2.00,
        ]);
        $duration = $plan->durations()->where('duration_months', 1)->first();
        $this->planService->subscribeAgent($this->agent, $plan, $duration);

        // Create 3 active MRUs (1 extra MRU)
        Mru::create(['user_id' => $this->agent->id, 'code' => 'R_MRU1', 'name' => 'R1', 'status' => 'active']);
        Mru::create(['user_id' => $this->agent->id, 'code' => 'R_MRU2', 'name' => 'R2', 'status' => 'active']);
        Mru::create(['user_id' => $this->agent->id, 'code' => 'R_MRU3', 'name' => 'R3', 'status' => 'active']);

        // Simulate 500 extra consumers in past billing cycles
        BillingCycle::create([
            'user_id' => $this->agent->id,
            'mru_id' => 1,
            'cycle_month' => 7,
            'cycle_year' => 2026,
            'consumer_count_at_creation' => 600,
            'included_quota_used' => 100,
            'extra_consumer_count' => 500,
            'extra_consumer_charge' => 1000.00,
            'status' => 'completed',
        ]);

        $summary = $this->renewalService->calculateRenewalSummary($this->agent);

        // Base price = 500, Extra MRUs = 1 * 150 = 150, Total = 650
        $this->assertEquals(500.00, $summary['base_price']);
        $this->assertEquals(1, $summary['extra_mrus_count']);
        $this->assertEquals(150.00, $summary['extra_mrus_total']);
        $this->assertEquals(650.00, $summary['total_with_extra_mrus']);

        // Invariant check: Consumer overage is strictly 0.00 in renewal
        $this->assertEquals(0.00, $summary['consumer_overage_amount']);
    }

    /**
     * (e) Renewal auto-locks the most recently created MRU when agent selects NO on extra MRU prompt.
     */
    public function test_renewal_flow_without_extra_mrus_auto_locks_newest_mru(): void
    {
        $plan = $this->planService->createPlan([
            'name' => 'Auto-Lock Plan',
            'base_price' => 300.00,
            'included_mrus' => 2,
            'included_consumers' => 500,
            'extra_mru_rate' => 100.00,
            'extra_consumer_rate' => 0.20,
        ]);
        $duration = $plan->durations()->where('duration_months', 1)->first();
        $this->planService->subscribeAgent($this->agent, $plan, $duration);
        $this->walletService->credit($this->agent, 500.00, 'payment_topup');

        $mru1 = Mru::create(['user_id' => $this->agent->id, 'code' => 'MRU_A', 'name' => 'First MRU', 'status' => 'active']);
        $mru2 = Mru::create(['user_id' => $this->agent->id, 'code' => 'MRU_B', 'name' => 'Second MRU', 'status' => 'active']);
        $mru3 = Mru::create(['user_id' => $this->agent->id, 'code' => 'MRU_C', 'name' => 'Third MRU (Newest)', 'status' => 'active']);

        // Agent renews with includeExtraMrus = false (and no manual selection -> auto-lock newest)
        $result = $this->renewalService->processRenewal($this->agent, includeExtraMrus: false);

        $this->assertTrue($result['success']);
        $this->assertEquals(300.00, $result['amount_charged']);
        $this->assertEquals(200.00, $this->walletService->getBalance($this->agent));

        // MRU 1 and 2 remain active, while newest MRU 3 is locked!
        $this->assertEquals('active', $mru1->fresh()->status);
        $this->assertEquals('active', $mru2->fresh()->status);
        $this->assertEquals('locked', $mru3->fresh()->status);
        $this->assertEquals('auto_locked_renewal', $mru3->fresh()->locked_reason);
    }

    /**
     * (f) Locked MRU permissions enforcement.
     */
    public function test_locked_mru_permissions_enforced_strictly(): void
    {
        $mru = Mru::create([
            'user_id' => $this->agent->id,
            'code' => 'MRU_LOCK_TEST',
            'name' => 'Locked Test MRU',
            'status' => 'locked',
            'locked_reason' => 'over_quota_unpaid',
        ]);

        // Allowed operations
        $this->assertTrue($this->mruQuotaService->isActionAllowed($mru, 'view'));
        $this->assertTrue($this->mruQuotaService->isActionAllowed($mru, 'rename'));
        $this->assertTrue($this->mruQuotaService->isActionAllowed($mru, 'delete'));
        $this->assertTrue($this->mruQuotaService->isActionAllowed($mru, 'add_consumer'));
        $this->assertTrue($this->mruQuotaService->isActionAllowed($mru, 'remove_consumer'));

        // Blocked operations
        $this->assertFalse($this->mruQuotaService->isActionAllowed($mru, 'modify_consumer_details'));
        $this->assertFalse($this->mruQuotaService->isActionAllowed($mru, 'create_cycle'));
        $this->assertFalse($this->mruQuotaService->isActionAllowed($mru, 'process_pdf'));
        $this->assertFalse($this->mruQuotaService->isActionAllowed($mru, 'download_pdf'));
    }

    /**
     * (g) Admin force delete requires migration target or force flag.
     */
    public function test_force_delete_requires_migration_target_or_force_flag(): void
    {
        $planA = $this->planService->createPlan(['name' => 'Plan A', 'base_price' => 100.00]);
        $planB = $this->planService->createPlan(['name' => 'Plan B', 'base_price' => 200.00]);

        $duration = $planA->durations()->first();
        $this->planService->subscribeAgent($this->agent, $planA, $duration);

        // Attempting force delete without migration target or force flag throws InvalidArgumentException
        $this->expectException(InvalidArgumentException::class);
        $this->planService->forceDeletePlan($planA, migrationPlanId: null, force: false);
    }
}
