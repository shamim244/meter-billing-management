<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use App\Services\BillDownloadService;
use App\Services\BillParseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProcessingCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    public function test_processing_hub_index_renders_successfully(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0477', 'name' => 'Gerua', 'status' => 'active']);

        $response = $this->actingAs($user)->get(route('processing.index'));

        $response->assertStatus(200);
        $response->assertSee('Data Processing Center');
        $response->assertSee('Bill Downloader');
        $response->assertSee('Bill Parser');
        $response->assertSee('process.log');
    }

    public function test_processing_status_returns_live_metrics(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0477', 'name' => 'Gerua', 'status' => 'active']);

        // 2 consumers, 1 downloaded & parsed, 1 missing
        ConsumerAccount::create(['user_id' => $user->id, 'mru_id' => $mru->id, 'ca_number' => '1001', 'status' => 'active']);
        ConsumerAccount::create(['user_id' => $user->id, 'mru_id' => $mru->id, 'ca_number' => '1002', 'status' => 'active']);

        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1001',
            'billing_month' => 8,
            'billing_year' => 2026,
            'download_status' => 'downloaded',
            'parse_status' => 'parsed',
            'pdf_path' => 'users/1/pdfs/2026/8/0477/1001.pdf',
        ]);

        $response = $this->actingAs($user)->getJson(route('processing.status', [
            'mru_id' => $mru->id,
            'month' => 8,
            'year' => 2026,
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'stats' => [
                'total_cas' => 2,
                'downloaded_count' => 1,
                'missing_downloads' => 1,
                'pdf_bills_count' => 1,
                'parsed_count' => 1,
                'pending_parse' => 0,
            ]
        ]);
    }

    public function test_downloader_and_parser_execution_and_logs(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0477', 'name' => 'Gerua', 'status' => 'active']);

        ConsumerAccount::create(['user_id' => $user->id, 'mru_id' => $mru->id, 'ca_number' => '1001', 'status' => 'active']);

        // Mock Download Service
        $mockDownload = $this->createMock(BillDownloadService::class);
        $mockDownload->expects($this->once())
            ->method('download')
            ->willReturn([
                'total' => 1,
                'success' => 1,
                'failed' => 0,
                'details' => [],
            ]);
        $this->app->instance(BillDownloadService::class, $mockDownload);

        // Run Downloader endpoint
        $dlResponse = $this->actingAs($user)->postJson(route('processing.downloader'), [
            'mru_id' => $mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
            'mode' => 'all',
        ]);
        $dlResponse->assertStatus(200);
        $dlResponse->assertJsonPath('success', true);

        // Mock Parse Service
        $mockParse = $this->createMock(BillParseService::class);
        $mockParse->expects($this->once())
            ->method('parse')
            ->willReturn([
                'total' => 1,
                'success' => 1,
                'failed' => 0,
                'details' => [],
            ]);
        $this->app->instance(BillParseService::class, $mockParse);

        // Run Parser endpoint
        $parseResponse = $this->actingAs($user)->postJson(route('processing.parser'), [
            'mru_id' => $mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
            'mode' => 'pending_only',
        ]);
        $parseResponse->assertStatus(200);
        $parseResponse->assertJsonPath('success', true);

        // Test clear logs endpoint
        $clearResponse = $this->actingAs($user)->postJson(route('processing.logs.clear'));
        $clearResponse->assertStatus(200);
        $clearResponse->assertJsonPath('success', true);
    }
}
