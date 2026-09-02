<?php

namespace Tests\Feature;

use App\Models\EmailProviderInstance;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Services\Notifications\Drivers\Email\HostingerDriver;
use App\Services\Notifications\EmailProviderRegistryService;
use App\Services\Notifications\HostingerMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HostingerMailIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $agentUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'email' => 'admin@nbpdcl-saas.com',
            'status' => 'active',
        ]);
        $this->adminUser->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']));

        $this->agentUser = User::factory()->create([
            'email' => 'agent@nexgenhub.site',
            'name' => 'Hostinger Test Agent',
            'status' => 'active',
        ]);
        $this->agentUser->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user']));
    }

    public function test_hostinger_driver_sends_email_successfully(): void
    {
        Http::fake([
            'https://api.mail.hostinger.com/api/v1/me' => Http::response([
                'data' => [
                    'mailboxes' => [
                        [
                            'resourceId' => 'AC68cd60bbe0df5cf1432bb84e625e',
                            'address' => 'agent@nexgenhub.site',
                        ],
                    ],
                ],
            ], 200),
            'https://api.mail.hostinger.com/api/v1/mailboxes/AC68cd60bbe0df5cf1432bb84e625e/send' => Http::response('', 204),
        ]);

        $driver = new HostingerDriver();
        $result = $driver->send(
            'agent@nexgenhub.site',
            'Welcome to NBPDCL SaaS',
            '<p>Your billing account is active</p>',
            [
                'api_key' => 'mock_token_123',
                'from_address' => 'agent@nexgenhub.site',
            ]
        );

        $this->assertTrue($result);
    }

    public function test_hostinger_provider_in_fallback_chain_delivers_notification(): void
    {
        Http::fake([
            'https://api.mail.hostinger.com/api/v1/me' => Http::response([
                'data' => [
                    'mailboxes' => [
                        [
                            'resourceId' => 'AC68cd60bbe0df5cf1432bb84e625e',
                            'address' => 'agent@nexgenhub.site',
                        ],
                    ],
                ],
            ], 200),
            'https://api.mail.hostinger.com/api/v1/mailboxes/AC68cd60bbe0df5cf1432bb84e625e/send' => Http::response('', 204),
        ]);

        $provider = EmailProviderInstance::create([
            'driver_type' => 'hostinger',
            'label' => 'Hostinger Mail API Test',
            'config' => [
                'api_key' => 'mock_token_123',
                'from_address' => 'agent@nexgenhub.site',
            ],
            'priority' => 1,
            'is_enabled' => true,
        ]);

        $notification = Notification::create([
            'user_id' => $this->agentUser->id,
            'event_type' => 'wallet.topup',
            'title' => 'Wallet Top-Up Successful',
            'body' => '₹1,500 credited to wallet.',
            'data' => ['email' => 'agent@nexgenhub.site'],
        ]);

        $delivery = NotificationDelivery::create([
            'notification_id' => $notification->id,
            'channel' => 'email',
            'status' => 'pending',
        ]);

        $registry = app(EmailProviderRegistryService::class);
        $result = $registry->sendViaChain($notification, $delivery);

        $this->assertTrue($result->success);
        $this->assertEquals($provider->id, $result->emailProviderInstanceId);

        $delivery->refresh();
        $this->assertEquals('sent', $delivery->status);
        $this->assertEquals($provider->id, $delivery->email_provider_instance_id);
    }

    public function test_admin_can_view_live_mailbox_inspector(): void
    {
        Http::fake([
            'https://api.mail.hostinger.com/api/v1/me' => Http::response([
                'data' => [
                    'mailboxes' => [
                        [
                            'resourceId' => 'AC68cd60bbe0df5cf1432bb84e625e',
                            'address' => 'agent@nexgenhub.site',
                        ],
                    ],
                ],
            ], 200),
            'https://api.mail.hostinger.com/api/v1/mailboxes/AC68cd60bbe0df5cf1432bb84e625e/folders/INBOX/messages*' => Http::response([
                'data' => [
                    [
                        'uid' => 1,
                        'subject' => 'Welcome to NBPDCL SaaS Pro!',
                        'from' => ['address' => 'agent@nexgenhub.site'],
                        'date' => now()->toIso8601String(),
                        'size' => 5000,
                    ],
                ],
                'pagination' => ['total' => 1],
            ], 200),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.notifications.mailbox.index'));
        $response->assertStatus(200);
        $response->assertSee('Live Hostinger Mailbox Inspector');
        $response->assertSee('agent@nexgenhub.site');
    }
}
