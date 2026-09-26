<?php

namespace Tests\Feature;

use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\Plan;
use App\Models\User;
use App\Services\Plan\PlanService;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MruArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(PlanSeeder::class);
    }

    public function test_user_can_create_mru_workspace(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->post('/mrus', [
            'code' => '0477',
            'name' => 'Gerua',
            'full_identifier' => 'Gerua Sub-division',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('mrus', [
            'user_id' => $user->id,
            'code' => '0477',
            'name' => 'Gerua',
        ]);
    }

    public function test_user_can_add_consumer_to_mru_master_list(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0473',
            'name' => 'Hala',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post("/mrus/{$mru->id}/consumers", [
            'ca_number' => '10230046961',
            'consumer_name' => 'Ramesh Kumar',
            'meter_no' => '3808220',
            'mobile' => '9876543210',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230046961',
            'consumer_name' => 'Ramesh Kumar',
            'meter_no' => '3808220',
        ]);
    }

    public function test_user_can_bulk_import_cas_to_mru(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0014',
            'name' => 'Lalpur',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post("/mrus/{$mru->id}/consumers/import", [
            'ca_data' => "102300783538\n102300783541\n102300783542",
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('consumer_accounts', 3);
        $this->assertDatabaseHas('consumer_accounts', ['mru_id' => $mru->id, 'ca_number' => '102300783538']);
        $this->assertDatabaseHas('consumer_accounts', ['mru_id' => $mru->id, 'ca_number' => '102300783541']);
        $this->assertDatabaseHas('consumer_accounts', ['mru_id' => $mru->id, 'ca_number' => '102300783542']);
    }

    public function test_user_can_export_mru_master_consumer_list(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0477',
            'name' => 'Gerua',
            'status' => 'active',
        ]);

        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230046961',
            'consumer_name' => 'Ramesh Kumar',
            'meter_no' => '3808220',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get("/mrus/{$mru->id}/consumers/export");
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_user_can_delete_consumer_from_mru_master_list(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0477',
            'name' => 'Gerua',
            'status' => 'active',
        ]);

        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230046961',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->delete("/mrus/{$mru->id}/consumers/{$consumer->id}");
        $response->assertRedirect();

        $this->assertDatabaseMissing('consumer_accounts', [
            'id' => $consumer->id,
        ]);
    }

    protected function subscribeUser(User $user): void
    {
        $plan = Plan::firstOrCreate(
            ['name' => 'Unlimited Test Plan'],
            [
                'included_mrus' => 50,
                'included_consumers' => 50000,
                'extra_mru_rate' => 0,
                'extra_consumer_rate' => 0,
                'is_active' => true,
            ]
        );

        $duration = $plan->durations()->firstOrCreate(
            ['duration_unit' => 'month', 'duration_value' => 1],
            ['final_price' => 0, 'is_active' => true]
        );

        app(PlanService::class)->subscribeAgent($user, $plan, $duration);
    }

    public function test_user_can_launch_billing_cycle_for_mru_with_month_and_year(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->subscribeUser($user);

        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0477',
            'name' => 'Gerua',
            'status' => 'active',
        ]);

        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230046961',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson("/mrus/{$mru->id}/start-billing", [
            'billing_month' => 7,
            'billing_year' => 2026,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_user_can_launch_global_billing_cycle(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->subscribeUser($user);

        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0473',
            'name' => 'Hala',
            'status' => 'active',
        ]);

        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson('/mrus/billing-cycle', [
            'mru_id' => $mru->id,
            'billing_month' => 7,
            'billing_year' => 2026,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_user_can_create_billing_cycle_only_without_immediate_download(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->subscribeUser($user);

        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0244',
            'name' => 'NISARBHATI',
            'status' => 'active',
        ]);

        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230046961',
            'consumer_name' => 'John Doe',
            'meter_no' => '3808220',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson("/mrus/{$mru->id}/start-billing", [
            'billing_month' => 7,
            'billing_year' => 2026,
            'action_type' => 'create_only',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('bill_records', [
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230046961',
            'billing_month' => 7,
            'billing_year' => 2026,
            'download_status' => 'pending',
        ]);
    }

    public function test_multiple_users_can_create_mru_with_same_code(): void
    {
        $userA = User::factory()->create(['status' => 'active']);
        $userB = User::factory()->create(['status' => 'active']);

        // User A creates MRU code "010"
        $responseA = $this->actingAs($userA)->post('/mrus', [
            'code' => '010',
            'name' => 'Testing MRU Operator A',
            'full_identifier' => 'Sub-division Alpha',
        ]);
        $responseA->assertRedirect();
        $this->assertDatabaseHas('mrus', [
            'user_id' => $userA->id,
            'code' => '010',
            'name' => 'Testing MRU Operator A',
        ]);

        // User B also creates MRU code "010" (same code for different MRC / worker)
        $responseB = $this->actingAs($userB)->post('/mrus', [
            'code' => '010',
            'name' => 'Testing MRU Operator B',
            'full_identifier' => 'Sub-division Beta',
        ]);
        $responseB->assertRedirect();
        $this->assertDatabaseHas('mrus', [
            'user_id' => $userB->id,
            'code' => '010',
            'name' => 'Testing MRU Operator B',
        ]);

        $this->assertDatabaseCount('mrus', 2);
    }

    public function test_creating_billing_cycle_on_mru_without_consumers_returns_422_with_guidance(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->subscribeUser($user);

        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0473',
            'name' => 'Hala',
            'status' => 'active',
        ]);

        // Attempting startMonthlyBilling on MRU with 0 consumers
        $response = $this->actingAs($user)->postJson("/mrus/{$mru->id}/start-billing", [
            'billing_month' => 9,
            'billing_year' => 2026,
            'action_type' => 'download_all',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'action_required' => 'add_consumers',
            'message' => "MRU 'Hala' has no active consumers. Please add consumers first.",
        ]);
        $this->assertStringContainsString("/mrus/{$mru->id}", $response->json('redirect_url'));

        // Attempting createCycleOnly on MRU with 0 consumers
        $cycleOnlyResponse = $this->actingAs($user)->postJson("/mrus/{$mru->id}/start-billing", [
            'billing_month' => 9,
            'billing_year' => 2026,
            'action_type' => 'create_only',
        ]);

        $cycleOnlyResponse->assertStatus(422);
        $cycleOnlyResponse->assertJson([
            'success' => false,
            'action_required' => 'add_consumers',
            'message' => "MRU 'Hala' has no active consumers. Please add consumers first.",
        ]);
    }

    public function test_mru_show_page_displays_onboarding_guidance_when_no_consumers_exist(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0473',
            'name' => 'Hala',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('mrus.show', $mru));

        $response->assertOk();
        $response->assertSee('No Consumers in this MRU Workspace Yet');
        $response->assertSee('Step 1: Add Consumers First');
        $response->assertSee('No Active Consumers in this MRU');
        $response->assertSee('Buttons are disallowed: Add or import consumers to this MRU to unlock billing cycles.');
    }

    public function test_creating_mru_includes_guidance_in_flash_message(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->post('/mrus', [
            'code' => '0888',
            'name' => 'Rampur',
            'full_identifier' => 'Rampur Sector',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', "MRU 'Rampur (0888)' created successfully. Next step: add or import consumers to start billing.");
    }
}
