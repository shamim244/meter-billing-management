<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReadingGapViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_reading_gap_components_cleanly(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('inspection-modal');
        $response->assertSee('gap-status-badge');
    }
}
