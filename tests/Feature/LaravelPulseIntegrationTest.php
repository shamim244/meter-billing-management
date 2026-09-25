<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class LaravelPulseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guest_is_redirected_to_login_when_accessing_pulse(): void
    {
        $response = $this->get('/pulse');

        $response->assertRedirect('/login');
    }

    public function test_non_admin_user_is_forbidden_from_accessing_pulse(): void
    {
        $regularUser = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($regularUser)->get('/pulse');

        $response->assertStatus(403);
    }

    public function test_admin_user_can_access_pulse_dashboard(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/pulse');

        $response->assertStatus(200);
        $response->assertSee('Pulse', false);
    }

    public function test_view_pulse_gate_logic(): void
    {
        $regularUser = User::factory()->create(['status' => 'active']);
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $this->assertFalse(Gate::forUser($regularUser)->allows('viewPulse'));
        $this->assertTrue(Gate::forUser($admin)->allows('viewPulse'));
        $this->assertFalse(Gate::forUser(null)->allows('viewPulse'));
    }
}
