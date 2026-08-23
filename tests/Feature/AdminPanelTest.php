<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $user = User::where('email', 'test@example.com')->first();

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::where('email', 'admin@nbpdcl-saas.com')->first();

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);
        $response->assertSeeText('System Overview & Analytics');
    }

    public function test_admin_can_view_users_list(): void
    {
        $admin = User::where('email', 'admin@nbpdcl-saas.com')->first();

        $response = $this->actingAs($admin)->get('/admin/users');
        $response->assertStatus(200);
        $response->assertSeeText('Billing Agents & User Management');
        $response->assertSee('test@example.com');
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::where('email', 'admin@nbpdcl-saas.com')->first();

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'New Client',
            'email' => 'client@test.com',
            'password' => 'SecurePassword123!',
            'phone' => '9876543210',
            'role' => 'user',
            'status' => 'active',
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', [
            'email' => 'client@test.com',
            'name' => 'New Client',
        ]);
    }

    public function test_admin_can_toggle_user_status(): void
    {
        $admin = User::where('email', 'admin@nbpdcl-saas.com')->first();
        $user = User::where('email', 'test@example.com')->first();

        $this->assertEquals('active', $user->status);

        $response = $this->actingAs($admin)->patch("/admin/users/{$user->id}/toggle-status");
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('suspended', $user->status);
    }

    public function test_admin_can_access_all_payment_subpages(): void
    {
        $admin = User::where('email', 'admin@nbpdcl-saas.com')->first();

        // 1. All Transactions (Ledger)
        $resIndex = $this->actingAs($admin)->get(route('admin.payments.index'));
        $resIndex->assertStatus(200);
        $resIndex->assertSeeText('All Transactions');

        // 2. Manual Verification Queue
        $resManual = $this->actingAs($admin)->get(route('admin.payments.manual'));
        $resManual->assertStatus(200);
        $resManual->assertSeeText('Manual Payment Verification Queue');

        // 3. Analytics & Reports
        $resAnalytics = $this->actingAs($admin)->get(route('admin.payments.analytics'));
        $resAnalytics->assertStatus(200);
        $resAnalytics->assertSeeText('Financial Analytics & Revenue Performance');

        // 4. Audit Trail
        $resAudit = $this->actingAs($admin)->get(route('admin.payments.audit'));
        $resAudit->assertStatus(200);
        $resAudit->assertSeeText('Payment Audit Trail');

        // 5. Test Simulator
        $resSim = $this->actingAs($admin)->get(route('admin.payments.simulator'));
        $resSim->assertStatus(200);
        $resSim->assertSeeText('Sandbox & Gateway Testing Console');

        // 6. Gateway Settings
        $resSettings = $this->actingAs($admin)->get(route('admin.payments.settings'));
        $resSettings->assertStatus(200);
        $resSettings->assertSeeText('Payment Gateway & Channel Settings');
    }

    public function test_admin_can_simulate_successful_checkout(): void
    {
        $admin = User::where('email', 'admin@nbpdcl-saas.com')->first();
        $user = User::where('email', 'test@example.com')->first();

        $res = $this->actingAs($admin)->post(route('admin.payments.simulator.checkout'), [
            'user_id' => $user->id,
            'amount' => 2500.0,
            'purpose' => 'wallet_topup',
            'gateway' => 'razorpay',
            'outcome' => 'success',
        ]);

        $res->assertSessionHas('success');
        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'amount' => 2500.0,
            'status' => PaymentStatus::SUCCESS->value,
        ]);
    }

    public function test_admin_can_simulate_failed_checkout(): void
    {
        $admin = User::where('email', 'admin@nbpdcl-saas.com')->first();
        $user = User::where('email', 'test@example.com')->first();

        $res = $this->actingAs($admin)->post(route('admin.payments.simulator.checkout'), [
            'user_id' => $user->id,
            'amount' => 1200.0,
            'purpose' => 'direct_subscription',
            'gateway' => 'cashfree',
            'outcome' => 'failed',
        ]);

        $res->assertSessionHas('error');
        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'amount' => 1200.0,
            'status' => PaymentStatus::FAILED->value,
        ]);
    }

    public function test_admin_can_simulate_webhook_event(): void
    {
        $admin = User::where('email', 'admin@nbpdcl-saas.com')->first();

        $res = $this->actingAs($admin)->postJson(route('admin.payments.simulator.webhook'), [
            'gateway' => 'razorpay',
            'event_type' => 'payment.captured',
            'amount' => 1500.0,
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('status', 'ok');
        $res->assertJsonPath('gateway', 'razorpay');
        $res->assertJsonPath('final_payment_status', 'success');
    }

    public function test_admin_can_seed_demo_payments(): void
    {
        $admin = User::where('email', 'admin@nbpdcl-saas.com')->first();

        $initialCount = Payment::withoutUserScope()->count();

        $res = $this->actingAs($admin)->post(route('admin.payments.simulator.seed'));
        $res->assertSessionHas('success');

        $this->assertEquals($initialCount + 4, Payment::withoutUserScope()->count());
    }
}
