<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReadingGapSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_reading_gap_engine_settings(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.bills.engine-settings.update'), [
            'gap_safe_ceiling_domestic' => 240,
            'gap_safe_ceiling_commercial' => 450,
            'gap_safe_ceiling_agriculture' => 900,
            'gap_hold_default_mode' => 'one',
            'inspection_stale_warning_days' => 180,
        ]);

        $response->assertRedirect();
        $this->assertEquals(240, (int) SystemSetting::get('gap_safe_ceiling_domestic'));
        $this->assertEquals(450, (int) SystemSetting::get('gap_safe_ceiling_commercial'));
        $this->assertEquals(900, (int) SystemSetting::get('gap_safe_ceiling_agriculture'));
        $this->assertEquals('one', SystemSetting::get('gap_hold_default_mode'));
        $this->assertEquals(180, (int) SystemSetting::get('inspection_stale_warning_days'));
    }

    public function test_reset_engine_settings_restores_gap_defaults(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        SystemSetting::set('gap_safe_ceiling_domestic', 350);
        SystemSetting::set('gap_hold_default_mode', 'one');

        $response = $this->actingAs($admin)->post(route('admin.bills.engine-settings.reset'));
        $response->assertRedirect();

        $this->assertEquals(220, (int) SystemSetting::get('gap_safe_ceiling_domestic'));
        $this->assertEquals('zero', SystemSetting::get('gap_hold_default_mode'));
    }
}
