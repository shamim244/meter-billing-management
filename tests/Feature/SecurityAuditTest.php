<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;
    protected User $userB;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->userA = User::factory()->create([
            'email' => 'userA@nbpdcl-saas.com',
            'status' => 'active',
        ]);
        $this->userA->assignRole($userRole);

        $this->userB = User::factory()->create([
            'email' => 'userB@nbpdcl-saas.com',
            'status' => 'active',
        ]);
        $this->userB->assignRole($userRole);

        $this->adminUser = User::factory()->create([
            'email' => 'admin@nbpdcl-saas.com',
            'status' => 'active',
        ]);
        $this->adminUser->assignRole($adminRole);
    }

    public function test_suspended_user_cannot_login(): void
    {
        $suspended = User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => bcrypt('password123'),
            'status' => 'suspended',
        ]);

        $response = $this->post('/login', [
            'email' => 'suspended@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_suspended_user_session_is_terminated_by_active_middleware(): void
    {
        $response = $this->actingAs($this->userA)->get('/dashboard');
        $response->assertOk();

        // Admin suspends userA
        $this->userA->status = 'suspended';
        $this->userA->save();

        // Subsequent request is intercepted by EnsureUserIsActive
        $response2 = $this->actingAs($this->userA)->get('/dashboard');
        $response2->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_user_cannot_view_another_users_pdf_via_idor(): void
    {
        Storage::fake('local');
        $pdfPath = "users/{$this->userB->id}/pdfs/2026/8/MRU_TEST/11122233344.pdf";
        Storage::disk('local')->put($pdfPath, '%PDF-1.4 test');

        $billB = BillRecord::withoutGlobalScopes()->create([
            'user_id' => $this->userB->id,
            'ca_number' => '11122233344',
            'billing_month' => 8,
            'billing_year' => 2026,
            'pdf_path' => $pdfPath,
            'download_status' => 'downloaded',
        ]);

        // User A attempts to access User B's PDF
        $response = $this->actingAs($this->userA)->get("/bills/pdf/{$billB->id}");
        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_user_cannot_modify_or_delete_another_users_mru_or_consumer(): void
    {
        $mruB = Mru::withoutGlobalScopes()->create([
            'user_id' => $this->userB->id,
            'code' => 'MRU_B',
            'name' => 'MRU B Name',
            'status' => 'active',
        ]);

        $consumerB = ConsumerAccount::withoutGlobalScopes()->create([
            'user_id' => $this->userB->id,
            'mru_id' => $mruB->id,
            'ca_number' => '99988877766',
            'consumer_name' => 'Victim Consumer',
            'status' => 'active',
        ]);

        $mruA = Mru::withoutGlobalScopes()->create([
            'user_id' => $this->userA->id,
            'code' => 'MRU_A',
            'name' => 'MRU A Name',
            'status' => 'active',
        ]);

        // User A attempts to show User B's MRU
        $responseShow = $this->actingAs($this->userA)->get("/mrus/{$mruB->id}");
        $this->assertTrue(in_array($responseShow->status(), [403, 404]));

        // User A attempts to update User B's MRU
        $responseUpdate = $this->actingAs($this->userA)->put("/mrus/{$mruB->id}", [
            'name' => 'Hacked Name',
            'code' => 'HACKED',
            'status' => 'active',
        ]);
        $this->assertTrue(in_array($responseUpdate->status(), [403, 404]));

        // User A attempts to add consumer to User B's MRU
        $responseAdd = $this->actingAs($this->userA)->post("/mrus/{$mruB->id}/consumers", [
            'ca_number' => '12345678901',
        ]);
        $this->assertTrue(in_array($responseAdd->status(), [403, 404]));

        // User A attempts to delete User B's consumer via MRU A route
        $responseDelete = $this->actingAs($this->userA)->delete("/mrus/{$mruA->id}/consumers/{$consumerB->id}");
        $this->assertTrue(in_array($responseDelete->status(), [403, 404]));

        // User B's consumer remains intact
        $this->assertDatabaseHas('consumer_accounts', ['id' => $consumerB->id]);
    }

    public function test_status_and_remark_updates_synchronize_bill_records_and_bill_statuses(): void
    {
        $bill = BillRecord::create([
            'user_id' => $this->userA->id,
            'ca_number' => '10020030040',
            'billing_month' => 8,
            'billing_year' => 2026,
            'review_status' => 'pending',
            'remark' => null,
        ]);

        // 1. Update status via dashboard endpoint
        $responseStatus = $this->actingAs($this->userA)->postJson('/bills/review-status', [
            'id' => $bill->id,
            'review_status' => 'critical',
        ]);
        $responseStatus->assertOk();

        $this->assertDatabaseHas('bill_records', [
            'id' => $bill->id,
            'review_status' => 'critical',
        ]);
        $this->assertDatabaseHas('bill_statuses', [
            'user_id' => $this->userA->id,
            'ca_number' => '10020030040',
            'status' => 'critical',
        ]);

        // 2. Update remark via dashboard endpoint
        $responseRemark = $this->actingAs($this->userA)->postJson('/bills/update-remark', [
            'id' => $bill->id,
            'remark' => 'Meter burnt inspection needed',
        ]);
        $responseRemark->assertOk();

        $this->assertDatabaseHas('bill_records', [
            'id' => $bill->id,
            'remark' => 'Meter burnt inspection needed',
        ]);
        $this->assertDatabaseHas('bill_statuses', [
            'user_id' => $this->userA->id,
            'ca_number' => '10020030040',
            'remark' => 'Meter burnt inspection needed',
        ]);
    }

    public function test_like_search_wildcard_escaping(): void
    {
        $response = $this->actingAs($this->userA)->getJson('/dashboard/data?search=' . urlencode('%_\\'));
        $response->assertOk();
        $this->assertTrue($response->json('success'));
    }
}
