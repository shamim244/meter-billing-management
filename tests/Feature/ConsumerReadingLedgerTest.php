<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumerReadingLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    public function test_updating_working_reading_syncs_consumer_account_ledger(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'consumer_name' => 'ABDUL ANNAN',
        ]);

        $billJuly = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 7,
            'billing_year' => 2026,
            'previous_reading' => '350',
            'current_reading' => '400',
            'units_consumed' => 50,
        ]);

        // User enters July Working Reading = 415
        $response = $this->actingAs($user)->postJson('/bills/update-working-reading', [
            'id' => $billJuly->id,
            'working_reading' => '415',
        ]);

        $response->assertStatus(200);

        // Assert consumer account ledger has been updated
        $consumer->refresh();
        $this->assertEquals('415', $consumer->last_working_reading);
        $this->assertEquals(7, $consumer->last_working_month);
        $this->assertEquals(2026, $consumer->last_working_year);
    }

    protected function subscribeUser(User $user): void
    {
        $plan = \App\Models\Plan::firstOrCreate(
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

        app(\App\Services\Plan\PlanService::class)->subscribeAgent($user, $plan, $duration);
    }

    public function test_new_cycle_initializes_previous_reading_from_consumer_ledger(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->subscribeUser($user);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'consumer_name' => 'ABDUL ANNAN',
            'last_working_reading' => '415',
            'last_working_month' => 7,
            'last_working_year' => 2026,
        ]);

        // Create August cycle
        $response = $this->actingAs($user)->postJson('/mrus/billing-cycle', [
            'mru_id' => $mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
            'action_type' => 'create_only',
        ]);

        $response->assertStatus(200);

        $augBill = BillRecord::where('user_id', $user->id)
            ->where('ca_number', '102300783538')
            ->where('billing_month', 8)
            ->where('billing_year', 2026)
            ->first();

        $this->assertNotNull($augBill);
        // August previous reading initialized from July's ledger reading 415
        $this->assertEquals('415', $augBill->previous_reading);

        // When dashboard data is loaded for August
        $dashResponse = $this->actingAs($user)->getJson("/dashboard/data?mru_id={$mru->id}&month=8&year=2026");
        $dashResponse->assertStatus(200);

        $item = $dashResponse->json('data')[0];
        $this->assertEquals('415', $item['db_prev_reading']);
        $this->assertEquals('465', $item['working_reading']); // 415 + 50 avg = 465
    }

    public function test_updating_prior_month_working_reading_cascades_to_future_cycles(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'consumer_name' => 'ABDUL ANNAN',
        ]);

        $billJuly = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 7,
            'billing_year' => 2026,
            'working_reading' => '415',
            'units_consumed' => 50,
        ]);

        $billAug = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '102300783538',
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '415',
            'working_reading' => '465',
            'units_consumed' => 50,
        ]);

        // User edits July from 415 to 420
        $response = $this->actingAs($user)->postJson('/bills/update-working-reading', [
            'id' => $billJuly->id,
            'working_reading' => '420',
        ]);

        $response->assertStatus(200);

        // Assert August previous reading and working reading auto-cascaded!
        $billAug->refresh();
        $this->assertEquals('420', $billAug->previous_reading);
        $this->assertEquals('470', $billAug->working_reading); // 420 + 50 = 470
    }
}
