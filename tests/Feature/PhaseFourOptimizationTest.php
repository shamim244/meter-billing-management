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

class PhaseFourOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Mru $mru;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->user = User::factory()->create([
            'email' => 'phase4_operator@test.local',
            'status' => 'active',
        ]);

        $this->mru = Mru::create([
            'user_id' => $this->user->id,
            'code' => 'P4_MRU_01',
            'name' => 'Phase 4 Test Village',
            'full_identifier' => 'P4_MRU_01',
            'status' => 'active',
        ]);
    }

    public function test_openapi_schema_returns_etag_and_handles_304_not_modified(): void
    {
        $response = $this->get('/api/v1/openapi.json');

        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('ETag'));
        $etag = $response->headers->get('ETag');
        $this->assertNotEmpty($etag);

        // Conditional GET with identical If-None-Match should return 304 Not Modified
        $conditionalResponse = $this->withHeader('If-None-Match', $etag)->get('/api/v1/openapi.json');
        $conditionalResponse->assertStatus(304);
        $this->assertEmpty($conditionalResponse->getContent());
    }

    public function test_docs_portal_returns_etag_and_handles_304_not_modified(): void
    {
        $response = $this->actingAs($this->user)->get('/docs/api');

        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('ETag'));
        $etag = $response->headers->get('ETag');
        $this->assertNotEmpty($etag);

        // Conditional GET with identical If-None-Match should return 304 Not Modified
        $conditionalResponse = $this->actingAs($this->user)
            ->withHeader('If-None-Match', $etag)
            ->get('/docs/api');

        $conditionalResponse->assertStatus(304);
        $this->assertEmpty($conditionalResponse->getContent());
    }

    public function test_dashboard_consolidated_kpi_counts_maintain_exact_parity(): void
    {
        // Populate test records with known distribution
        $ca1 = '100400000001';
        $ca2 = '100400000002';
        $ca3 = '100400000003';
        $ca4 = '100400000004';

        ConsumerAccount::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca1,
            'consumer_name' => 'Consumer One',
            'status' => 'active',
        ]);
        ConsumerAccount::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca2,
            'consumer_name' => 'Consumer Two',
            'status' => 'active',
        ]);
        ConsumerAccount::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca3,
            'consumer_name' => 'Consumer Three',
            'status' => 'active',
        ]);
        ConsumerAccount::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca4,
            'consumer_name' => 'Consumer Four',
            'status' => 'active',
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca1,
            'billing_month' => 4,
            'billing_year' => 2026,
            'total_amount' => 120.50,
            'units_consumed' => 25,
            'download_status' => 'downloaded',
            'pdf_path' => 'bills/pdf/1.pdf',
        ]);
        BillStatus::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca1,
            'billing_month' => 4,
            'billing_year' => 2026,
            'status' => 'submitted',
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca2,
            'billing_month' => 4,
            'billing_year' => 2026,
            'total_amount' => 340.00,
            'units_consumed' => 60,
            'download_status' => 'downloaded',
            'pdf_path' => 'bills/pdf/2.pdf',
        ]);
        BillStatus::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca2,
            'billing_month' => 4,
            'billing_year' => 2026,
            'status' => 'critical',
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca3,
            'billing_month' => 4,
            'billing_year' => 2026,
            'total_amount' => 210.00,
            'units_consumed' => 35,
            'download_status' => 'pending',
            'pdf_path' => null, // missing pdf
        ]);
        BillStatus::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca3,
            'billing_month' => 4,
            'billing_year' => 2026,
            'status' => 'doubt',
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca4,
            'billing_month' => 4,
            'billing_year' => 2026,
            'total_amount' => 500.00,
            'units_consumed' => 80,
            'download_status' => 'downloaded',
            'pdf_path' => 'bills/pdf/4.pdf',
            // pending review status
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard?mru_id='.$this->mru->id.'&month=4&year=2026');
        $response->assertStatus(200);

        // Verify view data variables computed via consolidated queries
        $viewData = $response->original->getData();

        $this->assertEquals(4, $viewData['totalPeriodBills']);
        $this->assertEquals(1170.50, $viewData['totalPeriodAmount']);
        $this->assertEquals(200, $viewData['totalPeriodUnits']);

        $statusCounts = $viewData['statusCounts'];
        $this->assertEquals(1, $statusCounts['submitted']);
        $this->assertEquals(1, $statusCounts['critical']);
        $this->assertEquals(1, $statusCounts['doubt']);
        $this->assertEquals(1, $statusCounts['pending']);
        $this->assertEquals(1, $statusCounts['missing_pdf']);
    }

    public function test_bill_api_index_sql_acceleration_and_pagination(): void
    {
        $apiKey = 'nbp_live_'.bin2hex(random_bytes(16));
        $this->user->apiKeys()->create([
            'name' => 'Phase 4 Key',
            'key_hash' => hash('sha256', $apiKey),
            'key_prefix' => substr($apiKey, 0, 12),
        ]);

        // Create 15 bills across different review statuses
        for ($i = 1; $i <= 15; $i++) {
            $status = match ($i % 4) {
                0 => 'submitted',
                1 => 'doubt',
                2 => 'critical',
                default => 'pending',
            };

            BillRecord::create([
                'user_id' => $this->user->id,
                'mru_id' => $this->mru->id,
                'ca_number' => sprintf('2004000000%02d', $i),
                'consumer_name' => "Consumer {$i}",
                'billing_month' => 5,
                'billing_year' => 2026,
                'review_status' => $status,
                'total_amount' => $i * 100,
                'units_consumed' => $i * 10,
            ]);
        }

        // Test page 1 with per_page = 5
        $res = $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/v1/bills?mru_id='.$this->mru->id.'&month=5&year=2026&per_page=5&page=1&sort_col=ca_number&sort_asc=true');

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('counts.all', 15)
            ->assertJsonPath('pagination.total', 15)
            ->assertJsonPath('pagination.per_page', 5)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.last_page', 3);

        $data = $res->json('data');
        $this->assertCount(5, $data);
        $this->assertEquals('200400000001', $data[0]['ca_number']);
        $this->assertEquals('200400000005', $data[4]['ca_number']);

        // Test page 2
        $resPage2 = $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/v1/bills?mru_id='.$this->mru->id.'&month=5&year=2026&per_page=5&page=2&sort_col=ca_number&sort_asc=true');

        $resPage2->assertStatus(200)
            ->assertJsonPath('pagination.current_page', 2);
        $dataPage2 = $resPage2->json('data');
        $this->assertCount(5, $dataPage2);
        $this->assertEquals('200400000006', $dataPage2[0]['ca_number']);
        $this->assertEquals('200400000010', $dataPage2[4]['ca_number']);
    }
}
