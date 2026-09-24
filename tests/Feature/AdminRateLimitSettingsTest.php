<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use App\Services\Api\RateLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRateLimitSettingsTest extends TestCase
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

    public function test_guest_is_redirected_from_admin_rate_limits(): void
    {
        $response = $this->get('/admin/rate-limits');
        $response->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_admin_rate_limits(): void
    {
        $response = $this->actingAs($this->operatorUser)->get('/admin/rate-limits');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_rate_limits_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/rate-limits');

        $response->assertOk()
            ->assertSeeText('API Rate Limits & Throttling Controls')
            ->assertSee('general_per_minute')
            ->assertSee('review_per_minute')
            ->assertSee('batch_per_minute')
            ->assertSee('login_per_minute')
            ->assertSee('openapi_per_minute');
    }

    public function test_admin_can_update_rate_limit_configurations(): void
    {
        $payload = [
            'enabled' => '1',
            'general_per_minute' => 350,
            'review_per_minute' => 180,
            'batch_per_minute' => 45,
            'login_per_minute' => 20,
            'openapi_per_minute' => 90,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/rate-limits', $payload);

        $response->assertRedirect('/admin/rate-limits');
        $response->assertSessionHas('status', 'API Rate Limiting configuration updated successfully.');

        /** @var RateLimitService $service */
        $service = app(RateLimitService::class);
        $limits = $service->getLimits();

        $this->assertTrue($limits['enabled']);
        $this->assertEquals(350, $limits['general_per_minute']);
        $this->assertEquals(180, $limits['review_per_minute']);
        $this->assertEquals(45, $limits['batch_per_minute']);
        $this->assertEquals(20, $limits['login_per_minute']);
        $this->assertEquals(90, $limits['openapi_per_minute']);
    }

    public function test_admin_cannot_set_invalid_rate_limits(): void
    {
        $payload = [
            'enabled' => '1',
            'general_per_minute' => -5,
            'review_per_minute' => 'invalid_text',
            'batch_per_minute' => 0,
            'login_per_minute' => 20,
            'openapi_per_minute' => 90,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/rate-limits', $payload);

        $response->assertSessionHasErrors(['general_per_minute', 'review_per_minute', 'batch_per_minute']);
    }

    public function test_admin_can_reset_rate_limits_to_factory_defaults(): void
    {
        /** @var RateLimitService $service */
        $service = app(RateLimitService::class);

        // Customize first
        $service->updateLimits([
            'enabled' => false,
            'general_per_minute' => 999,
            'review_per_minute' => 888,
            'batch_per_minute' => 77,
            'login_per_minute' => 50,
            'openapi_per_minute' => 300,
        ]);

        $this->assertEquals(999, $service->getLimit('general_per_minute'));

        // Reset via POST
        $response = $this->actingAs($this->adminUser)
            ->post('/admin/rate-limits/reset');

        $response->assertRedirect('/admin/rate-limits');
        $response->assertSessionHas('status');

        $resetLimits = $service->getLimits();
        $this->assertTrue($resetLimits['enabled']);
        $this->assertEquals(RateLimitService::DEFAULTS['general_per_minute'], $resetLimits['general_per_minute']);
        $this->assertEquals(RateLimitService::DEFAULTS['review_per_minute'], $resetLimits['review_per_minute']);
    }

    public function test_developer_portal_reflects_dynamically_updated_limits(): void
    {
        /** @var RateLimitService $service */
        $service = app(RateLimitService::class);
        $service->updateLimits([
            'enabled' => true,
            'general_per_minute' => 456,
            'review_per_minute' => 123,
            'batch_per_minute' => 67,
            'login_per_minute' => 15,
            'openapi_per_minute' => 60,
        ]);

        $response = $this->get('/docs/api');

        $response->assertOk();
        $response->assertSee('456 req / min');
        $response->assertSee('123 rev / min');
        $response->assertSee('67 batches / min');
    }

    public function test_api_rate_limiter_honors_configured_limits(): void
    {
        /** @var RateLimitService $service */
        $service = app(RateLimitService::class);
        $service->updateLimits([
            'enabled' => true,
            'general_per_minute' => 2, // Set to 2 per minute for testing
            'review_per_minute' => 120,
            'batch_per_minute' => 30,
            'login_per_minute' => 15,
            'openapi_per_minute' => 60,
        ]);

        RateLimiter::clear('api.general');

        $res = ApiKey::generate($this->operatorUser, 'Test Limiter Key');
        $token = $res['plainTextToken'];

        // Request 1: OK
        $r1 = $this->withHeaders(['X-API-Key' => $token])->getJson('/api/v1/auth/me');
        $r1->assertOk();

        // Request 2: OK
        $r2 = $this->withHeaders(['X-API-Key' => $token])->getJson('/api/v1/auth/me');
        $r2->assertOk();

        // Request 3: Exceeded -> HTTP 429
        $r3 = $this->withHeaders(['X-API-Key' => $token])->getJson('/api/v1/auth/me');
        $r3->assertStatus(429)
            ->assertJson([
                'success' => false,
                'error' => 'RateLimitExceeded',
            ]);

        // Disable rate limiting globally
        $service->updateLimits([
            'enabled' => false,
            'general_per_minute' => 2,
            'review_per_minute' => 120,
            'batch_per_minute' => 30,
            'login_per_minute' => 15,
            'openapi_per_minute' => 60,
        ]);

        // Request 4 should now bypass limiter
        $r4 = $this->withHeaders(['X-API-Key' => $token])->getJson('/api/v1/auth/me');
        $r4->assertOk();
    }

    protected function tearDown(): void
    {
        app(RateLimitService::class)->resetToDefaults();
        parent::tearDown();
    }
}
