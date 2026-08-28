<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminShortcutSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $operatorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'email' => 'admin@nbpdcl-saas.com',
            'status' => 'active',
        ]);
        $this->adminUser->assignRole($adminRole);

        $this->operatorUser = User::factory()->create([
            'email' => 'operator@nbpdcl-saas.com',
            'status' => 'active',
        ]);
        $this->operatorUser->assignRole($userRole);
    }

    public function test_non_admin_cannot_access_admin_shortcuts(): void
    {
        $response = $this->actingAs($this->operatorUser)->get('/admin/shortcuts');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_shortcuts_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/shortcuts');

        $response->assertOk()
            ->assertSee('Platform Default Keybindings')
            ->assertSee('Save System Defaults');
    }

    public function test_admin_can_update_system_wide_default_shortcuts(): void
    {
        $newShortcuts = [
            'copy_ca' => 'k',
            'focus_reading' => 'w',
            'auto_fill_reading' => 'q',
            'submit_ok' => 'Enter',
            'mark_doubt' => '8',
            'mark_critical' => '9',
            'next_card' => 'ArrowRight',
            'prev_card' => 'ArrowLeft',
            'open_remark' => 'n',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/shortcuts', [
                'shortcuts' => $newShortcuts,
            ]);

        $response->assertRedirect('/admin/shortcuts')
            ->assertSessionHas('success');

        $savedSetting = SystemSetting::get('shortcuts_default');
        $this->assertEquals('k', $savedSetting['copy_ca']);
        $this->assertEquals('w', $savedSetting['focus_reading']);
        $this->assertEquals('q', $savedSetting['auto_fill_reading']);
        $this->assertEquals('8', $savedSetting['mark_doubt']);

        // Assert regular operator dynamically inherits the new system defaults
        $operatorShortcuts = $this->operatorUser->getShortcutMap();
        $this->assertEquals('k', $operatorShortcuts['copy_ca']);
        $this->assertEquals('w', $operatorShortcuts['focus_reading']);
        $this->assertEquals('q', $operatorShortcuts['auto_fill_reading']);
    }

    public function test_admin_can_set_multi_key_combos_as_system_defaults(): void
    {
        $comboShortcuts = [
            'copy_ca' => 'Ctrl+C',
            'focus_reading' => 'Alt+R',
            'auto_fill_reading' => 'Ctrl+Shift+A',
            'submit_ok' => 'Ctrl+Enter',
            'mark_doubt' => 'Alt+2',
            'mark_critical' => 'Alt+3',
            'next_card' => 'Alt+ArrowDown',
            'prev_card' => 'Alt+ArrowUp',
            'open_remark' => 'Shift+M',
            'exit_box' => 'Escape',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/shortcuts', [
                'shortcuts' => $comboShortcuts,
            ]);

        $response->assertRedirect('/admin/shortcuts')
            ->assertSessionHas('success');

        $saved = SystemSetting::get('shortcuts_default');
        $this->assertEquals('Ctrl+C', $saved['copy_ca']);
        $this->assertEquals('Alt+R', $saved['focus_reading']);
        $this->assertEquals('Shift+M', $saved['open_remark']);

        $operatorShortcuts = $this->operatorUser->getShortcutMap();
        $this->assertEquals('Ctrl+C', $operatorShortcuts['copy_ca']);
        $this->assertEquals('Shift+M', $operatorShortcuts['open_remark']);
    }

    public function test_admin_can_reset_system_defaults_to_factory(): void
    {
        SystemSetting::set('shortcuts_default', [
            'copy_ca' => 'z',
            'focus_reading' => 'x',
            'auto_fill_reading' => 'y',
            'submit_ok' => 'Enter',
            'mark_doubt' => '2',
            'mark_critical' => '3',
            'next_card' => 'ArrowDown',
            'prev_card' => 'ArrowUp',
            'open_remark' => 'm',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/shortcuts/reset-factory');

        $response->assertRedirect('/admin/shortcuts')
            ->assertSessionHas('success');

        $restored = SystemSetting::get('shortcuts_default');
        $this->assertEquals('c', $restored['copy_ca']);
        $this->assertEquals('r', $restored['focus_reading']);
        $this->assertEquals('a', $restored['auto_fill_reading']);
    }

    public function test_admin_can_force_reset_all_user_custom_overrides(): void
    {
        $this->operatorUser->shortcuts = ['copy_ca' => 'x', 'focus_reading' => 'y'];
        $this->operatorUser->save();

        $this->assertNotNull($this->operatorUser->fresh()->shortcuts);

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/shortcuts/reset-all-users');

        $response->assertRedirect('/admin/shortcuts')
            ->assertSessionHas('success');

        $this->assertNull($this->operatorUser->fresh()->shortcuts);
    }
}
