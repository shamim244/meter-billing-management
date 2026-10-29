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
}
