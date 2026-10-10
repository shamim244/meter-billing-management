<?php

namespace Tests\Feature;

use App\Enums\PaymentMode;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\IssueReport;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NavigationModularArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_navigation_blade_and_partials_obey_line_count_invariant(): void
    {
        $filesToCheck = [
            'views/layouts/navigation.blade.php',
            'views/layouts/navigation/partials/brand-and-desktop-links.blade.php',
            'views/layouts/navigation/partials/nav/operations-menu.blade.php',
            'views/layouts/navigation/partials/nav/growth-menu.blade.php',
            'views/layouts/navigation/partials/nav/admin-suite-menu.blade.php',
            'views/layouts/navigation/partials/mobile-drawer.blade.php',
            'views/layouts/navigation/partials/mobile/operations-accordion.blade.php',
            'views/layouts/navigation/partials/mobile/growth-accordion.blade.php',
            'views/layouts/navigation/partials/mobile/admin-accordion.blade.php',
            'views/layouts/navigation/partials/mobile/user-profile.blade.php',
            'views/layouts/admin/partials/sidebar.blade.php',
            'views/layouts/admin/partials/nav/operations.blade.php',
            'views/layouts/admin/partials/nav/finance.blade.php',
            'views/layouts/admin/partials/nav/growth.blade.php',
            'views/layouts/admin/partials/nav/communications.blade.php',
            'views/layouts/admin/partials/nav/system.blade.php',
            'views/layouts/user-panel/partials/sidebar.blade.php',
        ];

        foreach ($filesToCheck as $relativePath) {
            $fullPath = resource_path($relativePath);
            $this->assertFileExists($fullPath);
            $lineCount = count(file($fullPath));
            $this->assertLessThanOrEqual(
                120,
                $lineCount,
                "File {$relativePath} exceeds 120 lines. Currently: {$lineCount} lines."
            );
        }
    }

    public function test_navigation_partials_and_js_css_exist_on_disk(): void
    {
        $this->assertFileExists(resource_path('views/layouts/navigation/partials/brand-and-desktop-links.blade.php'));
        $this->assertFileExists(resource_path('views/layouts/navigation/partials/notification-dropdown.blade.php'));
        $this->assertFileExists(resource_path('views/layouts/navigation/partials/theme-toggle.blade.php'));
        $this->assertFileExists(resource_path('views/layouts/navigation/partials/user-dropdown.blade.php'));
        $this->assertFileExists(resource_path('views/layouts/navigation/partials/mobile-drawer.blade.php'));

        $this->assertFileExists(public_path('css/navigation/navigation.css'));
        $this->assertFileExists(public_path('js/navigation/navigation-app.js'));

        $jsContent = file_get_contents(public_path('js/navigation/navigation-app.js'));
        $this->assertStringContainsString('function navigationThemeToggle', $jsContent);
        $this->assertStringContainsString('function navigationNotifications', $jsContent);
        $this->assertStringContainsString('function navigationDropdown', $jsContent);
        $this->assertStringContainsString('function navigationMobileDrawer', $jsContent);
        $this->assertStringNotContainsString('{{', $jsContent, 'External JS must not contain raw Blade directives.');
    }

    public function test_regular_operator_sees_clustered_dropdown_navigation(): void
    {
        $user = User::factory()->create(['status' => 'active', 'name' => 'John Operator']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('NBPDCL');
        $response->assertSee('Dashboard');

        // Operations cluster dropdown
        $response->assertSee('Operations');
        $response->assertSee('FieldDesk');
        $response->assertSee('MRUs');
        $response->assertSee('Processing');
        $response->assertSee('PDF Manager');

        // Growth & Reports cluster dropdown
        $response->assertSeeText('Growth & Reports');
        $response->assertSeeText('Usage Reports');
        $response->assertSeeText('Refer & Earn Program');
        $response->assertSeeText('Wallet & Ledger');

        // Should NOT see Admin Suite cluster in top navbar
        $response->assertDontSee('Admin Suite');

        // User info & scripts
        $response->assertSee('John Operator');
        $response->assertSee('navigation-app.js');
        $response->assertSee('navigation.css');
        $response->assertSee('@alpinejs/collapse');
    }

    public function test_admin_user_sees_direct_admin_suite_dropdown_in_top_navbar(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['status' => 'active', 'name' => 'Sarah Admin']);
        $admin->assignRole($adminRole);

        // Seed 1 pending payment and 1 pending issue to test top navbar badges
        Payment::create([
            'user_id' => $admin->id,
            'amount' => 1200.0,
            'mode' => PaymentMode::MANUAL_UPI,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'status' => PaymentStatus::PENDING_VERIFICATION,
            'utr_number' => 'UTRTOPTEST111',
        ]);

        IssueReport::create([
            'user_id' => $admin->id,
            'title' => 'Top Nav Issue Alert',
            'description' => 'Test issue for top navigation badge',
            'category' => 'bug',
            'status' => 'pending',
            'priority' => 'critical',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Admin Suite');
        $response->assertSee('SaaS Administration Control');
        $response->assertSee('Billing Agents');
        $response->assertSee('Engine Settings');
        $response->assertSee('Pulse Monitor');
        $response->assertSee('Disaster Recovery');
        $response->assertSee('Cloud Migration');
        $response->assertSee('Manual Approvals');
        $response->assertSee('Bug Tracker');
        $response->assertSee('max-w-[calc(100vw-2rem)]');
    }

    public function test_mobile_and_tablet_drawer_responsiveness_and_accessibility(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['status' => 'active', 'name' => 'Adaptive Admin']);
        $admin->assignRole($adminRole);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertStatus(200);

        // Accessible ARIA bindings and IDs
        $response->assertSee(':aria-expanded="open.toString()"', false);
        $response->assertSee('aria-controls="mobile-navigation-drawer"', false);
        $response->assertSee('id="mobile-navigation-drawer"', false);
        $response->assertSee('@keydown.escape.window="open = false"', false);

        // Calibrated responsive breakpoints
        $response->assertSee('hidden lg:flex', false);
        $response->assertSee('hidden lg:hidden', false);

        // Accordion sections
        $response->assertSeeText('Working Operations');
        $response->assertSeeText('Growth & Account Hub');
        $response->assertSeeText('SaaS Administration');
    }

    public function test_admin_sidebar_displays_five_collapsible_domain_pillars_with_alert_badges(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['status' => 'active', 'name' => 'Admin Controller']);
        $admin->assignRole($adminRole);

        // Seed 1 pending payment and 1 pending issue
        Payment::create([
            'user_id' => $admin->id,
            'amount' => 500.0,
            'mode' => PaymentMode::MANUAL_UPI,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'status' => PaymentStatus::PENDING_VERIFICATION,
            'utr_number' => 'UTRNAVTEST999',
        ]);

        IssueReport::create([
            'user_id' => $admin->id,
            'title' => 'Test Nav Issue Alert',
            'description' => 'Test issue for navigation badge',
            'category' => 'bug',
            'status' => 'pending',
            'priority' => 'high',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);

        // 5 Collapsible Domain Pillars
        $response->assertSeeText('Core Operations');
        $response->assertSeeText('Finance & Gateways');
        $response->assertSeeText('Growth & Marketing');
        $response->assertSeeText('Communications Hub');
        $response->assertSeeText('System & DevOps');

        // Scroll container & micro styling
        $response->assertSee('custom-scrollbar');
        $response->assertSee('max-h-[calc(100vh-5rem)]');

        // Alert badges for pending payments and issues
        $response->assertSeeText('Manual Approvals');
        $response->assertSeeText('Bug Tracker & AI Desk');
    }

    public function test_user_panel_sidebar_renders_calibrated_groups(): void
    {
        $user = User::factory()->create(['status' => 'active', 'name' => 'Operator User']);

        $response = $this->actingAs($user)->get(route('user-panel.index'));

        $response->assertStatus(200);
        $response->assertSeeText('Operator Account & Billing');
        $response->assertSeeText('Growth & Rewards');
        $response->assertSeeText('Preferences & Security');
        $response->assertSeeText('Support & System');
        $response->assertSee('custom-scrollbar');
    }
}
