<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_admin_can_access_dashboard_and_see_agent_mrus(): void
    {
        $agent = User::factory()->create(['status' => 'active']);
        $agent->assignRole('user');

        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $mru = Mru::create([
            'user_id' => $agent->id,
            'code' => 'MRU_AGENT_1',
            'name' => 'Agent Village One',
            'full_identifier' => 'MRU_AGENT_1',
            'status' => 'active',
        ]);

        BillRecord::create([
            'user_id' => $agent->id,
            'mru_id' => $mru->id,
            'ca_number' => '10290000001',
            'consumer_name' => 'Agent Consumer One',
            'billing_month' => 4,
            'billing_year' => 2026,
            'total_amount' => 650.00,
            'current_reading' => 100,
            'previous_reading' => 50,
            'units_consumed' => 50,
        ]);

        $response = $this->actingAs($admin)->get('/dashboard?mru_id='.$mru->id);

        $response->assertStatus(200);
        $response->assertSee('Agent Village One');
    }

    public function test_admin_can_fetch_dashboard_data_for_agent_mru(): void
    {
        $agent = User::factory()->create(['status' => 'active']);
        $agent->assignRole('user');

        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $mru = Mru::create([
            'user_id' => $agent->id,
            'code' => 'MRU_AGENT_DATA',
            'name' => 'Agent Village Data',
            'full_identifier' => 'MRU_AGENT_DATA',
            'status' => 'active',
        ]);

        $bill = BillRecord::create([
            'user_id' => $agent->id,
            'mru_id' => $mru->id,
            'ca_number' => '10290000002',
            'consumer_name' => 'Agent Consumer Data',
            'billing_month' => 5,
            'billing_year' => 2026,
            'total_amount' => 1200.00,
            'current_reading' => 200,
            'previous_reading' => 100,
            'units_consumed' => 100,
        ]);

        $response = $this->actingAs($admin)->getJson('/dashboard/data?month=5&year=2026&mru_id='.$mru->id);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'pagination' => [
                'total' => 1,
            ],
        ]);
        $response->assertJsonFragment([
            'ca_number' => '10290000002',
        ]);
    }

    public function test_admin_can_update_working_reading_on_agent_bill(): void
    {
        $agent = User::factory()->create(['status' => 'active']);
        $agent->assignRole('user');

        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $mru = Mru::create([
            'user_id' => $agent->id,
            'code' => 'MRU_AGENT_WORK',
            'name' => 'Agent Village Work',
            'full_identifier' => 'MRU_AGENT_WORK',
            'status' => 'active',
        ]);

        $consumer = ConsumerAccount::create([
            'user_id' => $agent->id,
            'mru_id' => $mru->id,
            'ca_number' => '10290000003',
            'consumer_name' => 'Agent Consumer Work',
            'status' => 'active',
        ]);

        $bill = BillRecord::create([
            'user_id' => $agent->id,
            'mru_id' => $mru->id,
            'ca_number' => '10290000003',
            'consumer_name' => 'Agent Consumer Work',
            'billing_month' => 6,
            'billing_year' => 2026,
            'total_amount' => 800.00,
            'current_reading' => 150,
            'previous_reading' => 100,
            'units_consumed' => 50,
        ]);

        $response = $this->actingAs($admin)->postJson('/bills/update-working-reading', [
            'id' => $bill->id,
            'working_reading' => '210',
            'source' => 'manual',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'working_reading' => '210',
        ]);

        $bill->refresh();
        $this->assertEquals('210', $bill->working_reading);

        $consumer->refresh();
        $this->assertEquals('210', $consumer->last_working_reading);
    }

    public function test_admin_can_update_review_status_remark_and_tag_on_agent_bill(): void
    {
        $agent = User::factory()->create(['status' => 'active']);
        $agent->assignRole('user');

        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $mru = Mru::create([
            'user_id' => $agent->id,
            'code' => 'MRU_AGENT_STATUS',
            'name' => 'Agent Village Status',
            'full_identifier' => 'MRU_AGENT_STATUS',
            'status' => 'active',
        ]);

        $bill = BillRecord::create([
            'user_id' => $agent->id,
            'mru_id' => $mru->id,
            'ca_number' => '10290000004',
            'consumer_name' => 'Agent Consumer Status',
            'billing_month' => 7,
            'billing_year' => 2026,
            'total_amount' => 450.00,
        ]);

        // 1. Update review status
        $statusResp = $this->actingAs($admin)->postJson('/bills/review-status', [
            'id' => $bill->id,
            'review_status' => 'submitted',
        ]);
        $statusResp->assertStatus(200);
        $bill->refresh();
        $this->assertEquals('submitted', $bill->review_status);

        $billStatus = BillStatus::where('ca_number', '10290000004')->first();
        $this->assertNotNull($billStatus);
        $this->assertEquals($agent->id, $billStatus->user_id);
        $this->assertEquals('submitted', $billStatus->status);

        // 2. Update remark
        $remarkResp = $this->actingAs($admin)->postJson('/bills/update-remark', [
            'id' => $bill->id,
            'remark' => 'Admin verified reading',
        ]);
        $remarkResp->assertStatus(200);
        $bill->refresh();
        $this->assertEquals('Admin verified reading', $bill->remark);

        // 3. Update tag
        $tagResp = $this->actingAs($admin)->postJson('/bills/tag', [
            'id' => $bill->id,
            'tag' => 'MD',
        ]);
        $tagResp->assertStatus(200);
        $bill->refresh();
        $this->assertEquals('MD', $bill->tag);
    }

    public function test_non_admin_cannot_access_other_agents_bills_on_dashboard(): void
    {
        $agent1 = User::factory()->create(['status' => 'active']);
        $agent1->assignRole('user');

        $agent2 = User::factory()->create(['status' => 'active']);
        $agent2->assignRole('user');

        $mru1 = Mru::create([
            'user_id' => $agent1->id,
            'code' => 'MRU_AGENT_ISOLATE',
            'name' => 'Agent One Isolated',
            'full_identifier' => 'MRU_AGENT_ISOLATE',
            'status' => 'active',
        ]);

        $bill1 = BillRecord::create([
            'user_id' => $agent1->id,
            'mru_id' => $mru1->id,
            'ca_number' => '10290000005',
            'consumer_name' => 'Isolated Consumer',
            'billing_month' => 8,
            'billing_year' => 2026,
            'total_amount' => 999.00,
        ]);

        // Agent 2 requests data for Agent 1's MRU
        $response = $this->actingAs($agent2)->getJson('/dashboard/data?month=8&year=2026&mru_id='.$mru1->id);
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'pagination' => [
                'total' => 0,
            ],
        ]);

        // Agent 2 tries to update Agent 1's bill
        $updateResp = $this->actingAs($agent2)->postJson('/bills/update-working-reading', [
            'id' => $bill1->id,
            'working_reading' => '500',
        ]);
        $updateResp->assertStatus(404);
    }
}
