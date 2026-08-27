<?php

namespace Tests\Feature;

use App\Models\AgentSubscription;
use App\Models\Mru;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\User;
use App\Services\Plan\PlanService;
use App\Services\Wallet\WalletService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionLifecycleExtensionAndPlanChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\NotificationSystemSeeder::class);
    }

    public function test_purchasing_same_plan_again_extends_billing_end_without_wiping_remaining_days(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        app(WalletService::class)->credit($user, 5000.00, 'test_topup');

        $plan = Plan::create([
            'name' => 'Starter Plan',
            'included_mrus' => 2,
            'included_consumers' => 2500,
            'base_price' => 500.00,
            'extra_mru_rate' => 20.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ]);
        $duration = $plan->durations()->create([
            'duration_unit' => 'month',
            'duration_value' => 1,
            'final_price' => 500.00,
            'is_active' => true,
        ]);

        // 1. Initial 1-month subscription
        $sub1 = app(PlanService::class)->subscribeAgent($user, $plan, $duration);
        $initialBillingEnd = $sub1->billing_end->copy();

        $this->assertTrue($initialBillingEnd->isFuture());

        // 2. User purchases same plan again to extend for 1 more month
        $response = $this->actingAs($user)->postJson('/subscription/subscribe-wallet', [
            'plan_id' => $plan->id,
            'duration_id' => $duration->id,
        ]);

        $response->assertStatus(200);

        // 3. Verify that billing_end was extended from the initialBillingEnd by exactly 1 month
        $activeSub = $user->fresh()->activeSubscription;
        $this->assertNotNull($activeSub);
        $this->assertEquals($sub1->id, $activeSub->id);
        
        $expectedExtendedEnd = $duration->calculateBillingEnd($initialBillingEnd);
        $this->assertEquals($expectedExtendedEnd->timestamp, $activeSub->billing_end->timestamp);

        // 4. Verify base_price_paid holds current duration final price and wallet debited ₹500 for extension
        $this->assertEquals(500.00, (float) $activeSub->base_price_paid);
        $this->assertEquals(4500.00, (float) app(WalletService::class)->getBalance($user));
    }

    public function test_downgrading_from_3_month_plan_to_7_day_plan_sets_7_day_lifecycle_and_refunds_credit(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        app(WalletService::class)->credit($user, 10000.00, 'test_topup');

        // Pro Plan (3-Month: ₹3,000)
        $proPlan = Plan::create([
            'name' => 'Business Pro',
            'included_mrus' => 10,
            'included_consumers' => 10000,
            'base_price' => 1000.00,
            'extra_mru_rate' => 20.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ]);
        $pro3mDuration = $proPlan->durations()->create([
            'duration_unit' => 'month',
            'duration_value' => 3,
            'final_price' => 3000.00,
            'is_active' => true,
        ]);

        // Starter Plan (7-Day: ₹100)
        $starterPlan = Plan::create([
            'name' => 'Starter 7-Day',
            'included_mrus' => 2,
            'included_consumers' => 1000,
            'base_price' => 400.00,
            'extra_mru_rate' => 20.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ]);
        $starter7dDuration = $starterPlan->durations()->create([
            'duration_unit' => 'day',
            'duration_value' => 7,
            'final_price' => 100.00,
            'is_active' => true,
        ]);

        // 1. User subscribes to 3-month Pro plan
        $proSub = app(PlanService::class)->subscribeAgent($user, $proPlan, $pro3mDuration);
        
        // Simulate 30 days elapsed out of 90 days (60 days remaining: unused credit = 60/90 * 3000 = ₹2,000)
        $proSub->update([
            'billing_start' => now()->subDays(30),
            'billing_end' => now()->addDays(60),
        ]);

        $walletBefore = (float) app(WalletService::class)->getBalance($user);

        // 2. User downgrades to 7-day Starter plan
        $response = $this->actingAs($user)->postJson('/subscription/subscribe-wallet', [
            'plan_id' => $starterPlan->id,
            'duration_id' => $starter7dDuration->id,
        ]);

        $response->assertStatus(200);

        // 3. Verify previous 3-month subscription is marked downgraded
        $this->assertEquals('downgraded', $proSub->fresh()->status);

        // 4. Verify new subscription is active and valid for EXACTLY 7 days from now
        $newActiveSub = $user->fresh()->activeSubscription;
        $this->assertNotNull($newActiveSub);
        $this->assertEquals($starterPlan->id, $newActiveSub->plan_id);
        $this->assertEquals('day', $newActiveSub->duration_unit);
        $this->assertEquals(7, $newActiveSub->duration_value);
        $this->assertEquals(7, (int) round(now()->floatDiffInDays($newActiveSub->billing_end)));

        // 5. Verify wallet received prorated credit (Old Credit ₹2,000 - New 7d Price ₹100 = +₹1,900)
        $walletAfter = (float) app(WalletService::class)->getBalance($user);
        $this->assertEquals($walletBefore + 1900.00, $walletAfter);
    }

    public function test_upgrading_from_1_month_to_3_month_plan_sets_3_month_lifecycle_and_charges_net_difference(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        app(WalletService::class)->credit($user, 10000.00, 'test_topup');

        // Starter Plan (1-Month: ₹500)
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

        // Pro Plan (3-Month: ₹3,000)
        $proPlan = Plan::create([
            'name' => 'Business Pro',
            'included_mrus' => 10,
            'included_consumers' => 10000,
            'base_price' => 1000.00,
            'extra_mru_rate' => 20.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ]);
        $pro3mDuration = $proPlan->durations()->create([
            'duration_unit' => 'month',
            'duration_value' => 3,
            'final_price' => 3000.00,
            'is_active' => true,
        ]);

        // 1. User subscribes to Starter Plan
        $starterSub = app(PlanService::class)->subscribeAgent($user, $starterPlan, $starterDuration);

        // Simulate 15 days elapsed out of 30 days (15 days remaining: unused credit = 15/30 * 500 = ₹250)
        $starterSub->update([
            'billing_start' => now()->subDays(15),
            'billing_end' => now()->addDays(15),
        ]);

        $walletBefore = (float) app(WalletService::class)->getBalance($user);

        // 2. User upgrades to 3-month Pro plan
        $response = $this->actingAs($user)->postJson('/subscription/subscribe-wallet', [
            'plan_id' => $proPlan->id,
            'duration_id' => $pro3mDuration->id,
        ]);

        $response->assertStatus(200);

        // 3. Verify old subscription is marked upgraded
        $this->assertEquals('upgraded', $starterSub->fresh()->status);

        // 4. Verify new subscription is active for 3 full months starting from now
        $newActiveSub = $user->fresh()->activeSubscription;
        $this->assertNotNull($newActiveSub);
        $this->assertEquals($proPlan->id, $newActiveSub->plan_id);
        $this->assertEquals(3, $newActiveSub->duration_value);
        $this->assertEquals('month', $newActiveSub->duration_unit);

        // 5. Verify net charge: Pro 3m (₹3,000) - Unused Starter (₹250) = ₹2,750 debited
        $walletAfter = (float) app(WalletService::class)->getBalance($user);
        $this->assertEquals($walletBefore - 2750.00, $walletAfter);
    }

    public function test_quota_services_permit_active_quota_during_renewal_due_and_grace_period(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $plan = Plan::create([
            'name' => 'Grace Test Plan',
            'included_mrus' => 5,
            'included_consumers' => 5000,
            'base_price' => 500.00,
            'extra_mru_rate' => 20.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ]);
        $duration = $plan->durations()->create([
            'duration_unit' => 'month',
            'duration_value' => 1,
            'final_price' => 500.00,
            'is_active' => true,
        ]);

        $sub = app(PlanService::class)->subscribeAgent($user, $plan, $duration);

        // Transition to renewal_due (billing_end has passed, but lifecycle_status is renewal_due)
        $sub->update([
            'billing_end' => now()->subMinutes(5),
            'lifecycle_status' => 'renewal_due',
        ]);

        $mruQuotaService = app(\App\Services\Plan\MruQuotaService::class);
        $consumerQuotaService = app(\App\Services\Plan\ConsumerQuotaService::class);

        // Verify quota services still recognize the active subscription during renewal_due
        $activeSub = $mruQuotaService->getActiveSubscription($user);
        $this->assertNotNull($activeSub);
        $this->assertEquals($sub->id, $activeSub->id);
        $this->assertEquals(5, $mruQuotaService->checkMruQuotaAvailable($user));

        // Transition to grace_period
        $sub->update([
            'lifecycle_status' => 'grace_period',
            'grace_period_ends_at' => now()->addDays(3),
        ]);

        $activeSubGrace = $consumerQuotaService->getActiveSubscription($user);
        $this->assertNotNull($activeSubGrace);
        $this->assertEquals($sub->id, $activeSubGrace->id);

        // Once suspended, getActiveSubscription MUST return null
        $sub->update([
            'lifecycle_status' => 'suspended',
            'suspended_at' => now(),
        ]);

        $this->assertNull($mruQuotaService->getActiveSubscription($user));
        $this->assertNull($consumerQuotaService->getActiveSubscription($user));
    }

    public function test_exact_boundary_second_renewal_extends_subscription(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        app(WalletService::class)->credit($user, 5000.00, 'test_topup');

        $plan = Plan::create([
            'name' => 'Boundary Plan',
            'included_mrus' => 2,
            'included_consumers' => 2000,
            'base_price' => 300.00,
            'extra_mru_rate' => 20.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ]);
        $duration = $plan->durations()->create([
            'duration_unit' => 'month',
            'duration_value' => 1,
            'final_price' => 300.00,
            'is_active' => true,
        ]);

        $sub = app(PlanService::class)->subscribeAgent($user, $plan, $duration);
        $now = now();
        $sub->update(['billing_end' => $now]);

        // Exact second boundary renewal
        $extendedSub = app(PlanService::class)->subscribeAgent($user, $plan, $duration);

        $this->assertEquals($sub->id, $extendedSub->id);
        $this->assertTrue($extendedSub->billing_end->isFuture());
    }

    public function test_grace_period_subscription_rendered_in_user_panel_and_renewable(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        app(WalletService::class)->credit($user, 5000.00, 'test_topup');

        $plan = Plan::create([
            'name' => 'Grace Recovery Plan',
            'included_mrus' => 2,
            'included_consumers' => 2000,
            'base_price' => 500.00,
            'extra_mru_rate' => 20.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ]);
        $duration = $plan->durations()->create([
            'duration_unit' => 'month',
            'duration_value' => 1,
            'final_price' => 500.00,
            'is_active' => true,
        ]);

        $sub = app(PlanService::class)->subscribeAgent($user, $plan, $duration);

        // Put into grace period (expired 1 hour ago)
        $sub->update([
            'billing_end' => now()->subHour(),
            'lifecycle_status' => 'grace_period',
            'grace_period_ends_at' => now()->addDays(3),
        ]);

        // 1. Verify UserPanelController::subscription view receives activeSubscription
        $response = $this->actingAs($user)->get(route('user-panel.subscription'));
        $response->assertStatus(200);
        $response->assertViewHas('activeSubscription', function ($activeSub) use ($sub) {
            return $activeSub && $activeSub->id === $sub->id && $activeSub->lifecycle_status === 'grace_period';
        });

        // 2. Verify SubscriptionCheckoutController quote recognizes it as extend
        $quoteRes = $this->actingAs($user)->getJson(route('subscription.quote', ['plan' => $plan->id, 'duration' => $duration->id]));
        $quoteRes->assertStatus(200);
        $quoteRes->assertJson([
            'success' => true,
            'action_type' => 'extend',
            'action_mode' => 'extend',
        ]);

        // 3. Renew from wallet during grace period
        $renewRes = $this->actingAs($user)->postJson('/subscription/subscribe-wallet', [
            'plan_id' => $plan->id,
            'duration_id' => $duration->id,
            'action_mode' => 'extend',
        ]);
        $renewRes->assertStatus(200);

        // 4. Verify subscription recovered to active lifecycle and extended from now()
        $recoveredSub = $user->fresh()->activeSubscription;
        $this->assertNotNull($recoveredSub);
        $this->assertEquals($sub->id, $recoveredSub->id);
        $this->assertEquals('active', $recoveredSub->lifecycle_status);
        $this->assertNull($recoveredSub->grace_period_ends_at);
        $this->assertTrue($recoveredSub->billing_end->isFuture());
    }

    public function test_user_can_shift_same_plan_duration_with_prorated_balance_adjustment(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        app(WalletService::class)->credit($user, 5000.00, 'test_topup');

        $plan = Plan::create([
            'name' => 'Starter Plan',
            'included_mrus' => 2,
            'included_consumers' => 2500,
            'base_price' => 500.00,
            'extra_mru_rate' => 20.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ]);
        $duration1m = $plan->durations()->create([
            'duration_unit' => 'month',
            'duration_value' => 1,
            'final_price' => 500.00,
            'is_active' => true,
        ]);
        $duration3m = $plan->durations()->create([
            'duration_unit' => 'month',
            'duration_value' => 3,
            'final_price' => 1200.00,
            'is_active' => true,
        ]);

        // Initial 1-month subscription
        $sub1 = app(PlanService::class)->subscribeAgent($user, $plan, $duration1m);
        // Simulate 15 days elapsed out of 30 (15 remaining days = ₹250 unused credit)
        $sub1->update([
            'billing_start' => now()->subDays(15),
            'billing_end' => now()->addDays(15),
        ]);

        $walletBefore = (float) app(WalletService::class)->getBalance($user);

        // User chooses to SHIFT (not extend) to 3-month cycle with balance adjustment
        $response = $this->actingAs($user)->postJson('/subscription/subscribe-wallet', [
            'plan_id' => $plan->id,
            'duration_id' => $duration3m->id,
            'action_mode' => 'shift',
        ]);

        $response->assertStatus(200);

        // Verify previous 1-month subscription is marked upgraded
        $this->assertEquals('upgraded', $sub1->fresh()->status);

        // Verify new 3-month subscription starts NOW and is valid for 3 full months
        $activeSub = $user->fresh()->activeSubscription;
        $this->assertNotNull($activeSub);
        $this->assertNotEquals($sub1->id, $activeSub->id);
        $this->assertEquals(3, $activeSub->duration_value);
        $this->assertEquals('month', $activeSub->duration_unit);
        $this->assertEquals(1200.00, (float) $activeSub->base_price_paid);

        // Verify net amount charged was ₹950 (₹1,200 New 3M - ₹250 Unused 1M)
        $walletAfter = (float) app(WalletService::class)->getBalance($user);
        $this->assertEquals($walletBefore - 950.00, $walletAfter);
    }

    public function test_user_can_extend_same_plan_different_duration_by_stacking_validity_without_proration(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        app(WalletService::class)->credit($user, 5000.00, 'test_topup');

        $plan = Plan::create([
            'name' => 'Starter Plan',
            'included_mrus' => 2,
            'included_consumers' => 2500,
            'base_price' => 500.00,
            'extra_mru_rate' => 20.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ]);
        $duration1m = $plan->durations()->create([
            'duration_unit' => 'month',
            'duration_value' => 1,
            'final_price' => 500.00,
            'is_active' => true,
        ]);
        $duration3m = $plan->durations()->create([
            'duration_unit' => 'month',
            'duration_value' => 3,
            'final_price' => 1200.00,
            'is_active' => true,
        ]);

        // Initial 1-month subscription expiring in 15 days
        $sub1 = app(PlanService::class)->subscribeAgent($user, $plan, $duration1m);
        $existingEnd = now()->addDays(15);
        $sub1->update([
            'billing_start' => now()->subDays(15),
            'billing_end' => $existingEnd->copy(),
        ]);

        $walletBefore = (float) app(WalletService::class)->getBalance($user);

        // User chooses to EXTEND (+3 Months added to end of existing validity)
        $response = $this->actingAs($user)->postJson('/subscription/subscribe-wallet', [
            'plan_id' => $plan->id,
            'duration_id' => $duration3m->id,
            'action_mode' => 'extend',
        ]);

        $response->assertStatus(200);

        // Verify existing subscription remains active and billing_end extended by 3 months onto $existingEnd
        $activeSub = $user->fresh()->activeSubscription;
        $this->assertNotNull($activeSub);
        $this->assertEquals($sub1->id, $activeSub->id);
        $this->assertEquals('active', $activeSub->status);
        
        $expectedNewEnd = $duration3m->calculateBillingEnd($existingEnd);
        $this->assertEquals($expectedNewEnd->timestamp, $activeSub->billing_end->timestamp);

        // Verify FULL ₹1,200 was charged (No proration subtracted on extension)
        $walletAfter = (float) app(WalletService::class)->getBalance($user);
        $this->assertEquals($walletBefore - 1200.00, $walletAfter);
    }

    public function test_user_can_shift_same_plan_duration_down_with_prorated_credit_refund(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        app(WalletService::class)->credit($user, 5000.00, 'test_topup');

        $plan = Plan::create([
            'name' => 'Starter Plan',
            'included_mrus' => 2,
            'included_consumers' => 2500,
            'base_price' => 500.00,
            'extra_mru_rate' => 20.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ]);
        $duration7d = $plan->durations()->create([
            'duration_unit' => 'day',
            'duration_value' => 7,
            'final_price' => 100.00,
            'is_active' => true,
        ]);
        $duration3m = $plan->durations()->create([
            'duration_unit' => 'month',
            'duration_value' => 3,
            'final_price' => 1200.00,
            'is_active' => true,
        ]);

        // User has 3-month subscription with 75 days remaining out of 90 (Unused credit = 75/90 * 1200 = ₹1,000)
        $sub3m = app(PlanService::class)->subscribeAgent($user, $plan, $duration3m);
        $sub3m->update([
            'billing_start' => now()->subDays(15),
            'billing_end' => now()->addDays(75),
            'base_price_paid' => 1200.00,
        ]);

        $walletBefore = (float) app(WalletService::class)->getBalance($user);

        // User shifts down to 7-Day option
        $response = $this->actingAs($user)->postJson('/subscription/subscribe-wallet', [
            'plan_id' => $plan->id,
            'duration_id' => $duration7d->id,
            'action_mode' => 'shift',
        ]);

        $response->assertStatus(200);

        // Verify previous 3-month subscription is marked downgraded
        $this->assertEquals('downgraded', $sub3m->fresh()->status);

        // Verify new 7-day subscription starts NOW
        $activeSub = $user->fresh()->activeSubscription;
        $this->assertNotNull($activeSub);
        $this->assertEquals('day', $activeSub->duration_unit);
        $this->assertEquals(7, $activeSub->duration_value);
        $this->assertEquals(7, (int) round(now()->floatDiffInDays($activeSub->billing_end)));

        // Verify wallet received prorated credit of ₹900 (Old Credit ₹1,000 - New 7d Price ₹100 = +₹900)
        $walletAfter = (float) app(WalletService::class)->getBalance($user);
        $this->assertEquals($walletBefore + 900.00, $walletAfter);
    }
}
