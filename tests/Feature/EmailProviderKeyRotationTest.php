<?php

namespace Tests\Feature;

use App\Models\EmailProviderInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailProviderKeyRotationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_gracefully_handles_invalid_mac_or_key_rotation_without_crashing(): void
    {
        // 1. Simulate a record encrypted with an old/corrupted key (invalid MAC payload)
        $corruptedPayload = base64_encode(json_encode([
            'iv' => base64_encode(random_bytes(16)),
            'value' => base64_encode('fake_encrypted_value'),
            'mac' => hash_hmac('sha256', 'tampered', 'wrong_key'),
            'tag' => '',
        ]));

        $provider = EmailProviderInstance::create([
            'driver_type' => 'smtp',
            'label' => 'Legacy SMTP Provider',
            'config' => ['host' => 'initial'],
            'priority' => 1,
            'is_enabled' => true,
        ]);

        // Manually write corrupted payload to database column
        \DB::table('email_provider_instances')
            ->where('id', $provider->id)
            ->update(['config' => $corruptedPayload]);

        $freshProvider = EmailProviderInstance::find($provider->id);

        // 2. Reading config should NOT throw DecryptException, should return empty array
        $config = $freshProvider->config;
        $this->assertIsArray($config);
        $this->assertEmpty($config);
        $this->assertTrue($freshProvider->isConfigDecryptionFailed());

        // 3. Admin page /admin/notifications/email-providers must return HTTP 200 OK without 500 error
        $response = $this->actingAs($this->admin)->get(route('admin.notifications.email_providers.index'));
        $response->assertOk();
        $response->assertSee('Server Encryption Key (APP_KEY) Rotated');
        $response->assertSee('Re-enter Credentials');

        // 4. Updating the provider via edit form should re-encrypt config with current APP_KEY
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.notifications.email_providers.update', $provider), [
            'label' => 'Updated SMTP Provider',
            'priority' => 1,
            'is_enabled' => 1,
            'smtp_host' => 'smtp.hostinger.com',
            'smtp_port' => 587,
            'smtp_username' => 'agent@nexgenhub.site',
            'smtp_password' => 'NewSecretPassword123!',
            'from_address' => 'agent@nexgenhub.site',
            'from_name' => 'NBPDCL SaaS',
        ]);

        $updateResponse->assertRedirect(route('admin.notifications.email_providers.index'));

        // 5. Config is now decryptable with current key
        $reSavedProvider = EmailProviderInstance::find($provider->id);
        $this->assertEquals('smtp.hostinger.com', $reSavedProvider->config['host']);
        $this->assertEquals('NewSecretPassword123!', $reSavedProvider->config['password']);
        $this->assertFalse($reSavedProvider->isConfigDecryptionFailed());
    }
}
