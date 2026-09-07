<?php

namespace Tests\Feature;

use App\Models\AgentSubscription;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\Plan;
use App\Models\User;
use App\Services\Plan\PlanService;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewUserFreePlanOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $freePlan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->freePlan = Plan::where('name', 'Free Starter')->firstOrFail();
    }

    public function test_new_user_registration_auto_subscribes_to_free_starter_plan(): void
    {
        $response = $this->post('/register', [
            'name' => 'John Operator',
            'email' => 'john.operator@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'john.operator@example.com')->first();
        $this->assertNotNull($user);

        $sub = $user->activeSubscription;
        $this->assertNotNull($sub, 'User should have an auto-activated active subscription');
        $this->assertEquals($this->freePlan->id, $sub->plan_id);
        $this->assertEquals('Free Starter', $sub->plan->name);
        $this->assertEquals(1, $sub->included_mrus_locked);
        $this->assertEquals(500, $sub->included_consumers_locked);
        $this->assertTrue($sub->billing_end->isFuture());
    }

    public function test_user_without_subscription_gets_requires_subscription_and_redirect_url_on_cycle_creation(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('user');

        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => 'TEST_01',
            'name' => 'Test Area 01',
            'status' => 'active',
        ]);

        ConsumerAccount::create([
            'mru_id' => $mru->id,
            'user_id' => $user->id,
            'ca_number' => '102300000001',
            'consumer_name' => 'Test Consumer',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson('/mrus/billing-cycle', [
            'mru_id' => $mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
            'action_type' => 'create_only',
        ]);

        $response->assertStatus(402);
        $response->assertJson([
            'success' => false,
            'requires_subscription' => true,
            'redirect_url' => route('user-panel.subscription'),
        ]);
        $this->assertStringContainsString('subscription plan is required', $response->json('message'));
    }

    public function test_user_can_activate_free_starter_plan_in_one_click_without_wallet_funds(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('user');

        // Ensure wallet balance is 0
        $this->assertEquals(0.0, (float) $user->balanceFloat);
        $this->assertNull($user->activeSubscription);

        $duration = $this->freePlan->durations()->where('is_active', true)->firstOrFail();

        $response = $this->actingAs($user)->postJson('/subscription/subscribe-wallet', [
            'plan_id' => $this->freePlan->id,
            'duration_id' => $duration->id,
            'action_mode' => 'new',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $user->refresh();
        $this->assertNotNull($user->activeSubscription);
        $this->assertEquals($this->freePlan->id, $user->activeSubscription->plan_id);
        $this->assertEquals(0.0, (float) $user->balanceFloat);
    }

    public function test_mru_creation_auto_activates_free_plan_if_user_has_no_active_plan(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('user');

        $this->assertNull($user->activeSubscription);

        $response = $this->actingAs($user)->postJson('/mrus', [
            'code' => 'AUTO_FREE_01',
            'name' => 'Auto Free Workspace',
        ]);

        $response->assertStatus(201);
        $user->refresh();

        $this->assertNotNull($user->activeSubscription, 'Free plan should auto-activate on first MRU creation if no plan was active');
        $this->assertEquals($this->freePlan->id, $user->activeSubscription->plan_id);

        $this->assertDatabaseHas('mrus', [
            'user_id' => $user->id,
            'code' => 'AUTO_FREE_01',
        ]);
    }
}
