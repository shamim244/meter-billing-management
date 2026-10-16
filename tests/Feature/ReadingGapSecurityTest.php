<?php

namespace Tests\Feature;

use App\Models\ConsumerAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReadingGapSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_record_inspection_on_another_users_consumer(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $owner->id,
            'ca_number' => '99990007771',
            'last_physical_reading' => '5000',
        ]);

        $response = $this->actingAs($attacker)->postJson('/api/reading-gap/record-inspection', [
            'ca_number' => '99990007771',
            'physical_reading' => '5500',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_record_inspection_on_any_consumer(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $owner = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $owner->id,
            'ca_number' => '99990007772',
            'last_physical_reading' => '5000',
        ]);

        $response = $this->actingAs($admin)->postJson('/api/reading-gap/record-inspection', [
            'ca_number' => '99990007772',
            'physical_reading' => '5600',
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('success'));
    }

    public function test_physical_reading_must_be_non_negative_numeric(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990007773',
            'last_physical_reading' => '5000',
        ]);

        $response = $this->actingAs($user)->postJson('/api/reading-gap/record-inspection', [
            'ca_number' => '99990007773',
            'physical_reading' => -100,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['physical_reading']);
    }

    public function test_user_cannot_update_gap_mode_on_another_users_consumer(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $owner->id,
            'ca_number' => '99990007774',
            'last_physical_reading' => '5000',
        ]);

        $response = $this->actingAs($attacker)->postJson('/api/reading-gap/update-mode', [
            'ca_number' => '99990007774',
            'gap_recovery_mode' => 'hold_advance',
        ]);

        $response->assertStatus(403);
    }
}
