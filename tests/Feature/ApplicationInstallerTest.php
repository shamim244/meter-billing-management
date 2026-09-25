<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ApplicationInstallerTest extends TestCase
{
    use RefreshDatabase;

    protected string $testLockPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->testLockPath = storage_path('installed.lock');
    }

    protected function tearDown(): void
    {
        config(['app.testing_installer' => false]);
        parent::tearDown();
    }

    public function test_uninstalled_application_redirects_traffic_to_install_wizard(): void
    {
        config(['app.testing_installer' => true]);

        // Temporarily ensure lock file is absent for test
        $hadLock = File::exists($this->testLockPath);
        $savedContent = $hadLock ? File::get($this->testLockPath) : null;
        if ($hadLock) {
            File::delete($this->testLockPath);
        }

        try {
            $response = $this->get('/');
            $response->assertRedirect(route('install.index'));
        } finally {
            if ($hadLock && $savedContent !== null) {
                File::put($this->testLockPath, $savedContent);
            } else {
                File::delete($this->testLockPath);
            }
        }
    }

    public function test_installer_step_1_displays_preflight_and_requirements(): void
    {
        config(['app.testing_installer' => true]);

        $hadLock = File::exists($this->testLockPath);
        $savedContent = $hadLock ? File::get($this->testLockPath) : null;
        if ($hadLock) {
            File::delete($this->testLockPath);
        }

        try {
            $response = $this->get(route('install.step1'));
            $response->assertStatus(200);
            $response->assertSee('Step 1: Server Readiness', false);
            $response->assertSee('PHP Version', false);
            $response->assertSee('Writable Directories', false);
        } finally {
            if ($hadLock && $savedContent !== null) {
                File::put($this->testLockPath, $savedContent);
            } else {
                File::delete($this->testLockPath);
            }
        }
    }

    public function test_installer_step_2_displays_database_form_and_tests_connection(): void
    {
        config(['app.testing_installer' => true]);

        $hadLock = File::exists($this->testLockPath);
        $savedContent = $hadLock ? File::get($this->testLockPath) : null;
        if ($hadLock) {
            File::delete($this->testLockPath);
        }

        try {
            // View Step 2
            $response = $this->get(route('install.step2'));
            $response->assertStatus(200);
            $response->assertSee('Step 2: Database & Environment Connection', false);

            // Test SQLite connection via AJAX endpoint
            $testResponse = $this->postJson(route('install.test_db'), [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ]);

            $testResponse->assertStatus(200);
            $testResponse->assertJson([
                'success' => true,
            ]);
        } finally {
            if ($hadLock && $savedContent !== null) {
                File::put($this->testLockPath, $savedContent);
            } else {
                File::delete($this->testLockPath);
            }
        }
    }

    public function test_installer_clean_install_creates_super_admin_and_locks_installer(): void
    {
        config(['app.testing_installer' => true]);

        $hadLock = File::exists($this->testLockPath);
        $savedContent = $hadLock ? File::get($this->testLockPath) : null;
        if ($hadLock) {
            File::delete($this->testLockPath);
        }

        try {
            $response = $this->post(route('install.run_clean'), [
                'name' => 'Installation Test Admin',
                'email' => 'installer_test@nbpdcl-saas.com',
                'password' => 'secretPass123!',
                'password_confirmation' => 'secretPass123!',
            ]);

            $response->assertRedirect(route('install.complete'));
            $response->assertSessionHas('success');

            // Verify user was created with admin role
            $admin = User::where('email', 'installer_test@nbpdcl-saas.com')->first();
            $this->assertNotNull($admin);
            $this->assertSame('Installation Test Admin', $admin->name);
            $this->assertTrue($admin->hasRole('admin'));

            // Verify lock file was created
            $this->assertFileExists($this->testLockPath);
        } finally {
            if ($hadLock && $savedContent !== null) {
                File::put($this->testLockPath, $savedContent);
            } else {
                File::delete($this->testLockPath);
            }
        }
    }

    public function test_installed_application_blocks_access_to_install_routes(): void
    {
        // Default testing mode treats app as installed
        config(['app.testing_installer' => false]);

        $response = $this->get(route('install.index'));
        $response->assertRedirect(route('login'));

        $step1Response = $this->get(route('install.step1'));
        $step1Response->assertRedirect(route('login'));
    }

    public function test_artisan_app_install_command_executes_in_headless_mode(): void
    {
        $this->artisan('app:install', [
            '--headless' => true,
            '--db-driver' => 'sqlite',
            '--db-name' => ':memory:',
            '--admin-name' => 'CLI Auto Admin',
            '--admin-email' => 'cli_auto_admin@example.com',
            '--admin-pass' => 'P@ssw0rd999!',
            '--force' => true,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Clean installation completed successfully');

        $admin = User::where('email', 'cli_auto_admin@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertSame('CLI Auto Admin', $admin->name);
        $this->assertTrue($admin->hasRole('admin'));
    }
}
