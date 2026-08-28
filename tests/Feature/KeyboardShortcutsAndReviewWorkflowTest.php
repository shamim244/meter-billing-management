<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeyboardShortcutsAndReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Mru $mru;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'operator@nbpdcl.test',
        ]);

        $this->mru = Mru::create([
            'user_id' => $this->user->id,
            'code' => 'MRU-01',
            'name' => 'Patna City Sector 1',
        ]);
    }

    public function test_user_gets_default_shortcuts_when_no_custom_shortcuts_set(): void
    {
        $this->assertNull($this->user->shortcuts);

        $shortcuts = $this->user->getShortcutMap();
        $this->assertEquals('c', $shortcuts['copy_ca']);
        $this->assertEquals('Enter', $shortcuts['submit_ok']);
        $this->assertEquals('2', $shortcuts['mark_doubt']);
        $this->assertEquals('3', $shortcuts['mark_critical']);
        $this->assertEquals('ArrowDown', $shortcuts['next_card']);
        $this->assertEquals('ArrowUp', $shortcuts['prev_card']);
        $this->assertEquals('r', $shortcuts['focus_reading']);
        $this->assertEquals('a', $shortcuts['auto_fill_reading']);
        $this->assertEquals('m', $shortcuts['open_remark']);
    }

    public function test_user_can_retrieve_shortcuts_api(): void
    {
        $response = $this->actingAs($this->user)->getJson('/user/shortcuts');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'is_customized' => false,
            ])
            ->assertJsonPath('shortcuts.copy_ca', 'c')
            ->assertJsonPath('shortcuts.submit_ok', 'Enter');
    }

    public function test_user_can_save_custom_shortcuts(): void
    {
        $custom = [
            'copy_ca' => 'k',
            'submit_ok' => 'Space',
            'mark_doubt' => 'd',
            'mark_critical' => 'x',
            'next_card' => 'j',
            'prev_card' => 'k',
            'focus_reading' => 'w',
            'open_remark' => 'n',
        ];

        $response = $this->actingAs($this->user)->postJson('/user/shortcuts', [
            'shortcuts' => $custom,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Custom shortcuts saved successfully!',
            ]);

        $this->user->refresh();
        $this->assertEquals('k', $this->user->getShortcutMap()['copy_ca']);
        $this->assertEquals('Space', $this->user->getShortcutMap()['submit_ok']);
    }

    public function test_user_can_save_multi_key_combo_shortcuts(): void
    {
        $combos = [
            'copy_ca' => 'Ctrl+C',
            'submit_ok' => 'Ctrl+Enter',
            'mark_doubt' => 'Alt+2',
            'mark_critical' => 'Alt+3',
            'next_card' => 'Alt+ArrowDown',
            'prev_card' => 'Alt+ArrowUp',
            'focus_reading' => 'Alt+R',
            'open_remark' => 'Shift+M',
            'auto_fill_reading' => 'Ctrl+Shift+A',
            'exit_box' => 'Escape',
        ];

        $response = $this->actingAs($this->user)->postJson('/user/shortcuts', [
            'shortcuts' => $combos,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Custom shortcuts saved successfully!',
            ]);

        $this->user->refresh();
        $map = $this->user->getShortcutMap();
        $this->assertEquals('Ctrl+C', $map['copy_ca']);
        $this->assertEquals('Ctrl+Enter', $map['submit_ok']);
        $this->assertEquals('Shift+M', $map['open_remark']);
        $this->assertEquals('Ctrl+Shift+A', $map['auto_fill_reading']);
    }

    public function test_user_can_reset_shortcuts_to_defaults(): void
    {
        $this->user->shortcuts = ['copy_ca' => 'z', 'submit_ok' => 'q'];
        $this->user->save();

        $this->assertEquals('z', $this->user->getShortcutMap()['copy_ca']);

        $response = $this->actingAs($this->user)->postJson('/user/shortcuts/reset');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Shortcuts reset to system defaults.',
            ]);

        $this->user->refresh();
        $this->assertNull($this->user->shortcuts);
        $this->assertEquals('c', $this->user->getShortcutMap()['copy_ca']);
    }

    public function test_user_can_update_bill_review_status(): void
    {
        $bill = BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => '10230046961',
            'billing_month' => 8,
            'billing_year' => 2026,
            'review_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->postJson('/bills/review-status', [
            'id' => $bill->id,
            'review_status' => 'doubt',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'review_status' => 'doubt',
            ]);

        $bill->refresh();
        $this->assertEquals('doubt', $bill->review_status);
    }

    public function test_user_can_update_bill_remark(): void
    {
        $bill = BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => '10230046961',
            'billing_month' => 8,
            'billing_year' => 2026,
            'remark' => null,
        ]);

        $response = $this->actingAs($this->user)->postJson('/bills/update-remark', [
            'id' => $bill->id,
            'remark' => 'Meter glass broken, need replacement',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'remark' => 'Meter glass broken, need replacement',
            ]);

        $bill->refresh();
        $this->assertEquals('Meter glass broken, need replacement', $bill->remark);
    }

    public function test_dashboard_data_includes_shortcuts_and_review_fields(): void
    {
        ConsumerAccount::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => '10230046961',
            'consumer_name' => 'Ramesh Kumar',
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => '10230046961',
            'billing_month' => 8,
            'billing_year' => 2026,
            'review_status' => 'critical',
            'remark' => 'Door Locked',
            'previous_reading' => '500',
            'working_reading' => '560',
            'units_consumed' => 60,
        ]);

        $response = $this->actingAs($this->user)->getJson('/dashboard/data?mru_id=' . $this->mru->id . '&month=8&year=2026');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'counts',
                'user_shortcuts',
                'shortcut_labels',
            ])
            ->assertJsonPath('data.0.ca_number', '10230046961')
            ->assertJsonPath('data.0.review_status', 'critical')
            ->assertJsonPath('data.0.remark', 'Door Locked')
            ->assertJsonPath('user_shortcuts.copy_ca', 'c')
            ->assertJsonPath('counts.critical', 1);
    }
}
