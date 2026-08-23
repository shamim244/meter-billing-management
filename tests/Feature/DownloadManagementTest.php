<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use App\Services\EngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DownloadManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    public function test_single_ca_download_endpoint_returns_json_and_stores_bill(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => 'TEST_0477',
            'name' => 'Test Gerua',
            'status' => 'active',
        ]);

        // Mock EngineService to avoid hitting real NBPDCL API during testing
        $mockEngine = $this->createMock(EngineService::class);
        $mockEngine->method('downloadAndParseBills')
            ->willReturnCallback(function ($cas, $userId, $month, $year, $mruId) use ($mru) {
                BillRecord::create([
                    'user_id' => $userId,
                    'mru_id' => $mru->id,
                    'ca_number' => $cas[0],
                    'billing_month' => $month,
                    'billing_year' => $year,
                    'consumer_name' => 'Mock Consumer',
                    'total_amount' => 1500.00,
                    'units_consumed' => 45,
                    'download_status' => 'downloaded',
                    'parse_status' => 'parsed',
                    'pdf_path' => "users/{$userId}/pdfs/{$year}/{$month}/TEST_0477/{$cas[0]}.pdf",
                ]);
                return [
                    'total' => 1,
                    'success' => 1,
                    'failed_download' => 0,
                    'failed_parse' => 0,
                    'details' => [],
                ];
            });

        $this->app->instance(EngineService::class, $mockEngine);

        $response = $this->actingAs($user)->postJson(route('bills.download-single'), [
            'ca_number' => '10230046961',
            'billing_month' => 8,
            'billing_year' => 2026,
            'mru_id' => $mru->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonPath('bill.ca_number', '10230046961');
        $response->assertJsonPath('bill.consumer_name', 'Mock Consumer');

        $this->assertDatabaseHas('bill_records', [
            'user_id' => $user->id,
            'ca_number' => '10230046961',
            'billing_month' => 8,
            'billing_year' => 2026,
            'total_amount' => 1500.00,
        ]);
    }

    public function test_sync_missing_only_queries_missing_bills_and_skips_downloaded(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '0477',
            'name' => 'Gerua',
            'status' => 'active',
        ]);

        // 3 consumers: CA1 (downloaded), CA2 (failed), CA3 (not downloaded)
        ConsumerAccount::create(['user_id' => $user->id, 'mru_id' => $mru->id, 'ca_number' => '1001', 'status' => 'active']);
        ConsumerAccount::create(['user_id' => $user->id, 'mru_id' => $mru->id, 'ca_number' => '1002', 'status' => 'active']);
        ConsumerAccount::create(['user_id' => $user->id, 'mru_id' => $mru->id, 'ca_number' => '1003', 'status' => 'active']);

        // CA1 already has downloaded PDF
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1001',
            'billing_month' => 8,
            'billing_year' => 2026,
            'download_status' => 'downloaded',
            'pdf_path' => 'users/1/pdfs/2026/8/0477/1001.pdf',
        ]);

        // CA2 has failed download
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1002',
            'billing_month' => 8,
            'billing_year' => 2026,
            'download_status' => 'failed',
            'pdf_path' => null,
        ]);

        // Engine mock: should ONLY receive CA2 and CA3 (missing/failed), not CA1
        $mockEngine = $this->createMock(EngineService::class);
        $mockEngine->expects($this->once())
            ->method('downloadAndParseBills')
            ->with(
                $this->callback(function ($cas) {
                    return count($cas) === 2 && in_array('1002', $cas) && in_array('1003', $cas) && !in_array('1001', $cas);
                }),
                $user->id,
                8,
                2026,
                $mru->id
            )
            ->willReturn([
                'total' => 2,
                'success' => 2,
                'failed_download' => 0,
                'failed_parse' => 0,
                'details' => [],
            ]);

        $this->app->instance(EngineService::class, $mockEngine);

        $response = $this->actingAs($user)->postJson(route('mrus.sync-missing', $mru), [
            'billing_month' => 8,
            'billing_year' => 2026,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'missing_count' => 2,
            'synced_count' => 2,
        ]);
    }
}
