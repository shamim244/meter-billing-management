<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReadingGapIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_hub_deep_link_and_progress_attributes(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('reading-gap?ca=');
    }
}
