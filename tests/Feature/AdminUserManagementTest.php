<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $operatorUser;
    protected User $secondAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'email' => 'superadmin@nbpdcl-saas.com',
            'name' => 'Super Admin',
            'status' => 'active',
        ]);
        $this->adminUser->assignRole($adminRole);

        $this->secondAdmin = User::factory()->create([
            'email' => 'secondadmin@nbpdcl-saas.com',
            'name' => 'Second Admin',
            'status' => 'active',
        ]);
        $this->secondAdmin->assignRole($adminRole);

        $this->operatorUser = User::factory()->create([
            'email' => 'operator1@nbpdcl-saas.com',
            'name' => 'Ramesh Operator',
            'phone' => '+919876543210',
            'status' => 'active',
        ]);
        $this->operatorUser->assignRole($userRole);
    }

    public function test_admin_can_view_users_index_page_with_stats_and_filters(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/users');

        $response->assertOk()
            ->assertSeeText('Billing Agents & User Management')
            ->assertSeeText('Total Accounts')
            ->assertSeeText('Ramesh Operator')
            ->assertSeeText('superadmin@nbpdcl-saas.com');
    }

    public function test_admin_can_view_user_dossier_show_page(): void
    {
        $mru = Mru::create([
            'user_id' => $this->operatorUser->id,
            'code' => 'MRU-PATNA-01',
            'name' => 'Patna City Main',
        ]);

        BillRecord::create([
            'user_id' => $this->operatorUser->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230046961',
            'billing_month' => 8,
            'billing_year' => 2026,
            'review_status' => 'submitted',
        ]);

        $response = $this->actingAs($this->adminUser)->get("/admin/users/{$this->operatorUser->id}");

        $response->assertOk()
            ->assertSeeText('User 360° Dossier')
            ->assertSeeText('Ramesh Operator')
            ->assertSeeText('MRU-PATNA-01')
            ->assertSeeText('Bill Review & Audit Activity');
    }

    public function test_admin_can_view_user_edit_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get("/admin/users/{$this->operatorUser->id}/edit");

        $response->assertOk()
            ->assertSeeText('Edit User Profile & Credentials')
            ->assertSeeText('Ramesh Operator')
            ->assertSeeText('Admin Password Reset');
    }

    public function test_admin_can_update_user_profile_and_role(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put("/admin/users/{$this->operatorUser->id}", [
                'name' => 'Ramesh Kumar Updated',
                'email' => 'ramesh.updated@nbpdcl-saas.com',
                'phone' => '+919999988888',
                'role' => 'user',
                'status' => 'active',
                'storage_limit_mb' => 500,
                'plan_tier' => 'pro',
                'email_verified' => '1',
            ]);

        $response->assertRedirect("/admin/users/{$this->operatorUser->id}")
            ->assertSessionHas('success');

        $this->operatorUser->refresh();
        $this->assertEquals('Ramesh Kumar Updated', $this->operatorUser->name);
        $this->assertEquals('ramesh.updated@nbpdcl-saas.com', $this->operatorUser->email);
        $this->assertEquals(500, $this->operatorUser->storage_limit_mb);
        $this->assertEquals('pro', $this->operatorUser->plan_tier);
        $this->assertNotNull($this->operatorUser->email_verified_at);
    }

    public function test_admin_can_reset_user_password(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put("/admin/users/{$this->operatorUser->id}/password", [
                'password' => 'NewSecurePassword123!',
                'password_confirmation' => 'NewSecurePassword123!',
            ]);

        $response->assertRedirect("/admin/users/{$this->operatorUser->id}")
            ->assertSessionHas('success');

        $this->operatorUser->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!', $this->operatorUser->password));
    }

    public function test_admin_can_impersonate_operator_and_leave_impersonation(): void
    {
        // 1. Admin initiates impersonation
        $response = $this->actingAs($this->adminUser)
            ->post("/admin/users/{$this->operatorUser->id}/impersonate");

        $response->assertRedirect('/dashboard')
            ->assertSessionHas('impersonated_by', $this->adminUser->id);

        $this->assertEquals($this->operatorUser->id, auth()->id());

        // 2. Impersonated user exits impersonation
        $exitResponse = $this->post('/impersonate/leave');

        $exitResponse->assertRedirect('/admin/users')
            ->assertSessionMissing('impersonated_by');

        $this->assertEquals($this->adminUser->id, auth()->id());
    }

    public function test_admin_cannot_impersonate_themselves_or_another_admin(): void
    {
        // Self impersonation check
        $selfResponse = $this->actingAs($this->adminUser)
            ->post("/admin/users/{$this->adminUser->id}/impersonate");

        $selfResponse->assertSessionHas('error');
        $this->assertEquals($this->adminUser->id, auth()->id());

        // Admin cannot impersonate another admin
        $adminResponse = $this->actingAs($this->adminUser)
            ->post("/admin/users/{$this->secondAdmin->id}/impersonate");

        $adminResponse->assertSessionHas('error');
        $this->assertEquals($this->adminUser->id, auth()->id());
    }

    public function test_non_admin_cannot_access_admin_user_management(): void
    {
        $response = $this->actingAs($this->operatorUser)->get('/admin/users');
        $response->assertStatus(403);

        $showResponse = $this->actingAs($this->operatorUser)->get("/admin/users/{$this->operatorUser->id}");
        $showResponse->assertStatus(403);

        $impersonateResponse = $this->actingAs($this->operatorUser)
            ->post("/admin/users/{$this->adminUser->id}/impersonate");
        $impersonateResponse->assertStatus(403);
    }
}
