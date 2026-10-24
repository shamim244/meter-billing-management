<?php

namespace Tests\Feature;

use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MruConsumerDuplicateStrategyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(PlanSeeder::class);
    }

    public function test_multi_agent_isolation_agent_b_cannot_affect_agent_a(): void
    {
        $userA = User::factory()->create(['status' => 'active']);
        $userB = User::factory()->create(['status' => 'active']);

        $mruA = Mru::create([
            'user_id' => $userA->id,
            'code' => '0471',
            'name' => 'Village A',
            'status' => 'active',
        ]);

        $mruB = Mru::create([
            'user_id' => $userB->id,
            'code' => '0472',
            'name' => 'Village B',
            'status' => 'active',
        ]);

        // Agent A adds consumer 10230057822
        $this->actingAs($userA)->post("/mrus/{$mruA->id}/consumers", [
            'ca_number' => '10230057822',
            'consumer_name' => 'Agent A Consumer',
            'meter_no' => 'MTR-A',
        ]);

        // Agent B imports the exact same CA 10230057822
        $this->actingAs($userB)->post("/mrus/{$mruB->id}/consumers/import", [
            'ca_data' => "10230057822\tAgent B Consumer\tDS-II\tOK\t500\tMTR-B",
            'duplicate_strategy' => 'keep_both',
        ]);

        // Assert Agent A's consumer is untouched
        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $userA->id,
            'mru_id' => $mruA->id,
            'ca_number' => '10230057822',
            'consumer_name' => 'Agent A Consumer',
            'meter_no' => 'MTR-A',
        ]);

        // Assert Agent B's consumer exists independently
        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $userB->id,
            'mru_id' => $mruB->id,
            'ca_number' => '10230057822',
            'consumer_name' => 'Agent B Consumer',
            'meter_no' => 'MTR-B',
        ]);

        // Agent B deletes their consumer
        $consumerB = ConsumerAccount::where('user_id', $userB->id)->where('ca_number', '10230057822')->first();
        $this->actingAs($userB)->delete("/mrus/{$mruB->id}/consumers/{$consumerB->id}");

        // Agent A's consumer is STILL intact
        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $userA->id,
            'mru_id' => $mruA->id,
            'ca_number' => '10230057822',
        ]);
        $this->assertDatabaseMissing('consumer_accounts', [
            'user_id' => $userB->id,
            'ca_number' => '10230057822',
        ]);
    }

    public function test_keep_both_strategy_allows_same_user_to_have_ca_in_multiple_mrus(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $mruX = Mru::create([
            'user_id' => $user->id,
            'code' => '0471',
            'name' => 'MRU X',
            'status' => 'active',
        ]);

        $mruY = Mru::create([
            'user_id' => $user->id,
            'code' => '0472',
            'name' => 'MRU Y',
            'status' => 'active',
        ]);

        // User adds consumer to MRU X
        $this->actingAs($user)->post("/mrus/{$mruX->id}/consumers", [
            'ca_number' => '10230057822',
            'consumer_name' => 'Ramesh Kumar',
            'meter_no' => '3808220',
        ]);

        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $user->id,
            'mru_id' => $mruX->id,
            'ca_number' => '10230057822',
        ]);

        // User imports same CA into MRU Y with default keep_both strategy
        $response = $this->actingAs($user)->post("/mrus/{$mruY->id}/consumers/import", [
            'ca_data' => "10230057822\n10230099999",
            'duplicate_strategy' => 'keep_both',
        ]);

        $response->assertRedirect();

        // CA 10230057822 MUST be in MRU X
        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $user->id,
            'mru_id' => $mruX->id,
            'ca_number' => '10230057822',
        ]);

        // CA 10230057822 MUST ALSO be in MRU Y
        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $user->id,
            'mru_id' => $mruY->id,
            'ca_number' => '10230057822',
        ]);

        // New CA 10230099999 is in MRU Y
        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $user->id,
            'mru_id' => $mruY->id,
            'ca_number' => '10230099999',
        ]);

        // Total consumer accounts for this user should be 3
        $this->assertDatabaseCount('consumer_accounts', 3);
        $this->assertEquals(1, $mruX->consumerAccounts()->count());
        $this->assertEquals(2, $mruY->consumerAccounts()->count());
    }

    public function test_transfer_strategy_moves_consumer_from_mru_x_to_mru_y(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $mruX = Mru::create([
            'user_id' => $user->id,
            'code' => '0471',
            'name' => 'MRU X',
            'status' => 'active',
        ]);

        $mruY = Mru::create([
            'user_id' => $user->id,
            'code' => '0472',
            'name' => 'MRU Y',
            'status' => 'active',
        ]);

        $this->actingAs($user)->post("/mrus/{$mruX->id}/consumers", [
            'ca_number' => '10230057822',
            'consumer_name' => 'Ramesh Kumar',
        ]);

        // Import into MRU Y with 'transfer'
        $response = $this->actingAs($user)->post("/mrus/{$mruY->id}/consumers/import", [
            'ca_data' => '10230057822',
            'duplicate_strategy' => 'transfer',
        ]);

        $response->assertRedirect();

        // Must be removed from MRU X and now in MRU Y
        $this->assertDatabaseMissing('consumer_accounts', [
            'user_id' => $user->id,
            'mru_id' => $mruX->id,
            'ca_number' => '10230057822',
        ]);
        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $user->id,
            'mru_id' => $mruY->id,
            'ca_number' => '10230057822',
        ]);
        $this->assertDatabaseCount('consumer_accounts', 1);
    }

    public function test_skip_strategy_preserves_consumer_in_mru_x_and_does_not_add_to_mru_y(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $mruX = Mru::create([
            'user_id' => $user->id,
            'code' => '0471',
            'name' => 'MRU X',
            'status' => 'active',
        ]);

        $mruY = Mru::create([
            'user_id' => $user->id,
            'code' => '0472',
            'name' => 'MRU Y',
            'status' => 'active',
        ]);

        $this->actingAs($user)->post("/mrus/{$mruX->id}/consumers", [
            'ca_number' => '10230057822',
            'consumer_name' => 'Ramesh Kumar',
        ]);

        // Import with 'skip' strategy: 1 existing, 1 new
        $response = $this->actingAs($user)->post("/mrus/{$mruY->id}/consumers/import", [
            'ca_data' => "10230057822\n10230099999",
            'duplicate_strategy' => 'skip',
        ]);

        $response->assertRedirect();

        // Existing CA remains in MRU X only
        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $user->id,
            'mru_id' => $mruX->id,
            'ca_number' => '10230057822',
        ]);
        $this->assertDatabaseMissing('consumer_accounts', [
            'user_id' => $user->id,
            'mru_id' => $mruY->id,
            'ca_number' => '10230057822',
        ]);

        // New CA is added to MRU Y
        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $user->id,
            'mru_id' => $mruY->id,
            'ca_number' => '10230099999',
        ]);

        $this->assertEquals(1, $mruX->consumerAccounts()->count());
        $this->assertEquals(1, $mruY->consumerAccounts()->count());
    }

    public function test_deleting_mru_y_preserves_bill_status_if_ca_also_belongs_to_mru_x(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $mruX = Mru::create([
            'user_id' => $user->id,
            'code' => '0471',
            'name' => 'MRU X',
            'status' => 'active',
        ]);

        $mruY = Mru::create([
            'user_id' => $user->id,
            'code' => '0472',
            'name' => 'MRU Y',
            'status' => 'active',
        ]);

        // Create consumer in both MRUs
        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mruX->id,
            'ca_number' => '10230057822',
            'consumer_name' => 'Dual Consumer',
        ]);
        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mruY->id,
            'ca_number' => '10230057822',
            'consumer_name' => 'Dual Consumer',
        ]);

        // Create a bill status for this CA
        BillStatus::create([
            'user_id' => $user->id,
            'ca_number' => '10230057822',
            'billing_month' => 8,
            'billing_year' => 2026,
            'status' => 'Submitted',
        ]);

        // Delete MRU Y
        $response = $this->actingAs($user)->delete("/mrus/{$mruY->id}");
        $response->assertRedirect();

        // BillStatus MUST still exist because CA 10230057822 still belongs to MRU X
        $this->assertDatabaseHas('bill_statuses', [
            'user_id' => $user->id,
            'ca_number' => '10230057822',
            'status' => 'Submitted',
        ]);

        // Consumer in MRU X must still exist
        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $user->id,
            'mru_id' => $mruX->id,
            'ca_number' => '10230057822',
        ]);
    }

    public function test_add_single_consumer_supports_keep_both_and_transfer(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $mruX = Mru::create([
            'user_id' => $user->id,
            'code' => '0471',
            'name' => 'MRU X',
            'status' => 'active',
        ]);

        $mruY = Mru::create([
            'user_id' => $user->id,
            'code' => '0472',
            'name' => 'MRU Y',
            'status' => 'active',
        ]);

        // Add to MRU X
        $this->actingAs($user)->post("/mrus/{$mruX->id}/consumers", [
            'ca_number' => '10230057822',
            'consumer_name' => 'Ramesh Kumar',
            'meter_no' => '3808220',
        ]);

        // Add to MRU Y with keep_both
        $response = $this->actingAs($user)->post("/mrus/{$mruY->id}/consumers", [
            'ca_number' => '10230057822',
            'duplicate_action' => 'keep_both',
        ]);
        $response->assertRedirect();

        // Should exist in both MRUs
        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $user->id,
            'mru_id' => $mruX->id,
            'ca_number' => '10230057822',
        ]);
        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $user->id,
            'mru_id' => $mruY->id,
            'ca_number' => '10230057822',
        ]);
        $this->assertDatabaseCount('consumer_accounts', 2);
    }
}
