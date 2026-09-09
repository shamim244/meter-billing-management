<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_user_can_view_dashboard(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSeeText('Billing Hub');
    }

    public function test_ajax_dashboard_data_endpoint(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $mru = Mru::create([
            'code' => 'TEST_MRU',
            'name' => 'Test Village',
            'full_identifier' => 'TEST_MRU',
            'status' => 'active',
        ]);

        BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '10230099999',
            'mru_id' => $mru->id,
            'billing_month' => 4,
            'billing_year' => 2026,
            'consumer_name' => 'John Doe',
            'total_amount' => 500.00,
            'units_consumed' => 45,
            'download_status' => 'downloaded',
            'parse_status' => 'parsed',
        ]);

        $response = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026');
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'pagination' => [
                'total' => 1,
            ]
        ]);
        $response->assertJsonFragment([
            'ca_number' => '10230099999',
            'consumer_name' => 'John Doe',
        ]);
    }

    public function test_user_can_update_bill_status(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->postJson('/bills/status', [
            'ca_number' => '10230099999',
            'billing_month' => 4,
            'billing_year' => 2026,
            'status' => 'critical',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'critical',
        ]);

        $this->assertDatabaseHas('bill_statuses', [
            'user_id' => $user->id,
            'ca_number' => '10230099999',
            'billing_month' => 4,
            'billing_year' => 2026,
            'status' => 'critical',
        ]);
    }

    public function test_user_can_save_and_clear_remark(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        // Save remark
        $response = $this->actingAs($user)->postJson('/bills/remark', [
            'ca_number' => '10230099999',
            'billing_month' => 4,
            'billing_year' => 2026,
            'remark' => 'Meter display broken, needs inspection',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'remark' => 'Meter display broken, needs inspection',
        ]);

        $this->assertDatabaseHas('bill_statuses', [
            'user_id' => $user->id,
            'ca_number' => '10230099999',
            'remark' => 'Meter display broken, needs inspection',
        ]);

        // Clear remark
        $response = $this->actingAs($user)->postJson('/bills/remark', [
            'ca_number' => '10230099999',
            'billing_month' => 4,
            'billing_year' => 2026,
            'remark' => '',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'remark' => null,
        ]);
    }

    public function test_user_can_export_csv(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '10230099999',
            'billing_month' => 4,
            'billing_year' => 2026,
            'consumer_name' => 'John Doe',
            'total_amount' => 500.00,
            'units_consumed' => 45,
            'download_status' => 'downloaded',
            'parse_status' => 'parsed',
        ]);

        $response = $this->actingAs($user)->get('/bills/export-csv?month=4&year=2026');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_status_priority_and_field_sorting(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        // Create 2 bills
        BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '10230011111',
            'billing_month' => 4,
            'billing_year' => 2026,
            'consumer_name' => 'Alice',
            'total_amount' => 100.00,
            'units_consumed' => 10,
        ]);

        BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '10230022222',
            'billing_month' => 4,
            'billing_year' => 2026,
            'consumer_name' => 'Bob',
            'total_amount' => 900.00,
            'units_consumed' => 80,
        ]);

        // Alice submitted, Bob critical
        BillStatus::create([
            'user_id' => $user->id,
            'ca_number' => '10230011111',
            'billing_month' => 4,
            'billing_year' => 2026,
            'status' => 'submitted',
        ]);

        BillStatus::create([
            'user_id' => $user->id,
            'ca_number' => '10230022222',
            'billing_month' => 4,
            'billing_year' => 2026,
            'status' => 'critical',
        ]);

        // When status_sort is cdps (critical first), Bob should be first
        $response = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&status_sort=cdps');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('10230022222', $data[0]['ca_number']); // Bob (critical)
        $this->assertEquals('10230011111', $data[1]['ca_number']); // Alice (submitted)

        // When status_sort is spdc (submitted first), Alice should be first
        $response = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&status_sort=spdc');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('10230011111', $data[0]['ca_number']); // Alice (submitted)
        $this->assertEquals('10230022222', $data[1]['ca_number']); // Bob (critical)
    }

    public function test_basis_filtering_and_sorting(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        // Create 3 bills with different basis
        BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '10230011111',
            'billing_month' => 4,
            'billing_year' => 2026,
            'consumer_name' => 'Alice Normal',
            'billing_basis' => 'OK',
            'working_reading' => 1200,
            'total_amount' => 100.00,
            'units_consumed' => 10,
        ]);

        BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '10230022222',
            'billing_month' => 4,
            'billing_year' => 2026,
            'consumer_name' => 'Bob Locked',
            'billing_basis' => 'LK',
            'working_reading' => 1500,
            'total_amount' => 200.00,
            'units_consumed' => 20,
        ]);

        BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '10230033333',
            'billing_month' => 4,
            'billing_year' => 2026,
            'consumer_name' => 'Charlie Defective',
            'billing_basis' => 'MD',
            'working_reading' => 800,
            'total_amount' => 300.00,
            'units_consumed' => 30,
        ]);

        // Filter by LK
        $response = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&basis_filter=LK');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('10230022222', $response->json('data.0.ca_number'));
        $this->assertEquals(1, $response->json('counts.basis_lk'));
        $this->assertEquals(1, $response->json('counts.basis_ok'));
        $this->assertEquals(1, $response->json('counts.basis_md'));

        // Filter by MD
        $response = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&basis_filter=MD');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('10230033333', $response->json('data.0.ca_number'));

        // Sort by basis_priority asc (OK -> LK -> MD)
        $response = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&sort_col=basis_priority&sort_asc=true');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('10230011111', $data[0]['ca_number']); // OK
        $this->assertEquals('10230022222', $data[1]['ca_number']); // LK
        $this->assertEquals('10230033333', $data[2]['ca_number']); // MD

        // Sort by working_reading asc (800 -> 1200 -> 1500)
        $response = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&sort_col=working_reading&sort_asc=true');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('10230033333', $data[0]['ca_number']); // 800
        $this->assertEquals('10230011111', $data[1]['ca_number']); // 1200
        $this->assertEquals('10230022222', $data[2]['ca_number']); // 1500

        // Sort by consumer_name desc (Charlie -> Bob -> Alice)
        $response = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&sort_col=consumer_name&sort_asc=false');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('10230033333', $data[0]['ca_number']); // Charlie
        $this->assertEquals('10230022222', $data[1]['ca_number']); // Bob
        $this->assertEquals('10230011111', $data[2]['ca_number']); // Alice
    }

    public function test_bulk_process_validates_empty_input(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->postJson('/bills/process', [
            'ca_numbers' => 'invalid_input,not_numeric',
        ]);

        $response->assertStatus(422);
    }

    public function test_dashboard_ping_endpoint_returns_ok_and_csrf_token(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->getJson('/dashboard/ping');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'server_time',
            'timestamp',
            'authenticated',
            'csrf_token',
        ]);
        $response->assertJson([
            'status' => 'ok',
            'authenticated' => true,
        ]);
        $this->assertNotEmpty($response->json('csrf_token'));
    }

    public function test_dashboard_ping_endpoint_works_for_unauthenticated_requests(): void
    {
        $response = $this->getJson('/dashboard/ping');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ok',
            'authenticated' => false,
        ]);
        $this->assertNotEmpty($response->json('csrf_token'));
    }

    public function test_ajax_dashboard_data_endpoint_supports_all_and_large_per_page(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $mru = Mru::create([
            'code' => 'TEST_BATCH_MRU',
            'name' => 'Batch Test Village',
            'full_identifier' => 'TEST_BATCH_MRU',
            'status' => 'active',
        ]);

        for ($i = 1; $i <= 60; $i++) {
            BillRecord::create([
                'user_id' => $user->id,
                'ca_number' => '1023009' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'mru_id' => $mru->id,
                'billing_month' => 4,
                'billing_year' => 2026,
                'consumer_name' => "Batch Consumer $i",
                'total_amount' => 100.00 + $i,
                'units_consumed' => 20 + $i,
                'download_status' => 'downloaded',
                'parse_status' => 'parsed',
            ]);
        }

        // Test with per_page=all
        $responseAll = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&per_page=all');
        $responseAll->assertStatus(200);
        $responseAll->assertJson([
            'success' => true,
            'pagination' => [
                'total' => 60,
                'per_page' => 1000,
                'current_page' => 1,
                'last_page' => 1,
            ]
        ]);
        $this->assertCount(60, $responseAll->json('data'));

        // Test with per_page=500
        $response500 = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&per_page=500');
        $response500->assertStatus(200);
        $response500->assertJson([
            'success' => true,
            'pagination' => [
                'total' => 60,
                'per_page' => 500,
                'current_page' => 1,
                'last_page' => 1,
            ]
        ]);
        $this->assertCount(60, $response500->json('data'));
    }
}


