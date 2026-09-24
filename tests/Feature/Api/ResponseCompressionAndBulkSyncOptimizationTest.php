<?php

namespace Tests\Feature\Api;

use App\Http\Middleware\EnsureCompressedResponse;
use App\Models\ApiKey;
use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\MeterReadingHistory;
use App\Models\Mru;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResponseCompressionAndBulkSyncOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected string $plainApiKey;

    protected Mru $mru;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->user = User::factory()->create([
            'email' => 'opt_test@nbpdcl.test',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $keyResult = ApiKey::generate($this->user, 'Optimization Test Suite Key');
        $this->plainApiKey = $keyResult['plainTextToken'];

        $this->mru = Mru::create([
            'user_id' => $this->user->id,
            'code' => '8801',
            'name' => 'OPTIMIZATION_TEST_VILLAGE',
            'status' => 'active',
        ]);
    }

    /**
     * Phase 1: Test Gzip response compression when client sends Accept-Encoding: gzip.
     */
    public function test_api_response_is_compressed_with_gzip_when_requested(): void
    {
        $res = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->withHeader('Accept-Encoding', 'gzip, deflate')
            ->get('/api/v1/auth/me');

        $res->assertStatus(200);
        $res->assertHeader('Content-Encoding', 'gzip');
        $res->assertHeader('Vary', 'Accept-Encoding');

        $rawBody = $res->getContent();
        $this->assertNotEmpty($rawBody);
        $res->assertHeader('Content-Length', (string) strlen($rawBody));

        // Decompress with gzdecode and verify valid JSON
        $decompressed = gzdecode($rawBody);
        $this->assertNotFalse($decompressed, 'gzdecode failed to decompress response body');

        $json = json_decode($decompressed, true);
        $this->assertIsArray($json);
        $this->assertEquals('opt_test@nbpdcl.test', $json['user']['email'] ?? null);
    }

    /**
     * Phase 1: Test Deflate response compression when client sends only Accept-Encoding: deflate.
     */
    public function test_api_response_is_compressed_with_deflate_when_only_deflate_supported(): void
    {
        $res = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->withHeader('Accept-Encoding', 'deflate')
            ->get('/api/v1/auth/me');

        $res->assertStatus(200);
        $res->assertHeader('Content-Encoding', 'deflate');
        $res->assertHeader('Vary', 'Accept-Encoding');

        $rawBody = $res->getContent();
        $this->assertNotEmpty($rawBody);
        $res->assertHeader('Content-Length', (string) strlen($rawBody));

        // Decompress with gzuncompress and verify valid JSON
        $decompressed = gzuncompress($rawBody);
        $this->assertNotFalse($decompressed, 'gzuncompress failed to decompress response body');

        $json = json_decode($decompressed, true);
        $this->assertIsArray($json);
        $this->assertEquals('opt_test@nbpdcl.test', $json['user']['email'] ?? null);
    }

    /**
     * Phase 1: Test that responses are uncompressed when Accept-Encoding is omitted.
     */
    public function test_api_response_is_not_compressed_without_accept_encoding_header(): void
    {
        $res = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->getJson('/api/v1/auth/me');

        $res->assertStatus(200);
        $this->assertFalse($res->headers->has('Content-Encoding'));

        $json = $res->json();
        $this->assertEquals('opt_test@nbpdcl.test', $json['user']['email'] ?? null);
    }

    /**
     * Phase 2: Test batch sync with bulk upserts, verifying bounded query count (no N+1).
     */
    public function test_batch_sync_performs_bulk_writes_without_n_plus_one_queries(): void
    {
        $batchSize = 10;
        $reviews = [];

        for ($i = 1; $i <= $batchSize; $i++) {
            $ca = sprintf('8801000000%02d', $i);

            ConsumerAccount::create([
                'user_id' => $this->user->id,
                'mru_id' => $this->mru->id,
                'ca_number' => $ca,
                'consumer_name' => "BATCH CONSUMER {$i}",
                'status' => 'active',
            ]);

            BillRecord::create([
                'user_id' => $this->user->id,
                'mru_id' => $this->mru->id,
                'ca_number' => $ca,
                'billing_month' => 5,
                'billing_year' => 2026,
                'previous_reading' => (string) (100 * $i),
                'review_status' => 'pending',
            ]);

            $reviews[] = [
                'ca_number' => $ca,
                'status' => $i % 2 === 0 ? 'submitted' : 'doubt',
                'reason_code' => $i % 2 === 0 ? null : 'PREMISES_LOCKED',
                'remark' => "Bulk review entry {$i}",
                'working_reading' => (100 * $i) + 50,
            ];
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $res = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->postJson('/api/v1/bills/batch-sync', [
                'billing_month' => 5,
                'billing_year' => 2026,
                'reviews' => $reviews,
            ]);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_received', $batchSize)
            ->assertJsonPath('synced_count', $batchSize)
            ->assertJsonPath('failed_count', 0);

        // Without bulk operations, 10 records would require 40+ to 70+ sequential queries.
        // With bulk pre-fetch and upsert, total queries should be well below 20 (typically ~10 including auth & telemetry).
        $this->assertLessThanOrEqual(15, count($queries), 'Query count exceeds expected bulk write threshold');

        // Verify data integrity across all 10 records
        for ($i = 1; $i <= $batchSize; $i++) {
            $ca = sprintf('8801000000%02d', $i);
            $expectedStatus = ($i % 2 === 0) ? 'submitted' : 'doubt';
            $expectedReading = (string) ((100 * $i) + 50);

            $bill = BillRecord::where('user_id', $this->user->id)->where('ca_number', $ca)->first();
            $this->assertNotNull($bill);
            $this->assertEquals($expectedStatus, $bill->review_status);
            $this->assertEquals($expectedReading, $bill->working_reading);
            $this->assertEquals(50, $bill->units_consumed);
            $this->assertEquals("Bulk review entry {$i}", $bill->remark);

            $status = BillStatus::where('user_id', $this->user->id)->where('ca_number', $ca)->first();
            $this->assertNotNull($status);
            $this->assertEquals($expectedStatus, $status->status);

            $history = MeterReadingHistory::where('user_id', $this->user->id)
                ->where('ca_number', $ca)
                ->where('billing_month', 5)
                ->where('billing_year', 2026)
                ->where('reading_source', 'working')
                ->first();
            $this->assertNotNull($history);
            $this->assertEquals($expectedReading, $history->working_reading);
            $this->assertEquals(50, $history->units_consumed);

            $consumer = ConsumerAccount::where('user_id', $this->user->id)->where('ca_number', $ca)->first();
            $this->assertNotNull($consumer);
            $this->assertEquals($expectedReading, $consumer->last_working_reading);
            $this->assertEquals(5, $consumer->last_working_month);
            $this->assertEquals(2026, $consumer->last_working_year);
        }
    }

    /**
     * Phase 2: Test mobile sync uploadBatchReadings with bulk prefetch & bulk upserts.
     */
    public function test_mobile_sync_batch_upload_performs_bulk_upserts(): void
    {
        $batchCount = 5;
        $readings = [];

        for ($i = 1; $i <= $batchCount; $i++) {
            $ca = sprintf('88010000005%d', $i);

            ConsumerAccount::create([
                'user_id' => $this->user->id,
                'mru_id' => $this->mru->id,
                'ca_number' => $ca,
                'consumer_name' => "MOBILE USER {$i}",
                'status' => 'active',
            ]);

            BillRecord::create([
                'user_id' => $this->user->id,
                'mru_id' => $this->mru->id,
                'ca_number' => $ca,
                'billing_month' => 6,
                'billing_year' => 2026,
                'previous_reading' => '200',
                'review_status' => 'unsubmitted',
            ]);

            $readings[] = [
                'ca_number' => $ca,
                'working_reading' => 240 + $i,
                'status' => 'Submitted',
                'remark' => "Mobile offline sync {$i}",
                'recorded_at' => '2026-06-20 14:00:00',
            ];
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $res = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->postJson('/api/v1/sync/readings/batch', [
                'mru_id' => $this->mru->id,
                'readings' => $readings,
            ]);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_received', $batchCount)
            ->assertJsonPath('synced_count', $batchCount)
            ->assertJsonPath('failed_count', 0);

        $this->assertLessThanOrEqual(15, count($queries), 'Mobile sync query count exceeds bulk threshold');

        for ($i = 1; $i <= $batchCount; $i++) {
            $ca = sprintf('88010000005%d', $i);
            $bill = BillRecord::where('user_id', $this->user->id)->where('ca_number', $ca)->first();

            $this->assertNotNull($bill);
            $this->assertEquals('Submitted', $bill->review_status);
            $this->assertEquals((string) (240 + $i), $bill->working_reading);
            $this->assertEquals(40 + $i, $bill->units_consumed);
            $this->assertEquals('flutter_mobile', $bill->reading_source);

            $status = BillStatus::where('user_id', $this->user->id)->where('ca_number', $ca)->first();
            $this->assertEquals('Submitted', $status->status);

            $history = MeterReadingHistory::where('user_id', $this->user->id)
                ->where('ca_number', $ca)
                ->where('billing_month', 6)
                ->where('billing_year', 2026)
                ->where('reading_source', 'working')
                ->first();
            $this->assertNotNull($history);
            $this->assertEquals((string) (240 + $i), $history->working_reading);
            $this->assertTrue((bool) $history->is_closed);
        }
    }

    /**
     * Phase 2: Test mixed batch sync where some CAs exist and some do not.
     */
    public function test_batch_sync_handles_mixed_valid_and_missing_cas(): void
    {
        $validCa = '880100000091';
        $missingCa = '880100000099';

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $validCa,
            'billing_month' => 4,
            'billing_year' => 2026,
            'previous_reading' => '500',
            'review_status' => 'pending',
        ]);

        $res = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->postJson('/api/v1/bills/batch-sync', [
                'billing_month' => 4,
                'billing_year' => 2026,
                'reviews' => [
                    [
                        'ca_number' => $validCa,
                        'status' => 'submitted',
                        'working_reading' => 550,
                    ],
                    [
                        'ca_number' => $missingCa,
                        'status' => 'doubt',
                        'working_reading' => 100,
                    ],
                ],
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_received', 2)
            ->assertJsonPath('synced_count', 1)
            ->assertJsonPath('failed_count', 1)
            ->assertJsonPath('failures.0.ca_number', $missingCa);

        $validBill = BillRecord::where('user_id', $this->user->id)->where('ca_number', $validCa)->first();
        $this->assertEquals('submitted', $validBill->review_status);
        $this->assertEquals('550', $validBill->working_reading);
    }

    /**
     * Phase 2 Edge Case: Test resolution of previous reading from consumer master when bill previous_reading is 0/null.
     */
    public function test_batch_sync_resolves_previous_reading_from_consumer_when_not_on_bill(): void
    {
        $ca = '880100000077';

        ConsumerAccount::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'consumer_name' => 'RESOLVE PREV USER',
            'last_working_reading' => '350',
            'last_working_month' => 3,
            'last_working_year' => 2026,
            'status' => 'active',
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'billing_month' => 4,
            'billing_year' => 2026,
            'previous_reading' => '0',
            'review_status' => 'pending',
        ]);

        $res = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->postJson('/api/v1/bills/batch-sync', [
                'billing_month' => 4,
                'billing_year' => 2026,
                'reviews' => [
                    [
                        'ca_number' => $ca,
                        'status' => 'submitted',
                        'working_reading' => 410,
                        'remark' => 'Resolved from consumer master',
                    ],
                ],
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('synced_count', 1);

        $bill = BillRecord::where('user_id', $this->user->id)->where('ca_number', $ca)->first();
        $this->assertEquals(350, (int) $bill->previous_reading);
        $this->assertEquals(60, $bill->units_consumed); // 410 - 350 = 60
        $this->assertEquals('submitted', $bill->review_status);

        $consumer = ConsumerAccount::where('user_id', $this->user->id)->where('ca_number', $ca)->first();
        $this->assertEquals('410', $consumer->last_working_reading);
        $this->assertEquals(4, $consumer->last_working_month);
    }

    /**
     * Phase 2 Edge Case: Duplicate CAs in the same payload should be upserted cleanly without SQL constraint violation.
     */
    public function test_batch_sync_deduplicates_duplicate_cas_in_same_payload(): void
    {
        $ca = '880100000088';

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'billing_month' => 4,
            'billing_year' => 2026,
            'previous_reading' => '100',
            'review_status' => 'pending',
        ]);

        $res = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->postJson('/api/v1/bills/batch-sync', [
                'billing_month' => 4,
                'billing_year' => 2026,
                'reviews' => [
                    [
                        'ca_number' => $ca,
                        'status' => 'pending',
                        'working_reading' => 120,
                        'remark' => 'First attempt',
                    ],
                    [
                        'ca_number' => $ca,
                        'status' => 'submitted',
                        'working_reading' => 150,
                        'remark' => 'Final confirmed reading',
                    ],
                ],
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('total_received', 2)
            ->assertJsonPath('synced_count', 2);

        $bill = BillRecord::where('user_id', $this->user->id)->where('ca_number', $ca)->first();
        $this->assertEquals('submitted', $bill->review_status);
        $this->assertEquals('150', $bill->working_reading);
        $this->assertEquals(50, $bill->units_consumed);
        $this->assertEquals('Final confirmed reading', $bill->remark);
    }

    /**
     * Phase 1 RFC Edge Case: Quality value q=0 explicitly forbids gzip.
     */
    public function test_compression_is_skipped_when_client_specifies_gzip_q_zero(): void
    {
        $res = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->withHeader('Accept-Encoding', 'gzip;q=0')
            ->get('/api/v1/auth/me');

        $res->assertStatus(200);
        $this->assertFalse($res->headers->has('Content-Encoding'), 'Response should not be compressed when gzip has q=0');

        $json = $res->json();
        $this->assertEquals('opt_test@nbpdcl.test', $json['user']['email'] ?? null);
    }

    /**
     * Phase 1 RFC Edge Case: gzip has q=0 but deflate has q > 0 -> must choose deflate.
     */
    public function test_compression_chooses_deflate_when_gzip_has_q_zero_and_deflate_is_allowed(): void
    {
        $res = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->withHeader('Accept-Encoding', 'gzip;q=0, deflate;q=0.8')
            ->get('/api/v1/auth/me');

        $res->assertStatus(200);
        $res->assertHeader('Content-Encoding', 'deflate');

        $decompressed = gzuncompress($res->getContent());
        $this->assertNotFalse($decompressed);
        $json = json_decode($decompressed, true);
        $this->assertEquals('opt_test@nbpdcl.test', $json['user']['email'] ?? null);
    }

    /**
     * Phase 1 Content-Type Edge Case: Non-compressible media types (e.g. PDF/images) must not be compressed.
     */
    public function test_compression_skips_non_compressible_content_types(): void
    {
        $mw = new EnsureCompressedResponse;
        $req = Request::create('/api/v1/export/pdf', 'GET', [], [], [], [
            'HTTP_ACCEPT_ENCODING' => 'gzip',
        ]);

        $res = $mw->handle($req, fn () => response('%PDF-1.4 mock binary content', 200, [
            'Content-Type' => 'application/pdf',
        ]));

        $this->assertFalse($res->headers->has('Content-Encoding'));
        $this->assertEquals('%PDF-1.4 mock binary content', $res->getContent());
    }

    /**
     * Phase 2 Edge Case: Mobile batch sync preserves reason_code and tag on both bill_records and bill_statuses.
     */
    public function test_mobile_sync_preserves_reason_code_and_tag(): void
    {
        $ca = '880100000066';

        ConsumerAccount::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'consumer_name' => 'TAG TEST USER',
            'status' => 'active',
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'billing_month' => 6,
            'billing_year' => 2026,
            'previous_reading' => '200',
            'review_status' => 'unsubmitted',
        ]);

        $res = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->postJson('/api/v1/sync/readings/batch', [
                'mru_id' => $this->mru->id,
                'readings' => [
                    [
                        'ca_number' => $ca,
                        'working_reading' => 250,
                        'status' => 'doubt',
                        'reason_code' => 'PREMISES_LOCKED',
                        'remark' => 'Gate locked on visit',
                    ],
                ],
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('synced_count', 1);

        $bill = BillRecord::where('user_id', $this->user->id)->where('ca_number', $ca)->first();
        $this->assertNotNull($bill);
        $this->assertEquals('doubt', $bill->review_status);
        $this->assertEquals('PREMISES_LOCKED', $bill->tag);
        $this->assertEquals('Gate locked on visit', $bill->remark);

        $status = BillStatus::where('user_id', $this->user->id)->where('ca_number', $ca)->first();
        $this->assertNotNull($status);
        $this->assertEquals('doubt', $status->status);
        $this->assertEquals('PREMISES_LOCKED', $status->tag);
        $this->assertEquals('Gate locked on visit', $status->remark);
    }

    /**
     * Phase 2 Edge Case: Status-only batch update on existing reading closes history when submitted.
     */
    public function test_batch_sync_status_only_update_closes_meter_history_when_submitted(): void
    {
        $ca = '880100000044';

        ConsumerAccount::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'consumer_name' => 'STATUS ONLY USER',
            'status' => 'active',
        ]);

        $bill = BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'billing_month' => 5,
            'billing_year' => 2026,
            'previous_reading' => '300',
            'working_reading' => '350',
            'units_consumed' => 50,
            'review_status' => 'doubt',
        ]);

        MeterReadingHistory::create([
            'user_id' => $this->user->id,
            'ca_number' => $ca,
            'mru_id' => $this->mru->id,
            'consumer_id' => null,
            'bill_record_id' => $bill->id,
            'billing_month' => 5,
            'billing_year' => 2026,
            'reading_source' => 'working',
            'previous_reading' => '300',
            'working_reading' => '350',
            'units_consumed' => 50,
            'is_closed' => false,
        ]);

        // Submit via batchSync without passing working_reading
        $res = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->postJson('/api/v1/bills/batch-sync', [
                'billing_month' => 5,
                'billing_year' => 2026,
                'reviews' => [
                    [
                        'ca_number' => $ca,
                        'status' => 'submitted',
                        'remark' => 'Promoted to submitted without changing reading',
                    ],
                ],
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('synced_count', 1);

        $updatedBill = BillRecord::where('user_id', $this->user->id)->where('ca_number', $ca)->first();
        $this->assertEquals('submitted', $updatedBill->review_status);
        $this->assertEquals('350', $updatedBill->working_reading);

        $history = MeterReadingHistory::where('user_id', $this->user->id)
            ->where('ca_number', $ca)
            ->where('billing_month', 5)
            ->where('billing_year', 2026)
            ->where('reading_source', 'working')
            ->first();

        $this->assertNotNull($history);
        $this->assertTrue((bool) $history->is_closed, 'History must be marked as closed when status changes to submitted');
    }
}
