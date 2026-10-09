<?php

namespace Tests\Feature\Docs;

use App\Models\ApiKey;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_public_user_can_access_developer_portal(): void
    {
        $response = $this->get('/docs/api');

        $response->assertStatus(200);
        $response->assertSeeText('NBPDCL Developer Portal');
        $response->assertSeeText('API Quickstart & Authentication');
        $response->assertSeeText('Interactive "Try It Out" API Console');
        $response->assertSeeText('AI Agent & Copilot Tool-Calling Setup');
    }

    public function test_authenticated_user_sees_personalized_banner_and_key(): void
    {
        $user = User::factory()->create([
            'name' => 'Shamim Engineer',
            'status' => 'active',
        ]);

        $keyData = ApiKey::generate($user, 'Personal Test Bot');
        $prefix = $keyData['apiKey']->key_prefix;

        $response = $this->actingAs($user)->get('/docs/api');

        $response->assertStatus(200);
        $response->assertSee('Shamim Engineer');
        $response->assertSee($prefix);
    }

    public function test_openapi_json_endpoint_returns_valid_openapi_3_schema(): void
    {
        $response = $this->getJson('/api/v1/openapi.json');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        $json = $response->json();

        $this->assertEquals('3.0.3', $json['openapi']);
        $this->assertArrayHasKey('info', $json);
        $this->assertArrayHasKey('paths', $json);
        $this->assertArrayHasKey('/bills', $json['paths']);
        $this->assertArrayHasKey('/bills/review', $json['paths']);
        $this->assertArrayHasKey('/bills/batch-sync', $json['paths']);
        $this->assertArrayHasKey('/automation/queue', $json['paths']);
        $this->assertArrayHasKey('/auth/me', $json['paths']);
        $this->assertArrayHasKey('components', $json);
        $this->assertArrayHasKey('securitySchemes', $json['components']);
    }

    public function test_developer_portal_view_obeys_modularity_and_decoupling_standard(): void
    {
        $viewPath = resource_path('views/docs/api.blade.php');
        $this->assertFileExists($viewPath);

        $lineCount = count(file($viewPath));
        $this->assertLessThanOrEqual(120, $lineCount, "Master view docs/api.blade.php has {$lineCount} lines, exceeding the 120 lines master limit.");

        $jsPath = public_path('js/docs/api-docs-app.js');
        $this->assertFileExists($jsPath);
        $jsContent = file_get_contents($jsPath);
        $this->assertStringNotContainsString('{{', $jsContent, 'External JS file must not contain raw Blade directives.');

        $cssPath = public_path('css/docs/api-docs.css');
        $this->assertFileExists($cssPath);

        $expectedPartials = [
            'header-nav.blade.php',
            'user-banner.blade.php',
            'sidebar.blade.php',
            'quickstart-section.blade.php',
            'endpoints-catalog-section.blade.php',
            'reason-codes-section.blade.php',
            'try-it-out-console.blade.php',
            'ai-copilot-section.blade.php',
            'footer.blade.php',
        ];

        foreach ($expectedPartials as $partial) {
            $this->assertFileExists(resource_path('views/docs/partials/'.$partial));
        }
    }
}
