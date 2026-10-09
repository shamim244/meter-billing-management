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
}
