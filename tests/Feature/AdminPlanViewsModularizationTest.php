<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Services\Plan\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPlanViewsModularizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $operator;

    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'email' => 'admin_plans_view@nbpdcl-saas.com',
            'status' => 'active',
        ]);
        $this->admin->assignRole($adminRole);

        $this->operator = User::factory()->create([
            'email' => 'operator_plans_view@nbpdcl-saas.com',
            'status' => 'active',
        ]);
        $this->operator->assignRole($userRole);

        $this->plan = app(PlanService::class)->createPlan([
            'name' => 'Modular Pro Tier',
            'base_price' => 599.00,
            'included_mrus' => 4,
            'included_consumers' => 3000,
            'extra_mru_rate' => 25.00,
            'extra_consumer_rate' => 0.25,
            'description' => 'A test modular plan',
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_from_plan_create_and_edit(): void
    {
        $this->get(route('admin.plans.create'))->assertRedirect('/login');
        $this->get(route('admin.plans.edit', $this->plan))->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_plan_create_or_edit(): void
    {
        $this->actingAs($this->operator)->get(route('admin.plans.create'))->assertForbidden();
        $this->actingAs($this->operator)->get(route('admin.plans.edit', $this->plan))->assertForbidden();
    }

    public function test_admin_can_view_plan_create_page_with_modular_partials_and_js(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.plans.create'));

        $response->assertOk()
            ->assertSeeText('Create Subscription Plan')
            ->assertSeeText('1. Plan Details & Included Quotas')
            ->assertSeeText('2. Base Price & Overage Rates')
            ->assertSeeText('3. Duration Pricing Table')
            ->assertSee('plan-form-app.js');
    }

    public function test_admin_can_view_plan_edit_page_with_modular_partials_and_js(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.plans.edit', $this->plan));

        $response->assertOk()
            ->assertSeeText('Edit Subscription Plan: Modular Pro Tier')
            ->assertSeeText('Important Plan Edit Invariant')
            ->assertSeeText('1. Plan Details & Included Quotas')
            ->assertSeeText('2. Base Price & Overage Rates')
            ->assertSeeText('3. Duration Pricing Table')
            ->assertSee('adminPlanConfig')
            ->assertSee('plan-form-app.js');
    }
}
