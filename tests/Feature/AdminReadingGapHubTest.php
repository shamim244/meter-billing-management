<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminReadingGapHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_system_wide_reading_gap_hub(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.reading-gap.index'));
        $response->assertOk();
        $response->assertSeeText('System-Wide Reading Gap & Unbilled Energy Intelligence');
    }

    public function test_regular_agent_cannot_access_admin_reading_gap_hub(): void
    {
        $agent = User::factory()->create();
        $response = $this->actingAs($agent)->get(route('admin.reading-gap.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_fetch_aggregated_gap_data(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->getJson(route('admin.reading-gap.data'));
        $response->assertOk();
        $this->assertArrayHasKey('kpis', $response->json());
        $this->assertArrayHasKey('mru_breakdown', $response->json());
        $this->assertArrayHasKey('recent_inspections', $response->json());
    }

    public function test_admin_can_export_audit_csv(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.reading-gap.export-audit'));
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
