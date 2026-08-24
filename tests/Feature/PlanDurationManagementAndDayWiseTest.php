<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\User;
use App\Services\Plan\PlanService;
use App\Services\Wallet\WalletService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanDurationManagementAndDayWiseTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;
    protected PlanService $planService;
    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planService = app(PlanService::class);
        $this->walletService = app(WalletService::class);

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'email' => 'admin@nbpdcl.test',
        ]);
        $this->admin->assignRole('admin');

        $this->agent = User::factory()->create([
            'email' => 'agent@nbpdcl.test',
        ]);
        $this->agent->assignRole('user');

        $this->walletService->credit(
            user: $this->agent,
            amount: 2000.0,
            source: 'initial_seed',
            description: 'Test Wallet Balance'
        );
    }

    public function test_admin_can_view_dedicated_plan_durations_page(): void
    {
        $plan = $this->planService->createPlan([
            'name' => 'Custom Tier',
            'included_mrus' => 5,
            'included_consumers' => 1000,
            'extra_mru_rate' => 20.0,
            'extra_consumer_rate' => 0.20,
            'base_price' => 500.0,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.plans.durations.index', $plan));

        $response->assertStatus(200);
        $response->assertSee('Duration Tiers');
        $response->assertSee('Custom Tier');
        $response->assertSee('Configured Validity Tiers');
    }

    public function test_admin_can_create_day_wise_and_month_wise_durations(): void
    {
        $plan = $this->planService->createPlan([
            'name' => 'Flex Plan',
            'included_mrus' => 2,
            'included_consumers' => 500,
            'extra_mru_rate' => 25.0,
            'extra_consumer_rate' => 0.25,
            'base_price' => 300.0,
        ], []);

        // 1. Add 7 Days Trial duration
        $resDay = $this->actingAs($this->admin)->post(route('admin.plans.durations.store', $plan), [
            'duration_unit' => 'day',
            'duration_value' => 7,
            'name' => '7 Days Starter Trial',
            'discount_percent' => 0,
            'final_price' => 70.0,
            'is_active' => 1,
        ]);

        $resDay->assertRedirect(route('admin.plans.durations.index', $plan));
        $this->assertDatabaseHas('plan_durations', [
            'plan_id' => $plan->id,
            'duration_unit' => 'day',
            'duration_value' => 7,
            'name' => '7 Days Starter Trial',
            'final_price' => 70.0,
            'is_active' => 1,
        ]);

        // 2. Add 24 Months Extended duration
        $resMonth = $this->actingAs($this->admin)->post(route('admin.plans.durations.store', $plan), [
            'duration_unit' => 'month',
            'duration_value' => 24,
            'name' => '2 Years Platinum Pass',
            'discount_percent' => 25.0,
            'final_price' => 5400.0,
            'is_active' => 1,
        ]);

        $resMonth->assertRedirect(route('admin.plans.durations.index', $plan));
        $this->assertDatabaseHas('plan_durations', [
            'plan_id' => $plan->id,
            'duration_unit' => 'month',
            'duration_value' => 24,
            'name' => '2 Years Platinum Pass',
            'final_price' => 5400.0,
            'discount_percent' => 25.0,
        ]);
    }

    public function test_admin_can_toggle_enable_disable_duration(): void
    {
        $plan = $this->planService->createPlan([
            'name' => 'Toggle Test Plan',
            'included_mrus' => 3,
            'included_consumers' => 500,
            'extra_mru_rate' => 20.0,
            'extra_consumer_rate' => 0.20,
            'base_price' => 400.0,
        ]);

        $duration = $plan->durations()->first();
        $this->assertTrue($duration->is_active);

        // Toggle to disabled
        $res = $this->actingAs($this->admin)->patch(route('admin.plans.durations.toggle', [$plan, $duration]));
        $res->assertSessionHas('success');
        $this->assertFalse($duration->fresh()->is_active);

        // Toggle back to active
        $res2 = $this->actingAs($this->admin)->patch(route('admin.plans.durations.toggle', [$plan, $duration]));
        $res2->assertSessionHas('success');
        $this->assertTrue($duration->fresh()->is_active);
    }

    public function test_admin_cannot_disable_or_delete_sole_remaining_active_duration(): void
    {
        $plan = $this->planService->createPlan([
            'name' => 'Sole Duration Plan',
            'included_mrus' => 1,
            'included_consumers' => 100,
            'extra_mru_rate' => 10.0,
            'extra_consumer_rate' => 0.10,
            'base_price' => 100.0,
        ], [
            ['duration_unit' => 'month', 'duration_value' => 1, 'discount_percent' => 0, 'final_price' => 100, 'is_active' => true]
        ]);

        $duration = $plan->durations()->first();

        // 1. Try to delete the only duration
        $resDelete = $this->actingAs($this->admin)->delete(route('admin.plans.durations.destroy', [$plan, $duration]));
        $resDelete->assertSessionHas('error');
        $this->assertDatabaseHas('plan_durations', ['id' => $duration->id]);

        // 2. Try to disable the only active duration
        $resToggle = $this->actingAs($this->admin)->patch(route('admin.plans.durations.toggle', [$plan, $duration]));
        $resToggle->assertSessionHas('error');
        $this->assertTrue($duration->fresh()->is_active);
    }

    public function test_subscribing_to_day_wise_duration_sets_exact_day_expiry(): void
    {
        Carbon::setTestNow('2026-08-24 10:00:00');

        $plan = $this->planService->createPlan([
            'name' => 'Weekly Trial Plan',
            'included_mrus' => 1,
            'included_consumers' => 100,
            'extra_mru_rate' => 10.0,
            'extra_consumer_rate' => 0.10,
            'base_price' => 200.0,
        ], [
            ['duration_unit' => 'day', 'duration_value' => 7, 'name' => '7-Day Pass', 'discount_percent' => 0, 'final_price' => 50.0, 'is_active' => true],
            ['duration_unit' => 'month', 'duration_value' => 1, 'discount_percent' => 0, 'final_price' => 200.0, 'is_active' => true],
        ]);

        $dayDuration = $plan->durations()->where('duration_unit', 'day')->where('duration_value', 7)->first();

        // Subscribe via wallet
        $sub = $this->planService->subscribeAgent($this->agent, $plan, $dayDuration);

        $this->assertEquals('active', $sub->status);
        $this->assertEquals('day', $sub->duration_unit);
        $this->assertEquals(7, $sub->duration_value);
        $this->assertEquals('2026-08-24 10:00:00', $sub->billing_start->toDateTimeString());
        $this->assertEquals('2026-08-31 10:00:00', $sub->billing_end->toDateTimeString()); // Exactly 7 days!
        $this->assertEquals('7 Days', $sub->formatted_duration);

        Carbon::setTestNow();
    }

    public function test_subscribing_to_month_wise_duration_sets_exact_month_expiry(): void
    {
        Carbon::setTestNow('2026-08-24 10:00:00');

        $plan = $this->planService->createPlan([
            'name' => 'Quarterly Plan',
            'included_mrus' => 5,
            'included_consumers' => 1000,
            'extra_mru_rate' => 20.0,
            'extra_consumer_rate' => 0.20,
            'base_price' => 500.0,
        ], [
            ['duration_unit' => 'month', 'duration_value' => 3, 'discount_percent' => 10, 'final_price' => 1350.0, 'is_active' => true],
        ]);

        $monthDuration = $plan->durations()->where('duration_unit', 'month')->where('duration_value', 3)->first();

        $sub = $this->planService->subscribeAgent($this->agent, $plan, $monthDuration);

        $this->assertEquals('active', $sub->status);
        $this->assertEquals('month', $sub->duration_unit);
        $this->assertEquals(3, $sub->duration_value);
        $this->assertEquals('2026-08-24 10:00:00', $sub->billing_start->toDateTimeString());
        $this->assertEquals('2026-11-24 10:00:00', $sub->billing_end->toDateTimeString()); // Exactly 3 months!

        Carbon::setTestNow();
    }

    public function test_user_subscription_page_only_renders_active_durations(): void
    {
        $plan = $this->planService->createPlan([
            'name' => 'Filtered Durations Plan',
            'included_mrus' => 2,
            'included_consumers' => 200,
            'extra_mru_rate' => 15.0,
            'extra_consumer_rate' => 0.15,
            'base_price' => 300.0,
        ], [
            ['duration_unit' => 'day', 'duration_value' => 15, 'name' => '15 Days Trial', 'discount_percent' => 0, 'final_price' => 150.0, 'is_active' => true],
            ['duration_unit' => 'month', 'duration_value' => 2, 'discount_percent' => 5, 'final_price' => 570.0, 'is_active' => false], // Disabled!
            ['duration_unit' => 'month', 'duration_value' => 6, 'discount_percent' => 15, 'final_price' => 1530.0, 'is_active' => true],
        ]);

        $response = $this->actingAs($this->agent)->get(route('user-panel.subscription'));

        $response->assertStatus(200);
        $response->assertSee('Filtered Durations Plan');
        $response->assertSee('15 Days Trial');
        $response->assertSee('1530');
        $response->assertDontSee('570'); // Disabled duration price (₹570) must NOT be passed or displayed
    }
}
