<?php

namespace Tests\Feature;

use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserReadingGapWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_dedicated_reading_gap_hub(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('reading-gap.index'));
        $response->assertOk();
        $response->assertSeeText('Reading Gap & Reconciliation Hub');
    }

    public function test_user_can_fetch_filtered_gap_data(): void
    {
        $user = User::factory()->create();
        $mru = Mru::create(['user_id' => $user->id, 'code' => 'MRU_HUB_1', 'name' => 'Hub MRU']);
        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990009991',
            'consumer_name' => 'Test Hub Consumer',
            'last_physical_reading' => '6000',
            'last_inspected_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/reading-gap/data?tab=all');
        $response->assertOk();
        $this->assertArrayHasKey('kpis', $response->json());
        $this->assertArrayHasKey('consumers', $response->json());
    }

    public function test_user_can_export_gap_audit_csv(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('reading-gap.export-csv'));
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_user_can_record_meter_swap(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990009995',
            'meter_no' => 'OLD_MTR',
            'last_physical_reading' => '4000',
        ]);

        $response = $this->actingAs($user)->postJson(route('api.reading-gap.record-swap'), [
            'ca_number' => '99990009995',
            'new_meter_no' => 'NEW_MTR_99',
            'initial_reading' => 0,
            'old_meter_final_reading' => 4200,
            'notes' => 'Meter burned and replaced',
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('success'));
        $consumer->refresh();
        $this->assertEquals('NEW_MTR_99', $consumer->meter_no);
        $this->assertEquals('0', $consumer->last_physical_reading);
    }
}
