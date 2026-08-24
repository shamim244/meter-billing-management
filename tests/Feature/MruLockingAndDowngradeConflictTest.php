<?php

namespace Tests\Feature;

use App\Models\AgentSubscription;
use App\Models\Mru;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\User;
use App\Services\Plan\PlanService;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MruLockingAndDowngradeConflictTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\NotificationSystemSeeder::class);
    }

    public function test_agent_can_lock_and_unlock_their_active_mru(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0244',
            'name' => 'NISARBHATI',
            'status' => 'active',
        ]);

        // 1. Lock MRU
        $response = $this->actingAs($user)->postJson("/mrus/{$mru->id}/lock", [
            'reason' => 'user_manual_lock',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertEquals('locked', $mru->fresh()->status);
        $this->assertEquals('user_manual_lock', $mru->fresh()->locked_reason);

        // 2. Unlock MRU
        $unlockResp = $this->actingAs($user)->postJson("/mrus/{$mru->id}/unlock");
        $unlockResp->assertStatus(200);
        $this->assertEquals('active', $mru->fresh()->status);
    }

    public function test_agent_cannot_lock_another_agents_mru(): void
    {
        $user1 = User::factory()->create(['status' => 'active']);
        $user2 = User::factory()->create(['status' => 'active']);

        $mruUser1 = Mru::create([
            'user_id' => $user1->id,
            'code' => '0244',
            'name' => 'NISARBHATI',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user2)->postJson("/mrus/{$mruUser1->id}/lock");
        // Scoped by BelongsToUser global trait, returns 404 not found across user boundaries
        $response->assertStatus(404);
    }

    public function test_downgrade_returns_ineligible_mrus_payload_and_succeeds_after_locking_excess_mru(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        app(WalletService::class)->credit($user, 5000.00, 'test_topup');

        // Create Pro Plan (includes 3 MRUs)
        $proPlan = Plan::create([
            'name' => 'Pro Plan',
            'included_mrus' => 3,
            'included_consumers' => 5000,
            'base_price' => 1000.00,
            'extra_mru_rate' => 20.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ]);
        $proDuration = $proPlan->durations()->create([
            'duration_unit' => 'month',
            'duration_value' => 1,
            'final_price' => 1000.00,
            'is_active' => true,
        ]);

        // Create Starter Plan (includes 2 MRUs)
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

        // Subscribe user to Pro Plan
        app(PlanService::class)->subscribeAgent($user, $proPlan, $proDuration);

        // User creates 3 active MRUs
        $mru1 = Mru::create(['user_id' => $user->id, 'code' => '001', 'name' => 'Zone 1', 'status' => 'active']);
        $mru2 = Mru::create(['user_id' => $user->id, 'code' => '002', 'name' => 'Zone 2', 'status' => 'active']);
        $mru3 = Mru::create(['user_id' => $user->id, 'code' => '003', 'name' => 'Zone 3', 'status' => 'active']);

        // User attempts to downgrade to Starter (which allows 2 MRUs)
        $downgradeAttempt = $this->actingAs($user)->postJson('/subscription/subscribe-wallet', [
            'plan_id' => $starterPlan->id,
            'duration_id' => $starterDuration->id,
        ]);

        $downgradeAttempt->assertStatus(422);
        $downgradeAttempt->assertJson([
            'success' => false,
            'ineligible_mrus' => true,
            'excess_mrus' => 1,
            'new_plan_quota' => 2,
        ]);

        // User locks 1 excess MRU (Zone 3)
        $lockResp = $this->actingAs($user)->postJson("/mrus/{$mru3->id}/lock", [
            'reason' => 'plan_downgrade',
        ]);
        $lockResp->assertStatus(200);
        $this->assertEquals('locked', $mru3->fresh()->status);

        // Retry downgrade after locking excess MRU -> Success!
        $retryDowngrade = $this->actingAs($user)->postJson('/subscription/subscribe-wallet', [
            'plan_id' => $starterPlan->id,
            'duration_id' => $starterDuration->id,
        ]);

        $retryDowngrade->assertStatus(200);
        $retryDowngrade->assertJson([
            'success' => true,
        ]);

        $this->assertEquals($starterPlan->id, $user->fresh()->subscriptions()->latest('id')->first()->plan_id);
    }
}
