<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationModularArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_navigation_blade_obeys_line_count_invariant(): void
    {
        $templatePath = resource_path('views/layouts/navigation.blade.php');
        $this->assertFileExists($templatePath);

        $lineCount = count(file($templatePath));
        $this->assertLessThanOrEqual(
            120,
            $lineCount,
            "Master navigation.blade.php must not exceed 120 lines. Currently: {$lineCount} lines."
        );
    }

    public function test_navigation_partials_and_js_exist_on_disk(): void
    {
        $this->assertFileExists(resource_path('views/layouts/navigation/partials/brand-and-desktop-links.blade.php'));
        $this->assertFileExists(resource_path('views/layouts/navigation/partials/notification-dropdown.blade.php'));
        $this->assertFileExists(resource_path('views/layouts/navigation/partials/theme-toggle.blade.php'));
        $this->assertFileExists(resource_path('views/layouts/navigation/partials/user-dropdown.blade.php'));
        $this->assertFileExists(resource_path('views/layouts/navigation/partials/mobile-drawer.blade.php'));

        $this->assertFileExists(public_path('js/navigation/navigation-app.js'));
        $jsContent = file_get_contents(public_path('js/navigation/navigation-app.js'));
        $this->assertStringContainsString('function navigationThemeToggle', $jsContent);
        $this->assertStringContainsString('function navigationNotifications', $jsContent);
        $this->assertStringNotContainsString('{{', $jsContent, 'External JS must not contain raw Blade directives.');
    }

    public function test_authenticated_user_sees_modular_navigation(): void
    {
        $user = User::factory()->create(['status' => 'active', 'name' => 'John Operator']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('NBPDCL');
        $response->assertSee('Dashboard');
        $response->assertSee('FieldDesk');
        $response->assertSee('MRUs');
        $response->assertSee('Processing');
        $response->assertSee('PDF Manager');
        $response->assertSee('Reports');
        $response->assertSee('John Operator');
        $response->assertSee('navigation-app.js');
    }
}
