<?php

namespace Tests\Feature;

use App\Jobs\SendEmailNotificationJob;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\Plan;
use App\Models\User;
use App\Services\Installation\InstallerService;
use App\Services\Migration\ServerMigrationService;
use App\Services\Notifications\Drivers\Channels\EmailChannelDriver;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ServerHostingAndInstallerResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(PlanSeeder::class);
    }

    /**
     * Area 1: Pre-flight check contains PHP functions audit, OPcache, and PHP 8.4.1 verification.
     */
    public function test_preflight_check_audits_php_functions_and_opcache(): void
    {
        /** @var ServerMigrationService $service */
        $service = app(ServerMigrationService::class);
        $preflight = $service->runPreflightCheck();

        $this->assertArrayHasKey('functions', $preflight);
        $this->assertArrayHasKey('critical', $preflight['functions']);
        $this->assertArrayHasKey('recommended', $preflight['functions']);
        $this->assertArrayHasKey('disabled_functions', $preflight['functions']);

        // Check critical functions
        $this->assertArrayHasKey('putenv', $preflight['functions']['critical']);

        // Check recommended functions
        $this->assertArrayHasKey('proc_open', $preflight['functions']['recommended']);
        $this->assertArrayHasKey('shell_exec', $preflight['functions']['recommended']);
        $this->assertArrayHasKey('exec', $preflight['functions']['recommended']);
        $this->assertArrayHasKey('symlink', $preflight['functions']['recommended']);

        // Check OPcache audit
        $this->assertArrayHasKey('opcache', $preflight);
        $this->assertArrayHasKey('installed', $preflight['opcache']);
        $this->assertArrayHasKey('enabled', $preflight['opcache']);
        $this->assertArrayHasKey('status', $preflight['opcache']);

        // Check PHP 8.4.1 satisfaction
        $this->assertEquals(version_compare(PHP_VERSION, '8.4.1', '>='), $preflight['php_satisfies']);
    }

    /**
     * Area 1: Server preflight CLI command outputs PHP >= 8.4.1 requirement.
     */
    public function test_server_preflight_command_outputs_php_841_requirement(): void
    {
        $this->artisan('app:preflight-check')
            ->expectsOutputToContain('8.4.1')
            ->assertExitCode(0);
    }

    /**
     * Area 1: Step 1 requirements view displays PHP functions diagnostics and Hostinger remediation.
     */
    public function test_installer_step_1_renders_server_diagnostics_and_hostinger_guide(): void
    {
        config(['app.testing_installer' => true]);

        $response = $this->get(route('install.step1'));
        $response->assertStatus(200);
        $response->assertSee('PHP Functions & Server Diagnostics', false);
        $response->assertSee('proc_open', false);
        $response->assertSee('symlink', false);
        $response->assertSee('Zend OPcache', false);
    }

    /**
     * Area 2: Zero-shell installer prerequisites ensures SQLite, key generation, and directory permissions.
     */
    public function test_installer_service_ensures_prerequisites_without_shell(): void
    {
        /** @var InstallerService $installer */
        $installer = app(InstallerService::class);

        // Ensure prerequisites executes cleanly
        config(['app.testing_installer' => true]);
        $installer->ensureInstallerPrerequisites();

        // Verify storage directories exist
        $this->assertDirectoryExists(storage_path('framework/cache'));
        $this->assertDirectoryExists(storage_path('framework/sessions'));
        $this->assertDirectoryExists(storage_path('framework/views'));
        $this->assertDirectoryExists(storage_path('app/public'));

        // Verify SQLite file exists
        $this->assertFileExists(database_path('database.sqlite'));
    }

    /**
     * Area 3: Storage fallback route serves files, detects correct MIME types, and blocks traversal/dotfiles.
     */
    public function test_storage_fallback_route_serves_public_assets_and_blocks_traversal(): void
    {
        $testFileName = 'test_asset_'.uniqid().'.txt';
        $testContent = 'NBPDCL Resilient Asset Fallback Test';
        $fullPath = storage_path('app/public/'.$testFileName);

        $svgFileName = 'test_icon_'.uniqid().'.svg';
        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg"><circle r="10"/></svg>';
        $svgFullPath = storage_path('app/public/'.$svgFileName);

        File::ensureDirectoryExists(storage_path('app/public'));
        File::put($fullPath, $testContent);
        File::put($svgFullPath, $svgContent);

        try {
            // 1. Success test (text file)
            $response = $this->get('/storage/'.$testFileName);
            $response->assertStatus(200);
            $this->assertSame($testContent, $response->streamedContent());
            $response->assertHeader('Cache-Control');

            // 2. MIME type test (SVG)
            $svgResponse = $this->get('/storage/'.$svgFileName);
            $svgResponse->assertStatus(200);
            $svgResponse->assertHeader('Content-Type', 'image/svg+xml');

            // 3. Traversal attack blocked (relative paths)
            $traversalResponse = $this->get('/storage/../../routes/web.php');
            $traversalResponse->assertStatus(404);

            // 4. URL-encoded traversal attack blocked (%2e%2e)
            $encodedTraversal = $this->get('/storage/%2e%2e/%2e%2e/routes/web.php');
            $encodedTraversal->assertStatus(404);

            // 5. Hidden dotfile access blocked
            $dotfileResponse = $this->get('/storage/.env');
            $dotfileResponse->assertStatus(404);

            // 6. Non-existent file
            $missingResponse = $this->get('/storage/non_existent_random_file.png');
            $missingResponse->assertStatus(404);
        } finally {
            File::delete($fullPath);
            File::delete($svgFullPath);
        }
    }

    /**
     * Area 4: SendEmailNotificationJob does NOT throw exception on synchronous queue failure.
     */
    public function test_send_email_notification_job_does_not_throw_on_sync_queue(): void
    {
        config(['queue.default' => 'sync']);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('user');

        $notification = Notification::create([
            'user_id' => $user->id,
            'event_type' => 'agent.subscribed',
            'priority' => 'routine',
            'title' => 'Welcome to Free Starter Plan',
            'body' => 'Your subscription is now active.',
        ]);

        $delivery = NotificationDelivery::create([
            'notification_id' => $notification->id,
            'channel' => 'email',
            'status' => 'pending',
            'attempt_count' => 0,
        ]);

        // Execute job with invalid/failing driver configuration
        $job = new SendEmailNotificationJob($delivery->id);

        // The job should complete without throwing a RuntimeException
        try {
            app()->call([$job, 'handle']);
            $this->assertTrue(true, 'SendEmailNotificationJob handled sync error gracefully without throwing.');
        } catch (\Throwable $e) {
            $this->fail('SendEmailNotificationJob threw an exception on sync queue: '.$e->getMessage());
        }

        $delivery->refresh();
        $this->assertSame('permanently_failed', $delivery->status);
    }

    /**
     * Area 4: SendEmailNotificationJob catches unexpected driver exceptions on sync queue.
     */
    public function test_send_email_notification_job_catches_driver_exception_on_sync_queue(): void
    {
        config(['queue.default' => 'sync']);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('user');

        $notification = Notification::create([
            'user_id' => $user->id,
            'event_type' => 'agent.subscribed',
            'priority' => 'routine',
            'title' => 'Welcome to Free Starter Plan',
            'body' => 'Your subscription is now active.',
        ]);

        $delivery = NotificationDelivery::create([
            'notification_id' => $notification->id,
            'channel' => 'email',
            'status' => 'pending',
            'attempt_count' => 0,
        ]);

        // Create a mock email driver that throws an unexpected connection exception
        $mockDriver = $this->createMock(EmailChannelDriver::class);
        $mockDriver->expects($this->once())
            ->method('send')
            ->willThrowException(new \RuntimeException('SMTP Connection Timeout'));

        $job = new SendEmailNotificationJob($delivery->id);

        try {
            $job->handle($mockDriver);
            $this->assertTrue(true, 'Job handled driver exception cleanly without bubbling up to caller.');
        } catch (\Throwable $e) {
            $this->fail('SendEmailNotificationJob threw driver exception: '.$e->getMessage());
        }

        $delivery->refresh();
        $this->assertSame('permanently_failed', $delivery->status);
        $this->assertStringContainsString('SMTP Connection Timeout', $delivery->failed_reason);
    }

    /**
     * Area 4: Free Starter Plan activation returns valid JSON and succeeds.
     */
    public function test_user_can_activate_free_plan_and_receives_clean_json(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('user');

        $freePlan = Plan::where('name', 'Free Starter')->firstOrFail();
        $duration = $freePlan->durations()->where('is_active', true)->firstOrFail();

        $response = $this->actingAs($user)->postJson('/subscription/subscribe-wallet', [
            'plan_id' => $freePlan->id,
            'duration_id' => $duration->id,
            'action_mode' => 'new',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertNotNull($response->json('subscription_id'));
    }

    /**
     * Area 4: Quote endpoint returns valid JSON structure.
     */
    public function test_subscription_quote_returns_structured_json(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('user');

        $freePlan = Plan::where('name', 'Free Starter')->firstOrFail();
        $duration = $freePlan->durations()->where('is_active', true)->firstOrFail();

        $response = $this->actingAs($user)->getJson("/subscription/quote/{$freePlan->id}/{$duration->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'final_amount' => 0,
        ]);
    }
}
