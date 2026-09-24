<?php

namespace Tests\Feature\Api;

use App\Models\ApiKey;
use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\MeterReadingHistory;
use App\Models\Mru;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestApiAndSyncTest extends TestCase
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
            'email' => 'apitest@nbpdcl.test',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $keyResult = ApiKey::generate($this->user, 'Test Suite Key');
        $this->plainApiKey = $keyResult['plainTextToken'];

        $this->mru = Mru::create([
            'user_id' => $this->user->id,
            'code' => '9901',
            'name' => 'API_TEST_VILLAGE',
            'status' => 'active',
        ]);
    }

    public function test_api_authentication_requires_valid_key(): void
    {
        // 1. Missing key -> 401
        $res = $this->getJson('/api/v1/auth/me');
        $res->assertStatus(401);

        // 2. Invalid key -> 401
        $res = $this->withHeader('X-API-Key', 'nbp_live_invalidkey123456789')
            ->getJson('/api/v1/auth/me');
        $res->assertStatus(401);

        // 3. Valid X-API-Key header -> 200
        $res = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->getJson('/api/v1/auth/me');
        $res->assertStatus(200)
            ->assertJsonPath('user.email', 'apitest@nbpdcl.test');

        // 4. Valid Authorization: Bearer <key> header -> 200
        $res = $this->withHeader('Authorization', "Bearer {$this->plainApiKey}")
            ->getJson('/api/v1/auth/me');
        $res->assertStatus(200);
    }

    public function test_mobile_login_endpoint_returns_token(): void
    {
        $res = $this->postJson('/api/v1/auth/login', [
            'email' => 'apitest@nbpdcl.test',
            'password' => 'secret123',
            'device_name' => 'Field Phone RedMi 12',
        ]);

        $res->assertStatus(200)
            ->assertJsonStructure(['success', 'token', 'token_type', 'user']);

        $token = $res->json('token');
        $this->assertNotEmpty($token);

        // Verify the newly generated token can access authenticated routes
        $authCheck = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');
        $authCheck->assertStatus(200);
    }

    public function test_api_key_management_lifecycle(): void
    {
        // 1. Create a new key via API
        $createRes = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Python Field Laptop',
                'expires_in_days' => 30,
            ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['api_key' => ['id', 'name', 'key_prefix']]);

        $newKeyId = $createRes->json('api_key.id');
        $newPlainKey = $createRes->json('plain_text_key');

        // 2. Test authenticating with the new key
        $checkRes = $this->withHeader('X-API-Key', $newPlainKey)->getJson('/api/v1/auth/me');
        $checkRes->assertStatus(200);

        // 3. List keys
        $listRes = $this->withHeader('X-API-Key', $this->plainApiKey)->getJson('/api/v1/api-keys');
        $listRes->assertStatus(200);
        $this->assertCount(2, $listRes->json('api_keys'));

        // 4. Revoke key
        $delRes = $this->withHeader('X-API-Key', $this->plainApiKey)->deleteJson("/api/v1/api-keys/{$newKeyId}");
        $delRes->assertStatus(200);

        // 5. Verify revoked key is rejected
        $rejectedRes = $this->withHeader('X-API-Key', $newPlainKey)->getJson('/api/v1/auth/me');
        $rejectedRes->assertStatus(401);
    }

    public function test_get_bills_with_pdcs_priority_sorting(): void
    {
        $ca1 = '990100000001'; // will be submitted
        $ca2 = '990100000002'; // will be pending
        $ca3 = '990100000003'; // will be doubt
        $ca4 = '990100000004'; // will be critical

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca1,
            'billing_month' => 4,
            'billing_year' => 2026,
            'review_status' => 'submitted',
            'total_amount' => 100,
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca2,
            'billing_month' => 4,
            'billing_year' => 2026,
            'review_status' => 'pending',
            'total_amount' => 200,
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca3,
            'billing_month' => 4,
            'billing_year' => 2026,
            'review_status' => 'doubt',
            'total_amount' => 300,
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca4,
            'billing_month' => 4,
            'billing_year' => 2026,
            'review_status' => 'critical',
            'total_amount' => 400,
        ]);

        // Request with status_sort=pdcs (Pending -> Doubt -> Critical -> Submitted)
        $res = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->getJson('/api/v1/bills?mru_id='.$this->mru->id.'&month=4&year=2026&status_sort=pdcs');

        $res->assertStatus(200)
            ->assertJsonPath('counts.all', 4)
            ->assertJsonPath('counts.pending', 1)
            ->assertJsonPath('counts.doubt', 1)
            ->assertJsonPath('counts.critical', 1)
            ->assertJsonPath('counts.submitted', 1);

        $returnedCas = collect($res->json('data'))->pluck('ca_number')->all();
        // Expect: ca2 (pending), ca3 (doubt), ca4 (critical), ca1 (submitted)
        $this->assertEquals([$ca2, $ca3, $ca4, $ca1], $returnedCas);
    }

    public function test_patch_bill_review_updates_reading_and_ledger(): void
    {
        $ca = '990100000010';
        ConsumerAccount::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'consumer_name' => 'MD RAHMAN',
            'status' => 'active',
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'billing_month' => 4,
            'billing_year' => 2026,
            'previous_reading' => '300',
            'working_reading' => '350',
            'units_consumed' => 50,
            'review_status' => 'pending',
        ]);

        // Send review PATCH
        $res = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->patchJson('/api/v1/bills/review', [
                'ca_number' => $ca,
                'billing_month' => 4,
                'billing_year' => 2026,
                'status' => 'submitted',
                'working_reading' => 380,
                'remark' => 'Meter display crystal clear',
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.review_status', 'submitted')
            ->assertJsonPath('data.working_reading', '380');

        // Verify BillRecord updated
        $updatedBill = BillRecord::where('user_id', $this->user->id)->where('ca_number', $ca)->first();
        $this->assertEquals('submitted', $updatedBill->review_status);
        $this->assertEquals('380', $updatedBill->working_reading);
        $this->assertEquals(80, $updatedBill->units_consumed); // 380 - 300 = 80
        $this->assertEquals('Meter display crystal clear', $updatedBill->remark);

        // Verify BillStatus updated
        $status = BillStatus::where('user_id', $this->user->id)->where('ca_number', $ca)->first();
        $this->assertEquals('submitted', $status->status);

        // Verify MeterReadingHistory created
        $history = MeterReadingHistory::where('user_id', $this->user->id)
            ->where('ca_number', $ca)
            ->where('billing_month', 4)
            ->where('billing_year', 2026)
            ->first();
        $this->assertNotNull($history);
        $this->assertEquals('380', $history->working_reading);
        $this->assertEquals(80, $history->units_consumed);

        // Verify ConsumerAccount master ledger updated
        $consumer = ConsumerAccount::where('user_id', $this->user->id)->where('ca_number', $ca)->first();
        $this->assertEquals('380', $consumer->last_working_reading);
        $this->assertEquals(4, $consumer->last_working_month);
    }

    public function test_post_bills_batch_sync_offline_records(): void
    {
        $ca1 = '990100000021';
        $ca2 = '990100000022';

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca1,
            'billing_month' => 4,
            'billing_year' => 2026,
            'previous_reading' => '100',
            'review_status' => 'pending',
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca2,
            'billing_month' => 4,
            'billing_year' => 2026,
            'previous_reading' => '200',
            'review_status' => 'pending',
        ]);

        $res = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->postJson('/api/v1/bills/batch-sync', [
                'billing_month' => 4,
                'billing_year' => 2026,
                'reviews' => [
                    [
                        'ca_number' => $ca1,
                        'status' => 'submitted',
                        'working_reading' => 170,
                        'remark' => 'OK',
                    ],
                    [
                        'ca_number' => $ca2,
                        'status' => 'doubt',
                        'reason_code' => 'PREMISES_LOCKED',
                        'remark' => 'House padlocked',
                    ],
                ],
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('synced_count', 2)
            ->assertJsonPath('failed_count', 0);

        $b1 = BillRecord::where('user_id', $this->user->id)->where('ca_number', $ca1)->first();
        $this->assertEquals('submitted', $b1->review_status);
        $this->assertEquals(70, $b1->units_consumed); // 170 - 100 = 70

        $b2 = BillRecord::where('user_id', $this->user->id)->where('ca_number', $ca2)->first();
        $this->assertEquals('doubt', $b2->review_status);
        $this->assertEquals('PREMISES_LOCKED', $b2->tag);
    }

    public function test_automation_queue_and_status_endpoints(): void
    {
        $ca = '990100000030';
        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'consumer_name' => 'AUTOMATION TEST USER',
            'billing_month' => 5,
            'billing_year' => 2026,
            'previous_reading' => '400',
            'working_reading' => '450',
            'review_status' => 'unsubmitted',
        ]);

        // 1. Fetch queue
        $queueRes = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->getJson('/api/v1/automation/queue?mru_id='.$this->mru->id.'&cycle=MAY-2026');

        $queueRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_in_queue', 1);

        $this->assertEquals($ca, $queueRes->json('consumers.0.ca_number'));

        // 2. Report consumer processed from ADB automation
        $updateRes = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->postJson('/api/v1/automation/update-status', [
                'ca_number' => $ca,
                'status' => 'Submitted',
                'working_reading' => 460,
                'remark' => 'Automated via ADB',
            ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'Submitted')
            ->assertJsonPath('data.working_reading', '460')
            ->assertJsonPath('data.units_consumed', 60); // 460 - 400 = 60
    }

    public function test_flutter_mobile_sync_in_and_sync_out(): void
    {
        $ca = '990100000040';
        ConsumerAccount::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'consumer_name' => 'FLUTTER OFFLINE USER',
            'meter_no' => 'MTR9988',
            'status' => 'active',
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'consumer_name' => 'FLUTTER OFFLINE USER',
            'billing_month' => 6,
            'billing_year' => 2026,
            'previous_reading' => '500',
            'review_status' => 'unsubmitted',
        ]);

        // 1. Sync-In (Download MRU Payload for on-device SQLite)
        $downloadRes = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->getJson('/api/v1/sync/mrus/'.$this->mru->id.'/download');

        $downloadRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('mru.id', $this->mru->id)
            ->assertJsonPath('cycle.month', 6)
            ->assertJsonPath('cycle.year', 2026);

        $consumers = $downloadRes->json('consumers');
        $this->assertCount(1, $consumers);
        $this->assertEquals($ca, $consumers[0]['ca_number']);
        $this->assertEquals(500, $consumers[0]['previous_reading']);

        // 2. Sync-Out (Upload Batch Readings recorded offline)
        $uploadRes = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->postJson('/api/v1/sync/readings/batch', [
                'mru_id' => $this->mru->id,
                'readings' => [
                    [
                        'ca_number' => $ca,
                        'working_reading' => 575,
                        'status' => 'Submitted',
                        'remark' => 'Read offline in village',
                        'recorded_at' => '2026-06-15 11:30:00',
                    ],
                ],
            ]);

        $uploadRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('synced_count', 1);

        $bill = BillRecord::where('user_id', $this->user->id)->where('ca_number', $ca)->first();
        $this->assertEquals('Submitted', $bill->review_status);
        $this->assertEquals('575', $bill->working_reading);
        $this->assertEquals(75, $bill->units_consumed); // 575 - 500 = 75
        $this->assertEquals('flutter_mobile', $bill->reading_source);
    }

    public function test_rate_limiting_provides_headers_and_protects_server(): void
    {
        $res = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->getJson('/api/v1/bills?month=4&year=2026');

        $res->assertStatus(200);
        $res->assertHeader('X-RateLimit-Limit', 240);
        $this->assertTrue($res->headers->has('X-RateLimit-Remaining'));

        $reviewRes = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->patchJson('/api/v1/bills/review', [
                'ca_number' => 'non_existent_ca',
                'billing_month' => 4,
                'billing_year' => 2026,
                'status' => 'submitted',
            ]);

        $reviewRes->assertHeader('X-RateLimit-Limit', 120);
        $this->assertTrue($reviewRes->headers->has('X-RateLimit-Remaining'));
    }
}
