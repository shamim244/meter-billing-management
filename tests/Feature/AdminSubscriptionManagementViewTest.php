<?php

namespace Tests\Feature;

use App\Models\AgentSubscription;
use App\Models\Plan;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSubscriptionManagementViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $agentUser;

    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'email' => 'admin_sub_view@nbpdcl-saas.com',
            'status' => 'active',
        ]);
        $this->adminUser->assignRole($adminRole);

        $this->agentUser = User::factory()->create([
            'name' => 'Agent Alpha',
            'email' => 'agent_alpha@nbpdcl-saas.com',
            'status' => 'active',
        ]);
        $this->agentUser->assignRole($userRole);

        $this->plan = Plan::create([
            'name' => 'Standard Pro',
            'slug' => 'standard-pro',
            'base_price' => 499.00,
            'included_mrus' => 5,
            'included_consumers' => 1000,
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_from_admin_subscriptions(): void
    {
        $response = $this->get(route('admin.subscriptions.index'));
        $response->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_subscriptions(): void
    {
        $response = $this->actingAs($this->agentUser)->get(route('admin.subscriptions.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_subscriptions_index_with_modular_partials_and_js(): void
    {
        $subscription = AgentSubscription::create([
            'user_id' => $this->agentUser->id,
            'plan_id' => $this->plan->id,
            'duration_unit' => 'month',
            'duration_value' => 1,
            'duration_months' => 1,
            'base_price_paid' => 499.00,
            'included_mrus_locked' => 5,
            'included_consumers_locked' => 1000,
            'extra_mru_rate_locked' => 0.00,
            'extra_consumer_rate_locked' => 0.00,
            'lifecycle_status' => 'active',
            'status' => 'active',
            'billing_start' => now()->subDays(5),
            'billing_end' => now()->addDays(25),
            'auto_renewal_enabled' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.subscriptions.index'));

        $response->assertOk()
            ->assertSeeText('Subscriptions & Lifecycle State Machine')
            ->assertSeeText('Total Contracts')
            ->assertSeeText('Platform Grace Period Policy')
            ->assertSeeText('Agent Subscriptions Ledger')
            ->assertSeeText('Agent Alpha')
            ->assertSeeText('Standard Pro')
            ->assertSee('subscriptions-app.js');
    }

    public function test_admin_can_update_grace_period_settings(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.subscriptions.update_settings'), [
            'default_grace_period_days' => 14,
        ]);

        $response->assertRedirect();
        $this->assertEquals(14, (int) SystemSetting::get('billing_default_grace_period_days', 3));
    }
}
