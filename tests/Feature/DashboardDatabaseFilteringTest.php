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

class DashboardDatabaseFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_enterprise_filtering_evaluates_entire_dataset_without_missing_records(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => 'ENT_MRU_01',
            'name' => 'Enterprise Test Village',
            'full_identifier' => 'ENT_MRU_01',
            'status' => 'active',
        ]);

        // Create 80 bills:
        // - 10 critical
        // - 15 doubt
        // - 20 submitted
        // - 35 pending
        for ($i = 1; $i <= 80; $i++) {
            $ca = '1023005'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $basis = ($i <= 12) ? 'LK' : (($i <= 20) ? 'MD' : 'OK');
            $status = ($i <= 10) ? 'critical' : (($i <= 25) ? 'doubt' : (($i <= 45) ? 'submitted' : 'pending'));

            $consumer = ConsumerAccount::create([
                'user_id' => $user->id,
                'mru_id' => $mru->id,
                'ca_number' => $ca,
                'consumer_name' => "Consumer Full {$i}",
                'billing_basis' => $basis,
                'meter_no' => "MTR-{$i}",
                'tariff_category' => 'DS-II',
            ]);

            $bill = BillRecord::create([
                'user_id' => $user->id,
                'mru_id' => $mru->id,
                'ca_number' => $ca,
                'billing_month' => 4,
                'billing_year' => 2026,
                'consumer_name' => "Consumer Full {$i}",
                'total_amount' => 100.00 + ($i * 10),
                'units_consumed' => 20 + $i,
                'billing_basis' => $basis,
                'download_status' => 'downloaded',
                'parse_status' => 'parsed',
            ]);

            // Persist status in BillStatus table to mirror production
            BillStatus::create([
                'user_id' => $user->id,
                'ca_number' => $ca,
                'billing_month' => 4,
                'billing_year' => 2026,
                'status' => $status,
                'tag' => ($i % 5 === 0) ? 'BQC' : 'OK',
            ]);
        }

        // Test 1: Filter by 'critical' across entire 80 records.
        // Must find EXACTLY all 10 critical records, not a partial slice!
        $responseCritical = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&mru_id='.$mru->id.'&filter=critical&per_page=50');
        $responseCritical->assertStatus(200);
        $this->assertEquals(10, $responseCritical->json('pagination.total'), 'Total critical matching must be 10 across whole dataset');
        $this->assertCount(10, $responseCritical->json('data'));
        $this->assertEquals(80, $responseCritical->json('counts.all'));
        $this->assertEquals(10, $responseCritical->json('counts.critical'));
        $this->assertEquals(15, $responseCritical->json('counts.doubt'));
        $this->assertEquals(20, $responseCritical->json('counts.submitted'));
        $this->assertEquals(35, $responseCritical->json('counts.pending'));

        // Test 2: Filter by 'LK' basis across entire 80 records.
        // Must find EXACTLY all 12 LK records!
        $responseLk = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&mru_id='.$mru->id.'&basis_filter=LK&per_page=50');
        $responseLk->assertStatus(200);
        $this->assertEquals(12, $responseLk->json('pagination.total'), 'Total LK matching must be 12 across whole dataset');
        $this->assertCount(12, $responseLk->json('data'));
        $this->assertEquals(12, $responseLk->json('counts.basis_lk'));
        $this->assertEquals(8, $responseLk->json('counts.basis_md'));
        $this->assertEquals(60, $responseLk->json('counts.basis_ok'));

        // Test 3: Filter by 'BQC' tag across entire 80 records (80 / 5 = 16)
        $responseBqc = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&mru_id='.$mru->id.'&tag_filter=BQC&per_page=50');
        $responseBqc->assertStatus(200);
        $this->assertEquals(16, $responseBqc->json('pagination.total'), 'Total BQC tag matching must be 16 across whole dataset');
        $this->assertCount(16, $responseBqc->json('data'));

        // Test 4: Pagination integrity (Page 1 and Page 2 partition all 80 records with per_page=50)
        $responseP1 = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&mru_id='.$mru->id.'&page=1&per_page=50');
        $responseP1->assertStatus(200);
        $this->assertEquals(80, $responseP1->json('pagination.total'));
        $this->assertEquals(2, $responseP1->json('pagination.last_page'));
        $this->assertCount(50, $responseP1->json('data'));

        $responseP2 = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&mru_id='.$mru->id.'&page=2&per_page=50');
        $responseP2->assertStatus(200);
        $this->assertEquals(80, $responseP2->json('pagination.total'));
        $this->assertCount(30, $responseP2->json('data'));

        // Verify zero overlap between page 1 and page 2
        $p1Cas = collect($responseP1->json('data'))->pluck('ca_number')->all();
        $p2Cas = collect($responseP2->json('data'))->pluck('ca_number')->all();
        $this->assertEmpty(array_intersect($p1Cas, $p2Cas), 'Pages must not have duplicate overlapping records');
        $this->assertEquals(80, count(array_unique(array_merge($p1Cas, $p2Cas))), 'Pages 1 and 2 together must cover 100% of all 80 records');
    }

    public function test_sorting_by_amount_asc_and_desc_across_dataset_with_baseline_and_negative_amounts(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => 'MRU_SORT_AMT',
            'name' => 'Sort Test Village',
            'full_identifier' => 'MRU_SORT_AMT',
            'status' => 'active',
        ]);

        // Create consumers with various amounts including advance/negative, zero, and baseline fallback
        $amounts = [-250.00, 0.00, 50.00, 120.00, 450.00, 1500.00];
        foreach ($amounts as $idx => $amt) {
            $ca = '1023007000'.$idx;
            ConsumerAccount::create([
                'user_id' => $user->id,
                'mru_id' => $mru->id,
                'ca_number' => $ca,
                'consumer_name' => "Consumer Sort {$idx}",
                'billing_basis' => 'OK',
                'meter_no' => "MTR-S-{$idx}",
                'tariff_category' => 'DS-II',
                'baseline_amount' => ($amt === 0.00) ? 35.00 : 0.00, // tests baseline fallback for 0 amount
            ]);

            BillRecord::create([
                'user_id' => $user->id,
                'mru_id' => $mru->id,
                'ca_number' => $ca,
                'billing_month' => 4,
                'billing_year' => 2026,
                'consumer_name' => "Consumer Sort {$idx}",
                'total_amount' => $amt,
                'units_consumed' => 50,
                'billing_basis' => 'OK',
                'download_status' => 'downloaded',
                'parse_status' => 'parsed',
            ]);
        }

        // Test Amount ASC
        $resAsc = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&mru_id='.$mru->id.'&sort_col=amount&sort_asc=true&per_page=50');
        $resAsc->assertStatus(200);
        $ascAmounts = collect($resAsc->json('data'))->pluck('total_amount')->map(fn ($v) => (float) $v)->values()->all();

        $this->assertEquals(-250.00, $ascAmounts[0], 'Negative/credit amount must sort first in ASC');
        $this->assertEquals(1500.00, end($ascAmounts), 'Highest amount must sort last in ASC');
        for ($i = 0; $i < count($ascAmounts) - 1; $i++) {
            $this->assertLessThanOrEqual($ascAmounts[$i + 1], $ascAmounts[$i], 'Each amount in ASC must be <= next amount');
        }

        // Test Amount DESC
        $resDesc = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&mru_id='.$mru->id.'&sort_col=amount&sort_asc=false&per_page=50');
        $resDesc->assertStatus(200);
        $descAmounts = collect($resDesc->json('data'))->pluck('total_amount')->map(fn ($v) => (float) $v)->values()->all();

        $this->assertEquals(1500.00, $descAmounts[0], 'Highest amount must sort first in DESC');
        $this->assertEquals(-250.00, end($descAmounts), 'Lowest amount must sort last in DESC');
        for ($i = 0; $i < count($descAmounts) - 1; $i++) {
            $this->assertGreaterThanOrEqual($descAmounts[$i + 1], $descAmounts[$i], 'Each amount in DESC must be >= next amount');
        }
    }

    public function test_sorting_by_amount_is_not_corrupted_by_status_priority(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => 'MRU_STATUS_PRIO',
            'name' => 'Status Priority Test',
            'full_identifier' => 'MRU_STATUS_PRIO',
            'status' => 'active',
        ]);

        // Create bills where high amounts are pending and low amounts are submitted
        // If status_priority inappropriately took precedence, amounts would be fragmented!
        $testData = [
            ['ca' => '10230080001', 'amount' => 5000.00, 'status' => 'pending'],
            ['ca' => '10230080002', 'amount' => 10.00, 'status' => 'submitted'],
            ['ca' => '10230080003', 'amount' => 200.00, 'status' => 'critical'],
            ['ca' => '10230080004', 'amount' => 50.00, 'status' => 'doubt'],
        ];

        foreach ($testData as $td) {
            ConsumerAccount::create([
                'user_id' => $user->id,
                'mru_id' => $mru->id,
                'ca_number' => $td['ca'],
                'consumer_name' => "Consumer {$td['ca']}",
                'billing_basis' => 'OK',
                'meter_no' => "MTR-{$td['ca']}",
                'tariff_category' => 'DS-II',
            ]);

            BillRecord::create([
                'user_id' => $user->id,
                'mru_id' => $mru->id,
                'ca_number' => $td['ca'],
                'billing_month' => 4,
                'billing_year' => 2026,
                'consumer_name' => "Consumer {$td['ca']}",
                'total_amount' => $td['amount'],
                'units_consumed' => 50,
                'billing_basis' => 'OK',
                'download_status' => 'downloaded',
                'parse_status' => 'parsed',
            ]);

            BillStatus::create([
                'user_id' => $user->id,
                'ca_number' => $td['ca'],
                'billing_month' => 4,
                'billing_year' => 2026,
                'status' => $td['status'],
                'tag' => 'OK',
            ]);
        }

        // Request Sort by Amount ASC with status_sort = pdcs
        $res = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&mru_id='.$mru->id.'&sort_col=amount&sort_asc=true&status_sort=pdcs&per_page=50');
        $res->assertStatus(200);

        $retrievedAmounts = collect($res->json('data'))->pluck('total_amount')->map(fn ($v) => (float) $v)->values()->all();

        // Must strictly sort 10, 50, 200, 5000 regardless of pdcs priority!
        $this->assertEquals([10.00, 50.00, 200.00, 5000.00], $retrievedAmounts, 'Global column sort must not be fragmented by status sort');

        // Now test when sort_col is ca_number with status_sort = pdcs -> Must group by status (pending first, submitted last)
        $resStatusSort = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026&mru_id='.$mru->id.'&sort_col=ca_number&sort_asc=true&status_sort=pdcs&per_page=50');
        $resStatusSort->assertStatus(200);

        $statuses = collect($resStatusSort->json('data'))->pluck('review_status')->values()->all();
        $this->assertEquals('pending', $statuses[0], 'First item under PDCS must be pending');
        $this->assertEquals('submitted', end($statuses), 'Last item under PDCS must be submitted');
    }
}
