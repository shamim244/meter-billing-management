<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_user_panel(): void
    {
        $response = $this->get('/user-panel');
        $response->assertRedirect('/login');

        $response = $this->get('/user-panel/subscription');
        $response->assertRedirect('/login');

        $response = $this->get('/user-panel/shortcuts');
        $response->assertRedirect('/login');
    }

    public function test_user_can_view_user_panel_overview(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->get('/user-panel');

        $response->assertStatus(200);
        $response->assertSee('Account Overview');
        $response->assertSee($user->name);
        $response->assertSee('Dashboard');
    }

    public function test_user_can_view_subscription_page(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $plan = Plan::create([
            'name' => 'Pro Operator',
            'base_price' => 499.00,
            'included_mrus' => 10,
            'included_consumers' => 5000,
            'extra_mru_rate' => 100.00,
            'extra_consumer_rate' => 0.20,
            'is_active' => true,
        ]);
        $plan->durations()->create([
            'duration_months' => 1,
            'discount_percent' => 0,
            'final_price' => 499.00,
        ]);

        $response = $this->actingAs($user)->get('/user-panel/subscription');

        $response->assertStatus(200);
        $response->assertSeeText('Subscription & Quota Management');
        $response->assertSee('Available Subscription Plans');
        $response->assertSee('Pro Operator');
    }

    public function test_user_can_view_shortcuts_customizer(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->get('/user-panel/shortcuts');

        $response->assertStatus(200);
        $response->assertSee('Review Keyboard Shortcuts');
        $response->assertSee('Workflow Optimization');
        $response->assertSee('exit_box');
    }

    public function test_user_can_view_and_update_preferences(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->get('/user-panel/preferences');
        $response->assertStatus(200);
        $response->assertSee('Workspace Preferences');

        $postResponse = $this->actingAs($user)->post('/user-panel/preferences', [
            'default_view' => 'table',
            'default_page_size' => 100,
            'auto_fill_suggestion' => '1',
            'sound_feedback' => '1',
            'theme' => 'dark',
            'card_density' => 'compact',
            'amount_size' => 'standard',
            'show_remark_presets' => '0',
        ]);

        $postResponse->assertRedirect(route('user-panel.preferences'));
        $postResponse->assertSessionHas('success', 'Workspace preferences saved successfully!');
        $this->assertEquals('table', session('pref_default_view'));
        $this->assertEquals(100, session('pref_page_size'));
        $this->assertEquals('compact', session('pref_card_density'));
        $this->assertEquals('standard', session('pref_amount_size'));
    }

    public function test_user_can_update_profile_and_password(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'password' => Hash::make('old-password-123'),
        ]);

        $patchResponse = $this->actingAs($user)->patch('/user-panel/profile', [
            'name' => 'Updated Operator Name',
            'email' => 'updated.operator@example.com',
            'phone' => '9876543210',
        ]);

        $patchResponse->assertRedirect(route('user-panel.profile'));
        $patchResponse->assertSessionHas('success', 'Profile information updated successfully!');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Operator Name',
            'email' => 'updated.operator@example.com',
            'phone' => '9876543210',
        ]);

        $pwdResponse = $this->actingAs($user)->put('/user-panel/password', [
            'current_password' => 'old-password-123',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $pwdResponse->assertRedirect(route('user-panel.profile'));
        $pwdResponse->assertSessionHas('success', 'Password updated successfully!');
        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
    }

    public function test_user_can_view_api_keys_page(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        ApiKey::generate($user, 'Test Android Device');

        $response = $this->actingAs($user)->get(route('user-panel.api-keys'));

        $response->assertStatus(200);
        $response->assertSeeText('API Keys & Field Automation Tokens');
        $response->assertSee('Test Android Device');
    }

    public function test_user_can_generate_new_api_key_from_gui_with_expiration(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->post(route('user-panel.api-keys.store'), [
            'name' => 'Python ADB Field Script',
            'duration' => '30_days',
            'abilities' => ['*'],
        ]);

        $response->assertRedirect(route('user-panel.api-keys'));
        $response->assertSessionHas('new_api_key');
        $response->assertSessionHas('success');

        $newKeyData = session('new_api_key');
        $this->assertNotEmpty($newKeyData['plain_text_token']);
        $this->assertStringStartsWith('nbp_live_', $newKeyData['plain_text_token']);

        $this->assertDatabaseHas('api_keys', [
            'user_id' => $user->id,
            'name' => 'Python ADB Field Script',
        ]);

        $dbKey = ApiKey::where('user_id', $user->id)->first();
        $this->assertNotNull($dbKey->expires_at);
        $this->assertTrue($dbKey->expires_at->isFuture());
    }

    public function test_user_can_revoke_api_key_from_gui(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $gen = ApiKey::generate($user, 'Deletable Key');
        $keyId = $gen['apiKey']->id;

        $response = $this->actingAs($user)->delete(route('user-panel.api-keys.revoke', $keyId));

        $response->assertRedirect(route('user-panel.api-keys'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('api_keys', ['id' => $keyId]);
    }

    public function test_user_cannot_revoke_another_users_api_key(): void
    {
        $user1 = User::factory()->create(['status' => 'active']);
        $user2 = User::factory()->create(['status' => 'active']);

        $gen = ApiKey::generate($user2, 'User 2 Secret Key');
        $keyId = $gen['apiKey']->id;

        $response = $this->actingAs($user1)->delete(route('user-panel.api-keys.revoke', $keyId));

        $response->assertRedirect(route('user-panel.api-keys'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('api_keys', ['id' => $keyId]);
    }
}
