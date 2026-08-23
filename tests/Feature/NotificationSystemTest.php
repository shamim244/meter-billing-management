<?php

namespace Tests\Feature;

use App\Events\AdminNotificationFailedEvent;
use App\Events\AgentPlanMigratedEvent;
use App\Events\AgentSubscribedEvent;
use App\Events\MruLockedEvent;
use App\Events\PaymentSuccessEvent;
use App\Events\SubscriptionEnteredGracePeriodEvent;
use App\Events\SubscriptionSuspendedEvent;
use App\Events\WalletCreditedEvent;
use App\Events\WalletCriticalBalanceEvent;
use App\Jobs\SendEmailNotificationJob;
use App\Models\AgentNotificationPreference;
use App\Models\AgentSubscription;
use App\Models\EmailProviderInstance;
use App\Models\Mru;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\NotificationTemplate;
use App\Models\Payment;
use App\Models\User;
use App\Services\Notifications\Contracts\EmailProviderDriverInterface;
use App\Services\Notifications\EmailProviderRegistryService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $agentUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);

        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'status' => 'active',
        ]);
        $this->adminUser->assignRole('admin');

        $this->agentUser = User::factory()->create([
            'email' => 'agent@example.com',
            'name' => 'Test Billing Agent',
            'status' => 'active',
        ]);
        $this->agentUser->assignRole('user');

        $this->seed(\Database\Seeders\NotificationSystemSeeder::class);
    }

    /**
     * 1. Test Fallback Chain: Provider #1 fails, Provider #2 succeeds.
     */
    public function test_email_fallback_chain_walks_providers_and_records_succeeding_instance(): void
    {
        // Clear default providers
        EmailProviderInstance::query()->delete();

        // Create Provider #1 (Priority 1) that will fail
        $failingProvider = EmailProviderInstance::create([
            'driver_type' => 'smtp',
            'label' => 'Failing Primary Provider',
            'config' => ['host' => 'fail.smtp.com'],
            'priority' => 1,
            'is_enabled' => true,
        ]);

        // Create Provider #2 (Priority 2) that will succeed
        $succeedingProvider = EmailProviderInstance::create([
            'driver_type' => 'resend',
            'label' => 'Succeeding Backup Provider',
            'config' => ['api_key' => 're_test_key_123'],
            'priority' => 2,
            'is_enabled' => true,
        ]);

        // Mock drivers in registry
        $mockFailingDriver = new class implements EmailProviderDriverInterface {
            public function send(string $to, string $subject, string $htmlBody, array $config): bool {
                throw new \RuntimeException("Connection to fail.smtp.com refused");
            }
        };

        $mockSucceedingDriver = new class implements EmailProviderDriverInterface {
            public function send(string $to, string $subject, string $htmlBody, array $config): bool {
                return true;
            }
        };

        $registry = app(EmailProviderRegistryService::class);
        $registry->registerDriver('smtp', $mockFailingDriver);
        $registry->registerDriver('resend', $mockSucceedingDriver);

        // Create test notification and delivery
        $notification = Notification::create([
            'user_id' => $this->agentUser->id,
            'event_type' => 'subscription.renewal_due',
            'priority' => 'routine',
            'title' => 'Renewal Due',
            'body' => 'Your subscription is due for renewal.',
        ]);

        $delivery = NotificationDelivery::create([
            'notification_id' => $notification->id,
            'channel' => 'email',
            'status' => 'pending',
        ]);

        // Execute fallback chain
        $result = $registry->sendViaChain($notification, $delivery);

        $this->assertTrue($result->success);
        $this->assertEquals($succeedingProvider->id, $result->emailProviderInstanceId);

        // Verify delivery record updated with successful provider
        $delivery->refresh();
        $this->assertEquals('sent', $delivery->status);
        $this->assertEquals($succeedingProvider->id, $delivery->email_provider_instance_id);

        // Verify failing provider recorded failure
        $failingProvider->refresh();
        $this->assertNotNull($failingProvider->last_failure_at);
        $this->assertStringContainsString('fail.smtp.com', $failingProvider->last_failure_reason);

        // Verify succeeding provider recorded last_used_at
        $succeedingProvider->refresh();
        $this->assertNotNull($succeedingProvider->last_used_at);
    }

    /**
     * 2. Test Config Encryption At Rest.
     */
    public function test_email_provider_config_is_encrypted_at_rest_in_raw_database(): void
    {
        $secretPassword = 'super-secret-smtp-password-9988';

        $provider = EmailProviderInstance::create([
            'driver_type' => 'smtp',
            'label' => 'Encrypted SMTP Instance',
            'config' => [
                'host' => 'smtp.example.com',
                'username' => 'agent_user',
                'password' => $secretPassword,
            ],
            'priority' => 10,
            'is_enabled' => true,
        ]);

        // Raw SQL read bypassing Eloquent cast
        $rawRow = DB::table('email_provider_instances')->where('id', $provider->id)->first();
        $this->assertNotNull($rawRow);

        // Raw column must NOT contain plain password
        $this->assertStringNotContainsString($secretPassword, $rawRow->config);

        // Eloquent model read MUST decrypt properly
        $freshProvider = EmailProviderInstance::find($provider->id);
        $this->assertIsArray($freshProvider->config);
        $this->assertEquals($secretPassword, $freshProvider->config['password']);
        $this->assertEquals('smtp.example.com', $freshProvider->config['host']);
    }

    /**
     * 3. Test CRITICAL Event In-App Delivery Is Never Disabled by User Preference.
     */
    public function test_critical_event_always_delivers_in_app_even_if_preference_is_disabled(): void
    {
        // Agent disables all notifications in preferences
        AgentNotificationPreference::create([
            'user_id' => $this->agentUser->id,
            'event_category' => 'billing',
            'channel' => 'email',
            'enabled' => false,
        ]);

        AgentNotificationPreference::create([
            'user_id' => $this->agentUser->id,
            'event_category' => 'billing',
            'channel' => 'push',
            'enabled' => false,
        ]);

        // Dispatch Critical Event (SubscriptionSuspendedEvent)
        $plan = \App\Models\Plan::create([
            'code' => 'TEST_CRIT',
            'name' => 'Critical Plan',
            'billing_period' => 'monthly',
            'base_price' => 500,
            'included_mrus' => 5,
            'included_consumers' => 500,
            'extra_mru_rate' => 100,
            'extra_consumer_rate' => 1.0,
            'is_active' => true,
            'is_default' => false,
        ]);

        $sub = AgentSubscription::create([
            'user_id' => $this->agentUser->id,
            'plan_id' => $plan->id,
            'status' => 'suspended',
            'duration_months' => 1,
            'base_price_paid' => 500,
            'billing_start' => now()->subMonth(),
            'billing_end' => now(),
            'started_at' => now(),
            'current_period_starts_at' => now()->subMonth(),
            'current_period_ends_at' => now(),
            'included_mrus_locked' => 5,
            'included_consumers_locked' => 500,
            'extra_mru_rate_locked' => 100,
            'extra_consumer_rate_locked' => 1.0,
        ]);

        event(new SubscriptionSuspendedEvent($sub));

        // Verify Notification record created with priority 'critical'
        $notification = Notification::where('user_id', $this->agentUser->id)
            ->where('event_type', 'subscription.suspended')
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals('critical', $notification->priority);

        // Verify IN-APP delivery was created and sent
        $inAppDelivery = NotificationDelivery::where('notification_id', $notification->id)
            ->where('channel', 'in_app')
            ->first();

        $this->assertNotNull($inAppDelivery);
        $this->assertEquals('sent', $inAppDelivery->status);
    }

    /**
     * 4. Test Sole Enabled Provider Delete Guard.
     */
    public function test_cannot_delete_sole_enabled_email_provider_instance(): void
    {
        // Keep exactly 1 enabled provider
        EmailProviderInstance::query()->delete();

        $soleProvider = EmailProviderInstance::create([
            'driver_type' => 'smtp',
            'label' => 'Sole Active SMTP',
            'config' => ['host' => '127.0.0.1'],
            'priority' => 1,
            'is_enabled' => true,
        ]);

        // Attempt deletion as Admin
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.notifications.email_providers.destroy', $soleProvider));

        // Must fail with validation error or redirect with error
        $response->assertSessionHasErrors(['provider']);

        // Provider must still exist in DB
        $this->assertDatabaseHas('email_provider_instances', [
            'id' => $soleProvider->id,
        ]);

        // Now add a 2nd enabled provider
        $secondProvider = EmailProviderInstance::create([
            'driver_type' => 'resend',
            'label' => 'Second Provider',
            'config' => ['api_key' => 're_123'],
            'priority' => 2,
            'is_enabled' => true,
        ]);

        // Now deleting the first should succeed
        $deleteResponse = $this->actingAs($this->adminUser)
            ->delete(route('admin.notifications.email_providers.destroy', $soleProvider));

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('email_provider_instances', [
            'id' => $soleProvider->id,
        ]);
    }

    /**
     * 5. Test 3-Attempt Failure Triggers AdminNotificationFailedEvent on CRITICAL Event Only.
     */
    public function test_critical_notification_failure_triggers_admin_alert_after_three_attempts(): void
    {
        Event::fake([AdminNotificationFailedEvent::class]);

        // 1. Critical Notification
        $critNotification = Notification::create([
            'user_id' => $this->agentUser->id,
            'event_type' => 'subscription.suspended',
            'priority' => 'critical',
            'title' => 'Account Suspended',
            'body' => 'Your account is suspended.',
        ]);

        $critDelivery = NotificationDelivery::create([
            'notification_id' => $critNotification->id,
            'channel' => 'email',
            'status' => 'pending',
        ]);

        $job = new SendEmailNotificationJob($critDelivery->id);
        $job->failed(new \RuntimeException("SMTP Error on 3rd retry"));

        Event::assertDispatched(AdminNotificationFailedEvent::class, function ($e) use ($critNotification) {
            return $e->notification->id === $critNotification->id;
        });

        // 2. Routine Notification (must NOT trigger Admin alert)
        Event::fake([AdminNotificationFailedEvent::class]);

        $routineNotification = Notification::create([
            'user_id' => $this->agentUser->id,
            'event_type' => 'payment.success',
            'priority' => 'routine',
            'title' => 'Payment Success',
            'body' => 'Your payment succeeded.',
        ]);

        $routineDelivery = NotificationDelivery::create([
            'notification_id' => $routineNotification->id,
            'channel' => 'email',
            'status' => 'pending',
        ]);

        $routineJob = new SendEmailNotificationJob($routineDelivery->id);
        $routineJob->failed(new \RuntimeException("Routine failure"));

        Event::assertNotDispatched(AdminNotificationFailedEvent::class);
    }

    /**
     * 6. Test Domain Event Listeners Wiring across Systems.
     */
    public function test_domain_events_trigger_notifications_with_merged_placeholders(): void
    {
        Mail::fake();
        \Illuminate\Support\Facades\Notification::fake();

        // 1. Wallet Credited
        $transaction = $this->agentUser->depositFloat(1250.00, ['description' => 'Online Top-Up']);
        event(new WalletCreditedEvent($this->agentUser, $transaction));

        $walletNotif = Notification::where('user_id', $this->agentUser->id)
            ->where('event_type', 'wallet.credited')
            ->first();

        $this->assertNotNull($walletNotif);
        $this->assertStringContainsString('1,250.00', $walletNotif->title);
        $this->assertEquals('routine', $walletNotif->priority);

        // 2. MRU Locked
        $mru = Mru::create([
            'user_id' => $this->agentUser->id,
            'code' => 'MRU_TEST_01',
            'name' => 'Test MRU Area',
            'status' => 'locked',
        ]);

        event(new MruLockedEvent($mru, 'Overage Quota Limit Exceeded'));

        $mruNotif = Notification::where('user_id', $this->agentUser->id)
            ->where('event_type', 'mru.locked')
            ->first();

        $this->assertNotNull($mruNotif);
        $this->assertStringContainsString('MRU_TEST_01', $mruNotif->title);

        // 3. User Registration Welcome
        event(new Registered($this->agentUser));

        $welcomeNotif = Notification::where('user_id', $this->agentUser->id)
            ->where('event_type', 'auth.welcome')
            ->first();

        $this->assertNotNull($welcomeNotif);
        $this->assertStringContainsString('Welcome', $welcomeNotif->title);
    }

    /**
     * 7. Test Agent Mark-As-Read and Recent Notifications Endpoints.
     */
    public function test_agent_notifications_endpoints_and_read_state(): void
    {
        $n1 = Notification::create([
            'user_id' => $this->agentUser->id,
            'event_type' => 'wallet.credited',
            'priority' => 'routine',
            'title' => 'Top-up Received',
            'body' => 'Your wallet balance has updated.',
        ]);

        // Recent JSON endpoint
        $recentResponse = $this->actingAs($this->agentUser)
            ->getJson(route('notifications.recent'));

        $recentResponse->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.title', 'Top-up Received');

        // Mark Read
        $readResponse = $this->actingAs($this->agentUser)
            ->post(route('notifications.mark_read', $n1));

        $readResponse->assertRedirect();
        $n1->refresh();
        $this->assertNotNull($n1->read_at);

        // Index feed
        $indexResponse = $this->actingAs($this->agentUser)
            ->get(route('notifications.index'));

        $indexResponse->assertOk()
            ->assertSee('Top-up Received');
    }

    /**
     * 8. Test 'sync' Template Attempts Immediate Send Within Current Request.
     */
    public function test_sync_template_attempts_immediate_send(): void
    {
        // 1. Create a dummy email provider that succeeds
        $mockDriver = new class implements \App\Services\Notifications\Contracts\EmailProviderDriverInterface {
            public bool $wasCalled = false;
            public function send(string $to, string $subject, string $htmlBody, array $config): bool {
                $this->wasCalled = true;
                return true;
            }
        };

        $registry = app(\App\Services\Notifications\EmailProviderRegistryService::class);
        $registry->registerDriver('mock_sync', $mockDriver);

        EmailProviderInstance::query()->delete();
        EmailProviderInstance::create([
            'driver_type' => 'mock_sync',
            'label' => 'Mock Sync Provider',
            'config' => ['api_key' => 'test'],
            'priority' => 1,
            'is_enabled' => true,
        ]);

        // 2. Ensure auth.welcome has dispatch_mode = 'sync'
        $template = NotificationTemplate::where('event_type', 'auth.welcome')
            ->where('channel', 'email')
            ->first();
        $this->assertEquals('sync', $template->dispatch_mode);

        // 3. Dispatch auth.welcome event
        $dispatcher = app(\App\Services\Notifications\NotificationDispatchService::class);
        $notification = $dispatcher->dispatch('auth.welcome', $this->agentUser, [
            'agent_name' => $this->agentUser->name,
            'email' => $this->agentUser->email,
        ]);

        $delivery = NotificationDelivery::where('notification_id', $notification->id)
            ->where('channel', 'email')
            ->first();

        // 4. Assert delivery was executed immediately on the spot
        $this->assertNotNull($delivery);
        $this->assertEquals('sent', $delivery->status);
        $this->assertTrue($mockDriver->wasCalled);
    }

    /**
     * 9. Test 'sync' Template That Times Out Falls Back to Queued Dispatch.
     */
    public function test_sync_template_that_times_out_falls_back_to_queued_dispatch(): void
    {
        Queue::fake([SendEmailNotificationJob::class]);

        // 1. Create a dummy email provider that throws timeout exception
        $timeoutDriver = new class implements \App\Services\Notifications\Contracts\EmailProviderDriverInterface {
            public function send(string $to, string $subject, string $htmlBody, array $config): bool {
                throw new \RuntimeException("Connection timed out after 8.0 seconds");
            }
        };

        $registry = app(\App\Services\Notifications\EmailProviderRegistryService::class);
        $registry->registerDriver('mock_timeout', $timeoutDriver);

        EmailProviderInstance::query()->delete();
        EmailProviderInstance::create([
            'driver_type' => 'mock_timeout',
            'label' => 'Mock Timeout Provider',
            'config' => ['api_key' => 'test'],
            'priority' => 1,
            'is_enabled' => true,
        ]);

        // 2. Dispatch auth.welcome (which is dispatch_mode = 'sync')
        $dispatcher = app(\App\Services\Notifications\NotificationDispatchService::class);
        $notification = $dispatcher->dispatch('auth.welcome', $this->agentUser, [
            'agent_name' => $this->agentUser->name,
            'email' => $this->agentUser->email,
        ]);

        $delivery = NotificationDelivery::where('notification_id', $notification->id)
            ->where('channel', 'email')
            ->first();

        $this->assertNotNull($delivery);

        // 3. Assert that when sync failed/timed out, it fell back to pushing SendEmailNotificationJob
        Queue::assertPushed(SendEmailNotificationJob::class, function ($job) use ($delivery) {
            return $job->deliveryId === $delivery->id;
        });
    }

    /**
     * 10. Test 'queued' Template Existing Behavior Is Completely Unchanged.
     */
    public function test_queued_template_existing_behavior_is_completely_unchanged(): void
    {
        Queue::fake([SendEmailNotificationJob::class]);

        // Ensure wallet.debited template has dispatch_mode = 'queued'
        $template = NotificationTemplate::where('event_type', 'wallet.debited')
            ->where('channel', 'email')
            ->first();
        $this->assertEquals('queued', $template->dispatch_mode);

        $dispatcher = app(\App\Services\Notifications\NotificationDispatchService::class);
        $notification = $dispatcher->dispatch('wallet.debited', $this->agentUser, [
            'amount' => '50.00',
            'balance' => '450.00',
            'description' => 'Service Fee',
        ]);

        $delivery = NotificationDelivery::where('notification_id', $notification->id)
            ->where('channel', 'email')
            ->first();

        $this->assertNotNull($delivery);
        $this->assertEquals('pending', $delivery->status);

        Queue::assertPushed(SendEmailNotificationJob::class, function ($job) use ($delivery) {
            return $job->deliveryId === $delivery->id;
        });
    }

    /**
     * 11. Test Admin Can Update Template Dispatch Mode via Console.
     */
    public function test_admin_can_update_template_dispatch_mode(): void
    {
        $template = NotificationTemplate::where('event_type', 'wallet.credited')
            ->where('channel', 'email')
            ->first();

        $this->assertEquals('queued', $template->dispatch_mode);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.notifications.templates.update', $template), [
                'subject' => 'Updated Wallet Credit Subject',
                'body_template' => 'Updated body template {amount}',
                'priority' => 'routine',
                'dispatch_mode' => 'sync',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('admin.notifications.templates.index'));

        $template->refresh();
        $this->assertEquals('sync', $template->dispatch_mode);
        $this->assertEquals('Updated Wallet Credit Subject', $template->subject);
    }
}
