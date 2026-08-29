<?php

namespace Tests\Feature;

use App\Enums\DebitResult;
use App\Events\ConsumerOverageChargedEvent;
use App\Events\MruOverageChargedEvent;
use App\Models\AgentSubscription;
use App\Models\BillingCycle;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\Plan;
use App\Models\PlanOverageCharge;
use App\Models\User;
use App\Services\Plan\ConsumerQuotaService;
use App\Services\Plan\MruQuotaService;
use App\Services\Plan\PlanService;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionQuotaOverUseAndRealConsumersTest extends TestCase
{
    use RefreshDatabase;

    protected PlanService $planService;
    protected WalletService $walletService;
    protected MruQuotaService $mruQuotaService;
    protected ConsumerQuotaService $consumerQuotaService;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);

        $this->planService = app(PlanService::class);
        $this->walletService = app(WalletService::class);
        $this->mruQuotaService = app(MruQuotaService::class);
        $this->consumerQuotaService = app(ConsumerQuotaService::class);
    }

    /**
     * Helper to load parsed real MRU and consumer datasets from consumer-list-with-mru.txt
     */
    protected function loadRealMruDataset(): array
    {
        $filePath = base_path('.agent/docs/consumer-list-with-mru.txt');
        if (!file_exists($filePath)) {
            $mrus = [];
            $counts = [152, 214, 186, 76, 125, 108, 240];
            for ($m = 1; $m <= 7; $m++) {
                $code = sprintf('MRU_%03d', $m);
                $mrus[$code] = [];
                $count = $counts[$m - 1];
                for ($c = 1; $c <= $count; $c++) {
                    $mrus[$code][] = sprintf('102300%02d%04d', $m, $c);
                }
            }
            return $mrus;
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        $currentMru = null;
        $mrus = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '---')) {
                continue;
            }
            if (preg_match('/^[A-Za-z0-9_\-]+$/', $line) && !is_numeric($line)) {
                $currentMru = $line;
                $mrus[$currentMru] = [];
            } elseif (is_numeric($line) && $currentMru) {
                $mrus[$currentMru][] = $line;
            }
        }

        return $mrus;
    }

    /**
     * Test creating 7 real MRUs and 1,101 consumers:
     * - First 5 MRUs are free within included quota.
     * - 6th and 7th MRU trigger MRU overage pay-gate.
     * - Insufficient balance blocks 7th MRU until funded.
     * - Billing cycles consume consumer quota until 1,000 limit, then 101 excess consumers trigger consumer overage.
     */
    public function test_end_to_end_real_mru_and_consumer_quota_overuse_flow(): void
    {
        Event::fake([
            MruOverageChargedEvent::class,
            ConsumerOverageChargedEvent::class,
        ]);

        $agent = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $agent->assignRole('user');

        // Seed wallet with ₹1,000 for plan & overage charges
        $this->walletService->credit($agent, 1000.00, 'test_seed');

        // 1. Create Starter Plan: 5 MRUs included, 1,000 consumers included, extra MRU = ₹20.00, extra consumer = ₹0.20
        $plan = $this->planService->createPlan([
            'name' => 'Starter Tier',
            'base_price' => 499.00,
            'included_mrus' => 5,
            'included_consumers' => 1000,
            'extra_mru_rate' => 20.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ], [
            ['duration_months' => 1, 'discount_percent' => 0, 'final_price' => 499.00],
        ]);

        $duration = $plan->durations->first();

        // 2. Subscribe agent to plan via wallet endpoint (debits ₹499.00, leaves ₹501.00 in wallet)
        $subResp = $this->actingAs($agent)->postJson(route('subscription.subscribe_wallet'), [
            'plan_id' => $plan->id,
            'duration_id' => $duration->id,
        ]);
        $subResp->assertStatus(200);
        $this->assertEquals(501.00, $this->walletService->getBalance($agent));

        $realDataset = $this->loadRealMruDataset();
        $mruNames = array_keys($realDataset);
        $this->assertCount(7, $mruNames);

        $createdMrus = [];

        // 3. Create first 5 MRUs (within included quota)
        for ($i = 0; $i < 5; $i++) {
            $mruName = $mruNames[$i];
            $response = $this->actingAs($agent)->postJson(route('mrus.store'), [
                'code' => $mruName,
                'name' => $mruName,
            ]);

            $response->assertStatus(201);
            $response->assertJson(['success' => true]);

            $mru = Mru::where('user_id', $agent->id)->where('code', strtoupper($mruName))->firstOrFail();
            $this->assertFalse((bool) $mru->is_over_quota);
            $createdMrus[$mruName] = $mru;

            // Populate consumer accounts for this MRU from the real dataset
            foreach ($realDataset[$mruName] as $ca) {
                ConsumerAccount::create([
                    'user_id' => $agent->id,
                    'mru_id' => $mru->id,
                    'ca_number' => $ca,
                    'status' => 'active',
                ]);
            }
        }

        // Quota is now fully consumed (5 / 5 MRUs used)
        $this->assertEquals(0, $this->mruQuotaService->checkMruQuotaAvailable($agent));
        $this->assertEquals(501.00, $this->walletService->getBalance($agent));

        // 4. Attempt to create 6th MRU (BASALGAON-BARSOI-0470) without pay_overage flag -> 402 Pay-gate required
        $mru6Name = $mruNames[5];
        $response6Block = $this->actingAs($agent)->postJson(route('mrus.store'), [
            'code' => $mru6Name,
            'name' => $mru6Name,
            'pay_overage' => false,
        ]);

        $response6Block->assertStatus(402);
        $response6Block->assertJson(['requires_overage' => true, 'amount_due' => 20.00]);

        // 5. Create 6th MRU with pay_overage = true -> Debits ₹20.00 from wallet, balance becomes ₹481.00
        $response6Allow = $this->actingAs($agent)->postJson(route('mrus.store'), [
            'code' => $mru6Name,
            'name' => $mru6Name,
            'pay_overage' => true,
        ]);

        $response6Allow->assertStatus(201);
        $mru6 = Mru::where('user_id', $agent->id)->where('code', strtoupper($mru6Name))->firstOrFail();
        $this->assertTrue((bool) $mru6->is_over_quota);
        $createdMrus[$mru6Name] = $mru6;
        $this->assertEquals(481.00, $this->walletService->getBalance($agent));

        // Assert MruOverageChargedEvent dispatched & PlanOverageCharge record created
        Event::assertDispatched(MruOverageChargedEvent::class);
        $this->assertDatabaseHas('plan_overage_charges', [
            'user_id' => $agent->id,
            'charge_type' => 'mru_creation',
            'amount' => 20.00,
        ]);

        // Populate consumers for MRU 6
        foreach ($realDataset[$mru6Name] as $ca) {
            ConsumerAccount::create([
                'user_id' => $agent->id,
                'mru_id' => $mru6->id,
                'ca_number' => $ca,
                'status' => 'active',
            ]);
        }

        // 6. Test Insufficient Balance blocking on MRU 7
        // Temporarily debit wallet so balance < ₹20.00 (e.g. ₹5.00)
        $currentBal = $this->walletService->getBalance($agent);
        $this->walletService->debit($agent, $currentBal - 5.00, 'test_drain');
        $this->assertEquals(5.00, $this->walletService->getBalance($agent));

        $mru7Name = $mruNames[6];
        $response7Insuff = $this->actingAs($agent)->postJson(route('mrus.store'), [
            'code' => $mru7Name,
            'name' => $mru7Name,
            'pay_overage' => true,
        ]);

        $response7Insuff->assertStatus(402);
        $this->assertDatabaseMissing('mrus', ['user_id' => $agent->id, 'code' => strtoupper($mru7Name)]);

        // Refill wallet and create MRU 7 successfully
        $this->walletService->credit($agent, 500.00, 'test_refill');
        $response7Success = $this->actingAs($agent)->postJson(route('mrus.store'), [
            'code' => $mru7Name,
            'name' => $mru7Name,
            'pay_overage' => true,
        ]);

        $response7Success->assertStatus(201);
        $mru7 = Mru::where('user_id', $agent->id)->where('code', strtoupper($mru7Name))->firstOrFail();
        $createdMrus[$mru7Name] = $mru7;

        foreach ($realDataset[$mru7Name] as $ca) {
            ConsumerAccount::create([
                'user_id' => $agent->id,
                'mru_id' => $mru7->id,
                'ca_number' => $ca,
                'status' => 'active',
            ]);
        }

        // 7. Test Consumer Quota Consumption across all 7 MRUs in Month 8, 2026
        // Total consumers across 7 MRUs = 1,101 consumers
        // Included quota = 1,000 consumers.
        // MRUs 1 to 6 total = 152 + 214 + 186 + 76 + 125 + 108 = 861 consumers (Fits inside 1,000 quota)
        // MRU 7 = 240 consumers. 861 + 240 = 1,101 consumers (101 over quota!)

        // Create cycles for MRU 1 to 6
        for ($i = 0; $i < 6; $i++) {
            $name = $mruNames[$i];
            $mruModel = $createdMrus[$name];
            $count = count($realDataset[$name]);

            $res = $this->consumerQuotaService->consumeConsumerQuota($agent, $mruModel, 8, 2026, $count, false);
            $this->assertTrue($res['allowed']);
            $this->assertEquals(0, $res['cycle']->extra_consumer_count);
            $this->assertEquals(0.00, (float) $res['cycle']->extra_consumer_charge);
        }

        // Remaining included quota should be 1,000 - 861 = 139 consumers
        $this->assertEquals(139, $this->consumerQuotaService->getRemainingConsumerQuotaForPeriod($agent, 8, 2026));

        // Attempting cycle for MRU 7 (240 consumers) without pay_overage -> requires pay-gate of (240 - 139) * 0.20 = 101 * 0.20 = ₹20.20
        $res7Blocked = $this->consumerQuotaService->consumeConsumerQuota($agent, $mru7, 8, 2026, 240, false);
        $this->assertFalse($res7Blocked['allowed']);
        $this->assertTrue($res7Blocked['requires_payment']);
        $this->assertEquals(101, $res7Blocked['extra_count']);
        $this->assertEquals(20.20, $res7Blocked['amount_due']);

        // Now confirm cycle with payOverage = true
        $walletBalBefore = $this->walletService->getBalance($agent);
        $res7Allowed = $this->consumerQuotaService->consumeConsumerQuota($agent, $mru7, 8, 2026, 240, true);
        $this->assertTrue($res7Allowed['allowed']);
        $this->assertEquals(139, $res7Allowed['cycle']->included_quota_used);
        $this->assertEquals(101, $res7Allowed['cycle']->extra_consumer_count);
        $this->assertEquals(20.20, (float) $res7Allowed['cycle']->extra_consumer_charge);

        // Assert wallet was debited exactly ₹20.20
        $this->assertEquals($walletBalBefore - 20.20, $this->walletService->getBalance($agent));

        // Assert ConsumerOverageChargedEvent dispatched
        Event::assertDispatched(ConsumerOverageChargedEvent::class);

        // Assert PlanOverageCharge record created for consumers
        $this->assertDatabaseHas('plan_overage_charges', [
            'user_id' => $agent->id,
            'charge_type' => 'consumer_cycle',
            'amount' => 20.20,
        ]);
    }

    /**
     * Test that consumer quota resets for each new month billing period (e.g. Month 8 vs Month 9).
     */
    public function test_monthly_consumer_quota_resets_per_billing_cycle_period(): void
    {
        $agent = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $agent->assignRole('user');
        $this->walletService->credit($agent, 1000.00, 'test_seed');

        $plan = $this->planService->createPlan([
            'name' => 'Monthly Quota Test Plan',
            'base_price' => 299.00,
            'included_mrus' => 3,
            'included_consumers' => 500,
            'extra_mru_rate' => 15.00,
            'extra_consumer_rate' => 0.15,
            'is_active' => true,
        ], [
            ['duration_months' => 3, 'discount_percent' => 0, 'final_price' => 897.00],
        ]);

        $this->actingAs($agent)->postJson(route('subscription.subscribe_wallet'), [
            'plan_id' => $plan->id,
            'duration_id' => $plan->durations->first()->id,
        ]);

        $mru = Mru::create([
            'user_id' => $agent->id,
            'code' => 'NISARBHATI-0244',
            'name' => 'NISARBHATI-0244',
            'status' => 'active',
        ]);

        // Consume 400 consumers in August 2026 (Month 8) -> 100 remaining for Month 8
        $this->consumerQuotaService->consumeConsumerQuota($agent, $mru, 8, 2026, 400);
        $this->assertEquals(100, $this->consumerQuotaService->getRemainingConsumerQuotaForPeriod($agent, 8, 2026));

        // In September 2026 (Month 9), the agent has full fresh 500 included quota!
        $this->assertEquals(500, $this->consumerQuotaService->getRemainingConsumerQuotaForPeriod($agent, 9, 2026));
    }

    /**
     * Test mid-cycle plan upgrade immediately expands quotas and avoids overage fees for newly permitted MRUs.
     */
    public function test_plan_upgrade_expands_mru_and_consumer_quotas_without_overage(): void
    {
        $agent = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $agent->assignRole('user');
        $this->walletService->credit($agent, 2000.00, 'test_seed');

        $starterPlan = $this->planService->createPlan([
            'name' => 'Starter 2 MRU',
            'base_price' => 200.00,
            'included_mrus' => 2,
            'included_consumers' => 500,
            'extra_mru_rate' => 25.00,
            'extra_consumer_rate' => 0.25,
            'is_active' => true,
        ], [
            ['duration_months' => 1, 'discount_percent' => 0, 'final_price' => 200.00],
        ]);

        $proPlan = $this->planService->createPlan([
            'name' => 'Pro 10 MRU',
            'base_price' => 800.00,
            'included_mrus' => 10,
            'included_consumers' => 3000,
            'extra_mru_rate' => 15.00,
            'extra_consumer_rate' => 0.15,
            'is_active' => true,
        ], [
            ['duration_months' => 1, 'discount_percent' => 0, 'final_price' => 800.00],
        ]);

        // Subscribe to Starter
        $this->actingAs($agent)->postJson(route('subscription.subscribe_wallet'), [
            'plan_id' => $starterPlan->id,
            'duration_id' => $starterPlan->durations->first()->id,
        ]);

        // Create 2 MRUs -> full Starter quota used
        Mru::create(['user_id' => $agent->id, 'code' => 'MRU-1', 'name' => 'MRU-1', 'status' => 'active']);
        Mru::create(['user_id' => $agent->id, 'code' => 'MRU-2', 'name' => 'MRU-2', 'status' => 'active']);
        $this->assertEquals(0, $this->mruQuotaService->checkMruQuotaAvailable($agent));

        // Upgrade to Pro Plan via PlanChangeService
        $planChangeService = app(\App\Services\Billing\PlanChangeService::class);
        $planChangeService->upgradePlan($agent->fresh()->activeSubscription, $proPlan, $proPlan->durations->first());

        // Quota immediately expands: 10 - 2 = 8 available MRU slots!
        $this->assertEquals(8, $this->mruQuotaService->checkMruQuotaAvailable($agent));

        // 3rd MRU can now be created without any overage pay-gate!
        $resp3 = $this->actingAs($agent)->postJson(route('mrus.store'), [
            'code' => 'MRU-3',
            'name' => 'MRU-3',
            'pay_overage' => false,
        ]);
        $resp3->assertStatus(201);
        $this->assertDatabaseHas('mrus', ['user_id' => $agent->id, 'code' => 'MRU-3', 'is_over_quota' => false]);
    }
}
