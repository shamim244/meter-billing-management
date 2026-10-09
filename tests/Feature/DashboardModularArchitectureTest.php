<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\Mru;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardModularArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_modular_dashboard_renders_with_css_and_js_assets(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $mru = Mru::create([
            'code' => '0477',
            'name' => 'Gerua Test Area',
            'full_identifier' => 'Sub-04',
            'status' => 'active',
        ]);

        BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '10230077881',
            'mru_id' => $mru->id,
            'billing_month' => 4,
            'billing_year' => 2026,
            'consumer_name' => 'Safe Test Consumer',
            'total_amount' => 650.00,
            'units_consumed' => 80,
            'download_status' => 'downloaded',
            'parse_status' => 'parsed',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        // Verify master view includes modular stylesheet
        $response->assertSee('/css/dashboard/dashboard.css', false);

        // Verify master view includes decoupled javascript app
        $response->assertSee('/js/dashboard/dashboard-app.js', false);

        // Verify window.dashboardConfig bridge script is rendered
        $response->assertSee('window.dashboardConfig = {', false);
        $response->assertSee('csrfToken:', false);
        $response->assertSee('mruTopupUrl:', false);
        $response->assertSee('shortcuts:', false);

        // Verify Core views partials are rendered
        $response->assertSeeText('Billing Hub');
        $response->assertSeeText('Consumer');
        $response->assertSeeText('Working Reading');

        // Verify modals are present in the DOM
        $response->assertSee('Create MRU Workspace');
        $response->assertSee('MRU Already Exists');
        $response->assertSee('New Billing Cycle');
        $response->assertSee('Quick Single CA Pull');
        $response->assertSee('Keyboard Shortcuts');
        $response->assertSee('Smart Average Tuning');
        $response->assertSee('2D METER READING HISTORY');
        $response->assertSee('Consumer Mobile Number');
        $response->assertSee('Bulk Mobile Numbers Update');
        $response->assertSee('FieldDesk Quick Bridge');
    }

    public function test_modular_css_files_exist_in_public_directory(): void
    {
        $cssFiles = [
            'dashboard.css',
            'layout.css',
            'cards.css',
            'reading-boxes.css',
            'badges.css',
            'modals.css',
            'tables.css',
        ];

        foreach ($cssFiles as $file) {
            $path = public_path("css/dashboard/{$file}");
            $this->assertFileExists($path, "CSS file {$file} should exist in public/css/dashboard/");
            $this->assertGreaterThan(0, filesize($path), "CSS file {$file} should not be empty");
        }
    }

    public function test_decoupled_js_file_exists_and_contains_no_blade_syntax(): void
    {
        $jsPath = public_path('js/dashboard/dashboard-app.js');
        $this->assertFileExists($jsPath);

        $jsContent = file_get_contents($jsPath);
        $this->assertStringContainsString('function dashboardApp()', $jsContent);
        $this->assertStringContainsString('window.dashboardConfig', $jsContent);

        // Assert strictly zero Blade directives exist in compiled/extracted JS
        $this->assertDoesNotMatchRegularExpression('/\{\{/', $jsContent, 'JS file must not contain raw Blade mustache {{ brackets');
        $this->assertDoesNotMatchRegularExpression('/\{!!/', $jsContent, 'JS file must not contain raw Blade raw {!! tags');
        $this->assertDoesNotMatchRegularExpression('/@json/', $jsContent, 'JS file must not contain @json Blade directive');
    }

    public function test_master_dashboard_blade_view_is_compact_under_120_lines(): void
    {
        $viewPath = resource_path('views/dashboard.blade.php');
        $this->assertFileExists($viewPath);

        $lines = file($viewPath, FILE_IGNORE_NEW_LINES);
        $this->assertLessThan(120, count($lines), 'Master dashboard.blade.php must be under 120 lines (currently '.count($lines).' lines)');
    }
}
