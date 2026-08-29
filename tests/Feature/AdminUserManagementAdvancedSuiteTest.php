<?php

namespace Tests\Feature;

use App\Models\AgentSubscription;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserManagementAdvancedSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $operator1;
    protected User $operator2;
    protected Plan $proPlan;
    protected PlanDuration $proDuration;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'email' => 'admin@nbpdcl-saas.com',
            'name' => 'Super Admin',
            'status' => 'active',
        ]);
        $this->adminUser->assignRole($adminRole);

        $this->operator1 = User::factory()->create([
            'email' => 'operator1@nbpdcl-saas.com',
            'name' => 'Operator One',
            'phone' => '+919876543210',
            'status' => 'active',
            'plan_tier' => 'starter',
        ]);
        $this->operator1->assignRole($userRole);

        $this->operator2 = User::factory()->create([
            'email' => 'operator2@nbpdcl-saas.com',
            'name' => 'Operator Two',
            'phone' => '+919876543211',
            'status' => 'suspended',
            'plan_tier' => 'free',
        ]);
        $this->operator2->assignRole($userRole);

        $this->proPlan = Plan::create([
            'name' => 'Pro Operator',
            'description' => 'High capacity pro plan',
            'included_mrus' => 5,
            'included_consumers' => 2500,
            'extra_mru_rate' => 20.00,
            'extra_consumer_rate' => 0.20,
            'grace_period_days' => 5,
            'is_active' => true,
        ]);

        $this->proDuration = PlanDuration::create([
            'plan_id' => $this->proPlan->id,
            'duration_unit' => 'month',
            'duration_value' => 1,
            'original_price' => 499.00,
            'discount_percent' => 0,
            'final_price' => 499.00,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_export_filtered_users_as_csv(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/users/export?status=active');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('Operator One', $content);
        $this->assertStringContainsString('operator1@nbpdcl-saas.com', $content);
    }

    public function test_admin_can_perform_bulk_actions_activate_suspend_change_tier(): void
    {
        // 1. Bulk Activate operator2
        $respActivate = $this->actingAs($this->adminUser)->post('/admin/users/bulk-action', [
            'user_ids' => [$this->operator2->id],
            'bulk_action' => 'activate',
        ]);
        $respActivate->assertRedirect()->assertSessionHas('success');
        $this->assertEquals('active', $this->operator2->fresh()->status);

        // 2. Bulk Suspend operator1
        $respSuspend = $this->actingAs($this->adminUser)->post('/admin/users/bulk-action', [
            'user_ids' => [$this->operator1->id],
            'bulk_action' => 'suspend',
        ]);
        $respSuspend->assertRedirect()->assertSessionHas('success');
        $this->assertEquals('suspended', $this->operator1->fresh()->status);

        // 3. Bulk Change Plan Tier
        $respTier = $this->actingAs($this->adminUser)->post('/admin/users/bulk-action', [
            'user_ids' => [$this->operator1->id, $this->operator2->id],
            'bulk_action' => 'change_plan_tier',
            'plan_tier' => 'enterprise',
        ]);
        $respTier->assertRedirect()->assertSessionHas('success');
        $this->assertEquals('enterprise', $this->operator1->fresh()->plan_tier);
        $this->assertEquals('enterprise', $this->operator2->fresh()->plan_tier);
    }

    public function test_admin_cannot_bulk_delete_or_lockout_themselves(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/users/bulk-action', [
            'user_ids' => [$this->adminUser->id],
            'bulk_action' => 'suspend',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals('active', $this->adminUser->fresh()->status);
    }

    public function test_admin_can_manually_grant_plan_to_user(): void
    {
        $response = $this->actingAs($this->adminUser)->post("/admin/users/{$this->operator1->id}/grant-plan", [
            'grant_mode' => 'new_plan',
            'plan_id' => $this->proPlan->id,
            'duration_id' => $this->proDuration->id,
        ]);

        $response->assertRedirect()->assertSessionHas('success');

        $activeSub = $this->operator1->fresh()->activeSubscription;
        $this->assertNotNull($activeSub);
        $this->assertEquals($this->proPlan->id, $activeSub->plan_id);
        $this->assertEquals('active', $activeSub->lifecycle_status);
        $this->assertEquals(5, $activeSub->included_mrus_locked);
        $this->assertEquals(2500, $activeSub->included_consumers_locked);
    }

    public function test_admin_can_manually_extend_subscription_validity_days(): void
    {
        // First grant plan
        $this->actingAs($this->adminUser)->post("/admin/users/{$this->operator1->id}/grant-plan", [
            'grant_mode' => 'new_plan',
            'plan_id' => $this->proPlan->id,
            'duration_id' => $this->proDuration->id,
        ]);

        $initialEndDate = $this->operator1->fresh()->activeSubscription->billing_end;

        // Extend validity +60 days
        $extendResp = $this->actingAs($this->adminUser)->post("/admin/users/{$this->operator1->id}/grant-plan", [
            'grant_mode' => 'extend_validity',
            'days_to_add' => 60,
        ]);

        $extendResp->assertRedirect()->assertSessionHas('success');
        $newEndDate = $this->operator1->fresh()->activeSubscription->billing_end;
        $this->assertEquals(60, $initialEndDate->diffInDays($newEndDate));
    }

    public function test_admin_can_override_subscription_quotas(): void
    {
        // Grant plan first
        $this->actingAs($this->adminUser)->post("/admin/users/{$this->operator1->id}/grant-plan", [
            'grant_mode' => 'new_plan',
            'plan_id' => $this->proPlan->id,
            'duration_id' => $this->proDuration->id,
        ]);

        // Override to 10 MRUs and 5,000 Consumers
        $response = $this->actingAs($this->adminUser)->post("/admin/users/{$this->operator1->id}/override-quotas", [
            'included_mrus_locked' => 10,
            'included_consumers_locked' => 5000,
            'extra_mru_rate_locked' => 15.00,
            'extra_consumer_rate_locked' => 0.15,
        ]);

        $response->assertRedirect()->assertSessionHas('success');

        $activeSub = $this->operator1->fresh()->activeSubscription;
        $this->assertEquals(10, $activeSub->included_mrus_locked);
        $this->assertEquals(5000, $activeSub->included_consumers_locked);
        $this->assertEquals(15.00, (float) $activeSub->extra_mru_rate_locked);
    }

    public function test_admin_can_send_direct_notification_and_record_delivery(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->adminUser)->post("/admin/users/{$this->operator1->id}/send-notification", [
            'title' => 'Urgent Server Maintenance Alert',
            'body' => 'Please finish your pending bill audits before midnight tonight.',
            'priority' => 'critical',
            'send_email' => '1',
        ]);

        $response->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->operator1->id,
            'title' => 'Urgent Server Maintenance Alert',
            'priority' => 'critical',
        ]);

        $notification = Notification::where('user_id', $this->operator1->id)->latest('id')->first();
        $this->assertDatabaseHas('notification_deliveries', [
            'notification_id' => $notification->id,
            'channel' => 'in_app',
            'status' => 'delivered',
        ]);
        $this->assertDatabaseHas('notification_deliveries', [
            'notification_id' => $notification->id,
            'channel' => 'email',
            'status' => 'delivered',
        ]);
    }

    public function test_admin_can_permanently_purge_user_and_storage_disk(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put("users/{$this->operator1->id}/pdfs/test.pdf", 'dummy bill content');
        $this->assertTrue(Storage::disk('private')->exists("users/{$this->operator1->id}/pdfs/test.pdf"));

        $response = $this->actingAs($this->adminUser)->delete("/admin/users/{$this->operator1->id}/purge", [
            'confirm_text' => 'DELETE',
        ]);

        $response->assertRedirect('/admin/users')->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $this->operator1->id]);
        $this->assertFalse(Storage::disk('private')->exists("users/{$this->operator1->id}/pdfs/test.pdf"));
    }

    public function test_non_admin_is_forbidden_from_all_advanced_admin_actions(): void
    {
        $resp1 = $this->actingAs($this->operator1)->get('/admin/users/export');
        $resp1->assertStatus(403);

        $resp2 = $this->actingAs($this->operator1)->post('/admin/users/bulk-action', [
            'user_ids' => [$this->operator2->id],
            'bulk_action' => 'activate',
        ]);
        $resp2->assertStatus(403);

        $resp3 = $this->actingAs($this->operator1)->post("/admin/users/{$this->operator2->id}/grant-plan", [
            'grant_mode' => 'extend_validity',
            'days_to_add' => 30,
        ]);
        $resp3->assertStatus(403);
    }
}
