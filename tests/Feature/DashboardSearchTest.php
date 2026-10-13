<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_search_by_ca_number(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => 'TEST_MRU_1',
            'name' => 'Test Village',
            'status' => 'active',
        ]);

        $bill1 = BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '10230011111',
            'mru_id' => $mru->id,
            'billing_month' => 4,
            'billing_year' => 2026,
            'consumer_name' => 'Alice Kumar',
            'meter_no' => 'MTR1001',
            'tariff_category' => 'DS1D',
            'total_amount' => 500.00,
            'units_consumed' => 45,
            'download_status' => 'downloaded',
            'parse_status' => 'parsed',
        ]);

        $bill2 = BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '10230022222',
            'mru_id' => $mru->id,
            'billing_month' => 4,
            'billing_year' => 2026,
            'consumer_name' => 'Bob Sharma',
            'meter_no' => 'MTR2002',
            'tariff_category' => 'NDS1D',
            'total_amount' => 1200.00,
            'units_consumed' => 120,
            'download_status' => 'downloaded',
            'parse_status' => 'parsed',
        ]);

        // Search for bill 1's CA number
        $response = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&search=10230011111');
        $response->assertOk();
        $this->assertTrue($response->json('success'));
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('10230011111', $data[0]['ca_number']);

        // Search for partial name case-insensitive
        $response2 = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&search=sharma');
        $response2->assertOk();
        $data2 = $response2->json('data');
        $this->assertCount(1, $data2);
        $this->assertEquals('10230022222', $data2[0]['ca_number']);

        // Search for meter number uppercase
        $response3 = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&search=MTR1001');
        $response3->assertOk();
        $data3 = $response3->json('data');
        $this->assertCount(1, $data3);
        $this->assertEquals('10230011111', $data3[0]['ca_number']);
    }

    public function test_dashboard_search_matches_consumer_account_fields_even_when_bill_has_empty_fields(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => 'TEST_MRU_2',
            'name' => 'North Area',
            'status' => 'active',
        ]);

        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230033333',
            'consumer_name' => 'Mohammad Shamim',
            'meter_no' => 'MET-998877',
            'mobile' => '9876543210',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'OK',
            'baseline_amount' => 350.00,
        ]);

        // Bill created without consumer_name and without meter_no (as happens when bill is fresh or unparsed)
        BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '10230033333',
            'mru_id' => $mru->id,
            'billing_month' => 4,
            'billing_year' => 2026,
            'consumer_name' => '',
            'meter_no' => '',
            'tariff_category' => '',
            'total_amount' => 0.00,
            'units_consumed' => 0,
            'download_status' => 'pending',
            'parse_status' => 'pending',
        ]);

        // Search by mobile number stored in consumer_accounts
        $responseMobile = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&search=9876543210');
        $responseMobile->assertOk();
        $dataMobile = $responseMobile->json('data');
        $this->assertCount(1, $dataMobile);
        $this->assertEquals('10230033333', $dataMobile[0]['ca_number']);

        // Search by master account consumer name
        $responseName = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&search=Shamim');
        $responseName->assertOk();
        $dataName = $responseName->json('data');
        $this->assertCount(1, $dataName);
        $this->assertEquals('10230033333', $dataName[0]['ca_number']);

        // Search by master account meter number
        $responseMeter = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&search=998877');
        $responseMeter->assertOk();
        $dataMeter = $responseMeter->json('data');
        $this->assertCount(1, $dataMeter);
        $this->assertEquals('10230033333', $dataMeter[0]['ca_number']);
    }

    public function test_dashboard_search_detects_other_period_and_filter_mismatch(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => 'MRU_PRIOR',
            'name' => 'South Sector',
            'status' => 'active',
        ]);

        // Bill exists in month 3, but user is currently viewing month 4
        BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '10230077777',
            'mru_id' => $mru->id,
            'billing_month' => 3,
            'billing_year' => 2026,
            'consumer_name' => 'Prior Month Consumer',
            'meter_no' => 'MTR-PRIOR-01',
            'tariff_category' => 'DS1D',
            'total_amount' => 450.00,
            'units_consumed' => 40,
            'download_status' => 'downloaded',
            'parse_status' => 'parsed',
        ]);

        // Search while on month 4
        $response = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&search=10230077777');
        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
        $otherPeriod = $response->json('other_period_match');
        $this->assertNotNull($otherPeriod);
        $this->assertEquals(3, $otherPeriod['month']);
        $this->assertEquals(2026, $otherPeriod['year']);

        // Another bill exists in month 4 with status 'submitted', but user has filter 'critical' active
        $submittedBill = BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '10230088888',
            'mru_id' => $mru->id,
            'billing_month' => 4,
            'billing_year' => 2026,
            'consumer_name' => 'Submitted User',
            'review_status' => 'submitted',
            'total_amount' => 600.00,
            'units_consumed' => 60,
        ]);

        // Search with filter=critical
        $filterResponse = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&filter=critical&search=10230088888');
        $filterResponse->assertOk();
        $this->assertCount(0, $filterResponse->json('data'));
        $this->assertTrue($filterResponse->json('filter_mismatch'));
        $this->assertEquals(1, $filterResponse->json('total_in_all'));
    }

    public function test_mru_show_search_by_various_fields(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => 'MRU_SHOW_TEST',
            'name' => 'Village West',
            'status' => 'active',
        ]);

        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '10230055555',
            'consumer_name' => 'Geeta Devi',
            'meter_no' => 'MTR-GEETA-99',
            'mobile' => '9123456789',
            'tariff_category' => 'IAS1',
            'billing_basis' => 'LK',
        ]);

        // Search by tariff category IAS1
        $resTariff = $this->actingAs($user)->get(route('mrus.show', ['mru' => $mru->id, 'search' => 'IAS1']));
        $resTariff->assertOk();
        $resTariff->assertSee('10230055555');
        $resTariff->assertSee('Geeta Devi');

        // Search by meter number
        $resMeter = $this->actingAs($user)->get(route('mrus.show', ['mru' => $mru->id, 'search' => 'GEETA']));
        $resMeter->assertOk();
        $resMeter->assertSee('10230055555');
    }
}
