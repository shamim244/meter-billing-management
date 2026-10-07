<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumerMobileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::where('email', 'admin@nbpdcl-saas.com')->first();
        $this->agent = User::where('email', 'test@example.com')->first();
    }

    public function test_user_can_update_individual_consumer_mobile_number(): void
    {
        $mru = Mru::create([
            'user_id' => $this->agent->id,
            'code' => 'MRU_MOB_01',
            'name' => 'MRU Mobile Test',
            'status' => 'active',
        ]);

        $bill = BillRecord::create([
            'user_id' => $this->agent->id,
            'ca_number' => '999000111222',
            'mru_id' => $mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
            'consumer_name' => 'Test Consumer',
            'total_amount' => 500.00,
        ]);

        $response = $this->actingAs($this->agent)
            ->postJson(route('consumers.update-mobile'), [
                'ca_number' => '999000111222',
                'mobile' => '9876543210',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'ca_number' => '999000111222',
                'mobile' => '9876543210',
            ]);

        $this->assertDatabaseHas('consumer_accounts', [
            'ca_number' => '999000111222',
            'mobile' => '9876543210',
            'user_id' => $this->agent->id,
        ]);
    }

    public function test_user_can_clear_consumer_mobile_number(): void
    {
        $consumer = ConsumerAccount::create([
            'user_id' => $this->agent->id,
            'ca_number' => '999000111333',
            'consumer_name' => 'Test Clear',
            'mobile' => '9876543210',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'OK',
        ]);

        $response = $this->actingAs($this->agent)
            ->postJson(route('consumers.update-mobile'), [
                'ca_number' => '999000111333',
                'mobile' => '',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'mobile' => null,
            ]);

        $this->assertDatabaseHas('consumer_accounts', [
            'ca_number' => '999000111333',
            'mobile' => null,
        ]);
    }

    public function test_mobile_number_is_normalized_stripping_non_digits_and_prefix(): void
    {
        $mru = Mru::create([
            'user_id' => $this->agent->id,
            'code' => 'MRU_MOB_02',
            'name' => 'MRU Mobile Test 2',
            'status' => 'active',
        ]);

        BillRecord::create([
            'user_id' => $this->agent->id,
            'ca_number' => '999000111444',
            'mru_id' => $mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
            'consumer_name' => 'Test Normalize',
            'total_amount' => 300.00,
        ]);

        $response = $this->actingAs($this->agent)
            ->postJson(route('consumers.update-mobile'), [
                'ca_number' => '999000111444',
                'mobile' => '+91 91234 56789',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'mobile' => '9123456789',
            ]);

        $this->assertDatabaseHas('consumer_accounts', [
            'ca_number' => '999000111444',
            'mobile' => '9123456789',
        ]);
    }

    public function test_bulk_update_consumer_mobile_from_raw_multiline_text(): void
    {
        $mru = Mru::create([
            'user_id' => $this->agent->id,
            'code' => 'MRU_MOB_03',
            'name' => 'MRU Mobile Bulk',
            'status' => 'active',
        ]);

        BillRecord::create([
            'user_id' => $this->agent->id,
            'ca_number' => '999000111555',
            'mru_id' => $mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
            'consumer_name' => 'Consumer 1',
            'total_amount' => 400.00,
        ]);

        BillRecord::create([
            'user_id' => $this->agent->id,
            'ca_number' => '999000111666',
            'mru_id' => $mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
            'consumer_name' => 'Consumer 2',
            'total_amount' => 600.00,
        ]);

        $rawData = "999000111555, 9876543210\n999000111666, +91 8877665544\n999000999999, 9123456780";

        $response = $this->actingAs($this->agent)
            ->postJson(route('consumers.bulk-update-mobile'), [
                'raw_data' => $rawData,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'updated_count' => 2,
                'not_found_count' => 1,
            ]);

        $this->assertDatabaseHas('consumer_accounts', [
            'ca_number' => '999000111555',
            'mobile' => '9876543210',
        ]);

        $this->assertDatabaseHas('consumer_accounts', [
            'ca_number' => '999000111666',
            'mobile' => '8877665544',
        ]);
    }

    public function test_dashboard_data_includes_mobile_and_allows_searching_by_mobile(): void
    {
        $mru = Mru::create([
            'user_id' => $this->agent->id,
            'code' => 'MRU_MOB_04',
            'name' => 'MRU Search Mobile',
            'status' => 'active',
        ]);

        $bill = BillRecord::create([
            'user_id' => $this->agent->id,
            'ca_number' => '999000111777',
            'mru_id' => $mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
            'consumer_name' => 'Searchable Person',
            'total_amount' => 850.00,
        ]);

        ConsumerAccount::create([
            'user_id' => $this->agent->id,
            'ca_number' => '999000111777',
            'consumer_name' => 'Searchable Person',
            'mobile' => '9988776655',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'OK',
        ]);

        // Request dashboard data
        $response = $this->actingAs($this->agent)
            ->getJson(route('dashboard.data', [
                'mru_id' => $mru->id,
                'billing_month' => 8,
                'billing_year' => 2026,
            ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('9988776655', $data[0]['mobile']);

        // Search by mobile number
        $searchResponse = $this->actingAs($this->agent)
            ->getJson(route('dashboard.data', [
                'mru_id' => $mru->id,
                'billing_month' => 8,
                'billing_year' => 2026,
                'search' => '9988776655',
            ]));

        $searchResponse->assertStatus(200);
        $searchData = $searchResponse->json('data');
        $this->assertCount(1, $searchData);
        $this->assertEquals('999000111777', $searchData[0]['ca_number']);
    }

    public function test_user_cannot_override_another_users_consumer_mobile(): void
    {
        $otherUser = User::factory()->create();

        // Consumer account belongs to otherUser
        $consumer = ConsumerAccount::create([
            'user_id' => $otherUser->id,
            'ca_number' => '999000111888',
            'consumer_name' => 'Other User Consumer',
            'mobile' => '9111111111',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'OK',
        ]);

        // Agent tries to update mobile for CA that belongs to otherUser without having a bill
        $response = $this->actingAs($this->agent)
            ->postJson(route('consumers.update-mobile'), [
                'ca_number' => '999000111888',
                'mobile' => '9222222222',
            ]);

        $response->assertStatus(200);

        // Verify otherUser's consumer mobile was NOT changed
        $this->assertEquals('9111111111', $consumer->fresh()->mobile);
    }
}
