<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\ApiRequestLog;
use App\Models\User;
use App\Services\Api\ApiConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminApiHubTest extends TestCase
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

    public function test_guest_is_redirected_from_api_hub(): void
    {
        $response = $this->get('/admin/api-hub');
        $response->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_api_hub(): void
    {
        $response = $this->actingAs($this->operatorUser)->get('/admin/api-hub');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_api_hub(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/api-hub');

        $response->assertOk()
            ->assertSeeText('API & Automation Control Hub')
            ->assertSeeText('Traffic Analytics')
            ->assertSeeText('Feature Switches')
            ->assertSeeText('Rate Limiting')
            ->assertSeeText('Security Policies')
            ->assertSeeText('Issued Keys Ledger');
    }

    public function test_admin_can_update_settings_and_policies(): void
    {
        $payload = [
            'api_master_enabled' => '1',
            'user_keys_enabled' => '1',
            'feature_automation_enabled' => '1',
            'feature_mobile_sync_enabled' => '1',
            'feature_batch_sync_enabled' => '1',
            'feature_consumer_updates_enabled' => '1',
            'public_docs_enabled' => '1',
            'max_keys_per_user' => 7,
            'allow_permanent_keys' => '1',
            'default_key_lifetime_days' => 90,
            'rate_limiting_enabled' => '1',
            'general_per_minute' => 300,
            'review_per_minute' => 150,
            'batch_per_minute' => 40,
            'login_per_minute' => 25,
            'openapi_per_minute' => 80,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/api-hub/settings', $payload);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        /** @var ApiConfigurationService $service */
        $service = app(ApiConfigurationService::class);
        $settings = $service->getSettings();

        $this->assertEquals(7, $settings['max_keys_per_user']);
        $this->assertEquals(90, $settings['default_key_lifetime_days']);
        $this->assertEquals(300, $settings['general_per_minute']);
        $this->assertEquals(150, $settings['review_per_minute']);
    }

    public function test_admin_can_reset_settings_to_defaults(): void
    {
        /** @var ApiConfigurationService $service */
        $service = app(ApiConfigurationService::class);
        $service->updateSettings([
            'max_keys_per_user' => 20,
            'general_per_minute' => 999,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/api-hub/reset');

        $response->assertRedirect('/admin/api-hub');
        $response->assertSessionHas('status');

        $settings = $service->getSettings();
        $this->assertEquals(ApiConfigurationService::DEFAULTS['max_keys_per_user'], $settings['max_keys_per_user']);
        $this->assertEquals(ApiConfigurationService::DEFAULTS['general_per_minute'], $settings['general_per_minute']);
    }

    public function test_admin_can_revoke_user_api_key(): void
    {
        $keyRes = ApiKey::generate($this->operatorUser, 'Compromised Field Key');
        $apiKey = $keyRes['apiKey'];

        $this->assertDatabaseHas('api_keys', ['id' => $apiKey->id]);

        $response = $this->actingAs($this->adminUser)
            ->delete("/admin/api-hub/keys/{$apiKey->id}");

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('api_keys', ['id' => $apiKey->id]);
    }

    public function test_api_requests_are_logged_to_analytics_table(): void
    {
        $keyRes = ApiKey::generate($this->operatorUser, 'Analytics Test Key');
        $token = $keyRes['plainTextToken'];

        $response = $this->withHeaders(['X-API-Key' => $token])
            ->getJson('/api/v1/auth/me');

        $response->assertOk();

        $this->assertDatabaseHas('api_request_logs', [
            'api_key_id' => $keyRes['apiKey']->id,
            'path' => '/api/v1/auth/me',
            'endpoint_group' => 'auth',
            'status_code' => 200,
        ]);
    }

    public function test_feature_switch_disables_specific_endpoint(): void
    {
        /** @var ApiConfigurationService $service */
        $service = app(ApiConfigurationService::class);
        $service->updateSettings([
            'api_master_enabled' => true,
            'feature_automation_enabled' => false, // Pause ADB tool
            'feature_mobile_sync_enabled' => true,
        ]);

        $keyRes = ApiKey::generate($this->operatorUser, 'Automation Key');
        $token = $keyRes['plainTextToken'];

        // Automation queue should be disabled (HTTP 503)
        $r1 = $this->withHeaders(['X-API-Key' => $token])
            ->getJson('/api/v1/automation/queue');

        $r1->assertStatus(503)
            ->assertJson([
                'success' => false,
                'error' => 'FeatureDisabled',
            ]);

        // General endpoints should still work fine (HTTP 200)
        $r2 = $this->withHeaders(['X-API-Key' => $token])
            ->getJson('/api/v1/auth/me');

        $r2->assertOk();
    }

    public function test_master_api_switch_disables_entire_api(): void
    {
        /** @var ApiConfigurationService $service */
        $service = app(ApiConfigurationService::class);
        $service->updateSettings([
            'api_master_enabled' => false, // Master switch OFF
        ]);

        $keyRes = ApiKey::generate($this->operatorUser, 'Test Key');
        $token = $keyRes['plainTextToken'];

        $response = $this->withHeaders(['X-API-Key' => $token])
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(503)
            ->assertJson([
                'success' => false,
                'error' => 'ApiDisabled',
            ]);
    }

    public function test_key_policies_enforced_in_user_panel(): void
    {
        /** @var ApiConfigurationService $service */
        $service = app(ApiConfigurationService::class);

        // 1. Max keys quota enforcement
        $service->updateSettings([
            'user_keys_enabled' => true,
            'max_keys_per_user' => 1,
            'allow_permanent_keys' => true,
        ]);

        // Create 1 key
        ApiKey::generate($this->operatorUser, 'Existing Key');

        // Trying to create a 2nd key should be rejected
        $response = $this->actingAs($this->operatorUser)
            ->post('/user-panel/api-keys', [
                'name' => 'Second Key',
                'duration' => '30_days',
            ]);

        $response->assertRedirect('/user-panel/api-keys');
        $response->assertSessionHas('error');

        // 2. Permanent keys restricted
        $service->updateSettings([
            'user_keys_enabled' => true,
            'max_keys_per_user' => 5,
            'allow_permanent_keys' => false, // Permanent disallowed
        ]);

        $response2 = $this->actingAs($this->operatorUser)
            ->post('/user-panel/api-keys', [
                'name' => 'Permanent Key Attempt',
                'duration' => 'never',
            ]);

        $response2->assertRedirect('/user-panel/api-keys');
        $response2->assertSessionHas('error');
    }

    public function test_admin_can_clear_analytics_logs(): void
    {
        ApiRequestLog::create([
            'path' => '/api/v1/test',
            'endpoint_group' => 'reads',
            'method' => 'GET',
            'status_code' => 200,
            'duration_ms' => 10,
            'created_at' => now(),
        ]);

        $this->assertEquals(1, ApiRequestLog::count());

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/api-hub/analytics/clear');

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $this->assertEquals(0, ApiRequestLog::count());
    }
}
