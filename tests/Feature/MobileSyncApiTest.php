<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\FieldDeskAction;
use App\Models\FieldDeskActivity;
use App\Models\FieldDeskCategory;
use App\Models\Mru;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class MobileSyncApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected string $plainApiKey;

    protected Mru $mru;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        // Safe isolated test user (NEVER User ID 9 / shamim244d@gmail.com)
        $this->user = User::factory()->create([
            'email' => 'mobilesync_tester@nbpdcl.test',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $keyResult = ApiKey::generate($this->user, 'Mobile Test Key');
        $this->plainApiKey = $keyResult['plainTextToken'];

        $this->mru = Mru::create([
            'user_id' => $this->user->id,
            'code' => '8801',
            'name' => 'MRU_MOBILE_TEST',
            'status' => 'active',
        ]);

        FieldDeskCategory::ensureDefaultsExist();
    }

    public function test_bills_archive_requires_authentication(): void
    {
        $response = $this->getJson("/api/v1/sync/mrus/{$this->mru->id}/bills-archive");
        $response->assertStatus(401);
    }

    public function test_bills_archive_returns_404_for_nonexistent_mru(): void
    {
        $response = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->getJson('/api/v1/sync/mrus/999999/bills-archive');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_bills_archive_streams_valid_zip_with_manifest_and_pdfs(): void
    {
        Storage::fake('local');

        $ca1 = '101010101';
        $ca2 = '101010102';

        // Create sample dummy PDF files
        $fakePdfContent1 = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";
        $fakePdfContent2 = "%PDF-1.4\n2 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";

        Storage::disk('local')->put("bills/{$this->user->id}/8801/{$ca1}.pdf", $fakePdfContent1);
        Storage::disk('local')->put("bills/{$this->user->id}/8801/{$ca2}.pdf", $fakePdfContent2);

        BillRecord::create([
            'user_id' => $this->user->id,
            'ca_number' => $ca1,
            'mru_id' => $this->mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
            'consumer_name' => 'Ramesh Kumar',
            'pdf_path' => "bills/{$this->user->id}/8801/{$ca1}.pdf",
            'pdf_filename' => "{$ca1}.pdf",
            'download_status' => 'downloaded',
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'ca_number' => $ca2,
            'mru_id' => $this->mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
            'consumer_name' => 'Suresh Singh',
            'pdf_path' => "bills/{$this->user->id}/8801/{$ca2}.pdf",
            'pdf_filename' => "{$ca2}.pdf",
            'download_status' => 'downloaded',
        ]);

        $response = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->get("/api/v1/sync/mrus/{$this->mru->id}/bills-archive");

        $response->assertStatus(200);
        $this->assertEquals('application/zip', $response->headers->get('content-type'));

        // Capture streamed content into temporary zip file and verify contents
        $tempZip = tempnam(sys_get_temp_dir(), 'test_zip_');
        file_put_contents($tempZip, $response->streamedContent());

        $zip = new ZipArchive;
        $openResult = $zip->open($tempZip);
        $this->assertTrue($openResult === true, 'Downloaded archive must be a valid zip');

        $this->assertNotFalse($zip->locateName('manifest.json'), 'Archive must contain manifest.json');
        $this->assertNotFalse($zip->locateName("{$ca1}.pdf"), "Archive must contain {$ca1}.pdf");
        $this->assertNotFalse($zip->locateName("{$ca2}.pdf"), "Archive must contain {$ca2}.pdf");

        $manifestJson = $zip->getFromName('manifest.json');
        $manifest = json_decode($manifestJson, true);
        $this->assertEquals($this->mru->id, $manifest['mru_id']);
        $this->assertEquals(2, $manifest['archived_pdfs']);

        $zip->close();
        @unlink($tempZip);
    }

    public function test_upload_batch_field_desk_upserts_actions_and_updates_consumer(): void
    {
        $ca = '202020202';

        $consumer = ConsumerAccount::create([
            'user_id' => $this->user->id,
            'ca_number' => $ca,
            'mru_id' => $this->mru->id,
            'consumer_name' => 'Original Name',
            'mobile' => '9123456780',
            'status' => 'active',
        ]);

        $defaultCat = FieldDeskCategory::first();

        $payload = [
            'items' => [
                [
                    'ca_number' => $ca,
                    'mobile' => '9876543210',
                    'category_id' => $defaultCat ? $defaultCat->id : null,
                    'category_code' => 'general_note',
                    'priority' => 'high',
                    'target_date' => now()->addDays(2)->toDateString(),
                    'private_note' => 'Consumer requested meter box replacement. New phone updated.',
                    'latitude' => 25.5941,
                    'longitude' => 85.1376,
                    'location_accuracy' => 4.5,
                    'status' => 'open',
                    'target_amount' => 1250.00,
                    'collected_amount' => 0.00,
                    'mru_id' => $this->mru->id,
                    'recorded_at' => now()->toIso8601String(),
                ],
                [
                    'ca_number' => '303030303',
                    'consumer_name' => 'Auto Created Consumer',
                    'mobile' => '9988776655',
                    'priority' => 'normal',
                    'private_note' => 'Offline spot inspection completed.',
                    'status' => 'completed',
                    'mru_id' => $this->mru->id,
                ],
            ],
        ];

        $response = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->postJson('/api/v1/sync/field-desk/batch', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('synced_count', 2)
            ->assertJsonPath('failed_count', 0);

        // 1. Verify consumer 1 mobile & coordinates updated
        $consumer->refresh();
        $this->assertEquals('9876543210', $consumer->mobile);
        $this->assertEquals(25.5941, (float) $consumer->latitude);
        $this->assertEquals(85.1376, (float) $consumer->longitude);

        // 2. Verify FieldDeskAction created for CA 1
        $action1 = FieldDeskAction::where('user_id', $this->user->id)->where('ca_number', $ca)->first();
        $this->assertNotNull($action1);
        $this->assertEquals('high', $action1->priority);
        $this->assertEquals('9876543210', $action1->mobile);
        $this->assertStringContainsString('meter box replacement', $action1->private_note);

        // 3. Verify FieldDeskActivity logged
        $activity = FieldDeskActivity::where('action_id', $action1->id)->first();
        $this->assertNotNull($activity);
        $this->assertEquals('created', $activity->action_type);

        // 4. Verify consumer 2 was auto-created and action marked completed
        $consumer2 = ConsumerAccount::where('user_id', $this->user->id)->where('ca_number', '303030303')->first();
        $this->assertNotNull($consumer2);
        $this->assertEquals('Auto Created Consumer', $consumer2->consumer_name);

        $action2 = FieldDeskAction::where('user_id', $this->user->id)->where('ca_number', '303030303')->first();
        $this->assertNotNull($action2);
        $this->assertEquals('completed', $action2->status);
        $this->assertNotNull($action2->resolved_at);
    }

    public function test_app_download_landing_page_renders(): void
    {
        $response = $this->get('/download/app');
        $response->assertStatus(200);
        $response->assertSee('NBPDCL Field Reader');
        $response->assertSee('Download APK');
    }

    public function test_upload_batch_readings_updates_bills_and_consumers(): void
    {
        $ca = '505050505';

        ConsumerAccount::create([
            'user_id' => $this->user->id,
            'ca_number' => $ca,
            'mru_id' => $this->mru->id,
            'consumer_name' => 'Batch Test Consumer',
            'status' => 'active',
        ]);

        $bill = BillRecord::create([
            'user_id' => $this->user->id,
            'ca_number' => $ca,
            'mru_id' => $this->mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => 500,
            'working_reading' => null,
            'review_status' => 'unsubmitted',
        ]);

        $payload = [
            'mru_id' => $this->mru->id,
            'readings' => [
                [
                    'ca_number' => $ca,
                    'working_reading' => 580,
                    'status' => 'Submitted',
                    'remark' => 'Meter display crystal clear',
                    'recorded_at' => now()->toIso8601String(),
                ],
            ],
        ];

        $response = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->postJson('/api/v1/sync/readings/batch', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('synced_count', 1);

        $bill->refresh();
        $this->assertEquals('580', (string) $bill->working_reading);
        $this->assertEquals('Submitted', $bill->review_status);
        $this->assertEquals(80, (int) $bill->units_consumed);
        $this->assertEquals('Meter display crystal clear', $bill->remark);

        $consumer = ConsumerAccount::where('user_id', $this->user->id)->where('ca_number', $ca)->first();
        $this->assertEquals('580', (string) $consumer->last_working_reading);
    }

    public function test_upload_batch_readings_with_null_working_reading_preserves_reading(): void
    {
        $ca = '606060606';

        $consumer = ConsumerAccount::create([
            'user_id' => $this->user->id,
            'ca_number' => $ca,
            'mru_id' => $this->mru->id,
            'consumer_name' => 'Locked House Consumer',
            'last_working_reading' => '450',
            'status' => 'active',
        ]);

        $bill = BillRecord::create([
            'user_id' => $this->user->id,
            'ca_number' => $ca,
            'mru_id' => $this->mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => 450,
            'working_reading' => '450',
            'review_status' => 'unsubmitted',
        ]);

        $payload = [
            'mru_id' => $this->mru->id,
            'readings' => [
                [
                    'ca_number' => $ca,
                    'working_reading' => null,
                    'status' => 'Locked',
                    'remark' => 'Main gate locked, nobody home',
                    'recorded_at' => now()->toIso8601String(),
                ],
            ],
        ];

        $response = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->postJson('/api/v1/sync/readings/batch', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('synced_count', 1);

        $bill->refresh();
        $this->assertEquals('Locked', $bill->review_status);
        // Working reading must NOT be overwritten with "0"
        $this->assertEquals('450', (string) $bill->working_reading);

        $consumer->refresh();
        $this->assertEquals('450', (string) $consumer->last_working_reading);
    }

    public function test_download_mru_payload_returns_effective_review_status_and_contact_info(): void
    {
        $ca = '101010999';
        ConsumerAccount::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'consumer_name' => 'Amit Sharma',
            'mobile' => '9876543210',
            'latitude' => 25.5941,
            'longitude' => 85.1376,
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'billing_month' => 8,
            'billing_year' => 2026,
            'consumer_name' => 'Amit Sharma',
            'review_status' => 'Critical',
            'tag' => 'defective',
            'remark' => 'Meter glass broken',
        ]);

        $response = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->getJson("/api/v1/sync/mrus/{$this->mru->id}/download");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('consumers.0.mobile', '9876543210')
            ->assertJsonPath('consumers.0.current_bill.review_status', 'Critical')
            ->assertJsonPath('consumers.0.current_bill.tag', 'defective')
            ->assertJsonPath('consumers.0.current_bill.remark', 'Meter glass broken');
    }

    public function test_apk_download_route_streams_package_file(): void
    {
        $apkPath = public_path('downloads/nbpdcl-field-reader.apk');
        $dir = dirname($apkPath);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $existed = File::exists($apkPath);
        if (! $existed) {
            File::put($apkPath, 'PK dummy apk content');
        }

        try {
            $response = $this->get('/download/app/apk');
            $response->assertStatus(200);
            $response->assertHeader('content-type', 'application/vnd.android.package-archive');
        } finally {
            if (! $existed) {
                @unlink($apkPath);
            }
        }
    }

    public function test_download_field_desk_agenda_returns_mru_and_global_actions(): void
    {
        $category = FieldDeskCategory::first();

        // 1. MRU-linked Field Desk Action
        FieldDeskAction::create([
            'user_id' => $this->user->id,
            'ca_number' => '11112222',
            'mru_id' => $this->mru->id,
            'category_id' => $category->id,
            'target_date' => now()->addDays(2)->toDateString(),
            'target_amount' => 1200.00,
            'status' => 'open',
            'private_note' => 'MRU specific follow-up',
        ]);

        // 2. Global / Unassigned Field Desk Action (mru_id = null)
        FieldDeskAction::create([
            'user_id' => $this->user->id,
            'ca_number' => '99990000',
            'mru_id' => null,
            'category_id' => $category->id,
            'target_date' => now()->toDateString(),
            'target_amount' => 500.00,
            'status' => 'open',
            'private_note' => 'Global walk-in consumer note',
        ]);

        $response = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->getJson('/api/v1/sync/field-desk');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'actions');

        $actions = collect($response->json('actions'));
        $globalAction = $actions->firstWhere('ca_number', '99990000');
        $mruAction = $actions->firstWhere('ca_number', '11112222');

        $this->assertNotNull($globalAction);
        $this->assertNull($globalAction['mru_id']);
        $this->assertEquals('Global', $globalAction['mru_code']);

        $this->assertNotNull($mruAction);
        $this->assertEquals($this->mru->id, $mruAction['mru_id']);
    }
}
