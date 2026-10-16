<?php

namespace Tests\Feature;

use App\Models\ConsumerAccount;
use App\Models\MeterInspection;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhysicalInspectionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_view_inspection_history(): void
    {
        $user = User::factory()->create();
        $mru = Mru::create(['user_id' => $user->id, 'code' => 'MRU_H1', 'name' => 'History MRU']);
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '88880001111',
            'consumer_name' => 'History Test Consumer',
        ]);

        MeterInspection::create([
            'user_id' => $user->id,
            'consumer_account_id' => $consumer->id,
            'ca_number' => $consumer->ca_number,
            'physical_reading' => '5000',
            'inspected_at' => now()->subMonths(3),
            'notes' => 'Visit 1',
        ]);

        MeterInspection::create([
            'user_id' => $user->id,
            'consumer_account_id' => $consumer->id,
            'ca_number' => $consumer->ca_number,
            'physical_reading' => '5500',
            'inspected_at' => now()->subMonth(),
            'notes' => 'Visit 2',
        ]);

        $response = $this->actingAs($user)->getJson(route('api.reading-gap.inspections.index', ['ca' => $consumer->ca_number]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'inspections');

        $this->assertEquals('5500', $response->json('inspections.0.physical_reading'));
        $this->assertEquals('5000', $response->json('inspections.1.physical_reading'));
    }

    public function test_operator_can_update_an_inspection_and_cache_syncs(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '88880001112',
            'last_physical_reading' => '5000',
            'last_inspected_at' => now(),
        ]);

        $inspection = MeterInspection::create([
            'user_id' => $user->id,
            'consumer_account_id' => $consumer->id,
            'ca_number' => $consumer->ca_number,
            'physical_reading' => '5000',
            'inspected_at' => now(),
            'notes' => 'Typo reading',
        ]);

        $response = $this->actingAs($user)->putJson(route('api.reading-gap.inspections.update', ['id' => $inspection->id]), [
            'physical_reading' => '5200',
            'notes' => 'Corrected reading typo',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('meter_inspections', [
            'id' => $inspection->id,
            'physical_reading' => '5200',
            'notes' => 'Corrected reading typo',
        ]);

        $consumer->refresh();
        $this->assertEquals('5200', $consumer->last_physical_reading);
    }

    public function test_operator_can_delete_single_inspection_and_cache_falls_back(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '88880001113',
            'last_physical_reading' => '6500',
            'last_inspected_at' => now(),
        ]);

        MeterInspection::create([
            'user_id' => $user->id,
            'consumer_account_id' => $consumer->id,
            'ca_number' => $consumer->ca_number,
            'physical_reading' => '5000',
            'inspected_at' => now()->subMonths(2),
        ]);

        $recentInspection = MeterInspection::create([
            'user_id' => $user->id,
            'consumer_account_id' => $consumer->id,
            'ca_number' => $consumer->ca_number,
            'physical_reading' => '6500',
            'inspected_at' => now(),
        ]);

        $response = $this->actingAs($user)->deleteJson(route('api.reading-gap.inspections.destroy', ['id' => $recentInspection->id]));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('meter_inspections', ['id' => $recentInspection->id]);

        $consumer->refresh();
        $this->assertEquals('5000', $consumer->last_physical_reading);
    }

    public function test_operator_can_delete_all_inspections_and_cache_resets_to_null(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '88880001114',
            'last_physical_reading' => '6500',
            'last_inspected_at' => now(),
        ]);

        $inspection = MeterInspection::create([
            'user_id' => $user->id,
            'consumer_account_id' => $consumer->id,
            'ca_number' => $consumer->ca_number,
            'physical_reading' => '6500',
            'inspected_at' => now(),
        ]);

        $response = $this->actingAs($user)->deleteJson(route('api.reading-gap.inspections.clear-all', ['ca' => $consumer->ca_number]));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('meter_inspections', ['id' => $inspection->id]);

        $consumer->refresh();
        $this->assertNull($consumer->last_physical_reading);
        $this->assertNull($consumer->last_inspected_at);
    }

    public function test_bulk_delete_multiple_inspections(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '88880001115',
            'last_physical_reading' => '7000',
            'last_inspected_at' => now(),
        ]);

        $insp1 = MeterInspection::create([
            'user_id' => $user->id,
            'consumer_account_id' => $consumer->id,
            'ca_number' => $consumer->ca_number,
            'physical_reading' => '4000',
            'inspected_at' => now()->subMonths(4),
        ]);

        $insp2 = MeterInspection::create([
            'user_id' => $user->id,
            'consumer_account_id' => $consumer->id,
            'ca_number' => $consumer->ca_number,
            'physical_reading' => '5000',
            'inspected_at' => now()->subMonths(2),
        ]);

        $insp3 = MeterInspection::create([
            'user_id' => $user->id,
            'consumer_account_id' => $consumer->id,
            'ca_number' => $consumer->ca_number,
            'physical_reading' => '7000',
            'inspected_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('api.reading-gap.inspections.bulk-destroy'), [
            'ids' => [$insp2->id, $insp3->id],
            'ca_number' => $consumer->ca_number,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('meter_inspections', ['id' => $insp2->id]);
        $this->assertDatabaseMissing('meter_inspections', ['id' => $insp3->id]);
        $this->assertDatabaseHas('meter_inspections', ['id' => $insp1->id]);

        $consumer->refresh();
        $this->assertEquals('4000', $consumer->last_physical_reading);
    }

    public function test_unauthorized_user_cannot_access_or_modify_other_user_inspections(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $consumer = ConsumerAccount::create([
            'user_id' => $userA->id,
            'ca_number' => '88880001116',
            'last_physical_reading' => '5000',
        ]);

        $inspection = MeterInspection::create([
            'user_id' => $userA->id,
            'consumer_account_id' => $consumer->id,
            'ca_number' => $consumer->ca_number,
            'physical_reading' => '5000',
            'inspected_at' => now(),
        ]);

        // User B tries to view
        $this->actingAs($userB)
            ->getJson(route('api.reading-gap.inspections.index', ['ca' => $consumer->ca_number]))
            ->assertStatus(403);

        // User B tries to update
        $this->actingAs($userB)
            ->putJson(route('api.reading-gap.inspections.update', ['id' => $inspection->id]), [
                'physical_reading' => '9999',
            ])
            ->assertStatus(403);

        // User B tries to delete
        $this->actingAs($userB)
            ->deleteJson(route('api.reading-gap.inspections.destroy', ['id' => $inspection->id]))
            ->assertStatus(403);
    }
}
