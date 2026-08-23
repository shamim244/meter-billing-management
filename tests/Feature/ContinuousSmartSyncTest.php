<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use App\Services\BillParseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContinuousSmartSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    public function test_continuous_smart_sync_auto_registers_and_smart_updates(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0477', 'name' => 'Gerua', 'status' => 'active']);

        // 1. Initial State: Consumer 1001 not in Master table yet
        $this->assertDatabaseMissing('consumer_accounts', [
            'user_id' => $user->id,
            'ca_number' => '1001',
        ]);

        // Create a bill record for month 7 (July)
        $fakePdfPath = "users/{$user->id}/pdfs/2026/7/0477/1001.pdf";
        Storage::disk('local')->put($fakePdfPath, '%PDF-1.4 Fake PDF Content');

        $billJuly = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1001',
            'billing_month' => 7,
            'billing_year' => 2026,
            'download_status' => 'downloaded',
            'pdf_path' => $fakePdfPath,
        ]);

        // Mock extractFromPdf to return initial extracted identity
        $mockParser = $this->getMockBuilder(BillParseService::class)
            ->onlyMethods(['extractFromPdf'])
            ->getMock();

        $mockParser->expects($this->exactly(3))
            ->method('extractFromPdf')
            ->willReturnOnConsecutiveCalls(
                // Call 1: July Cycle -> Discovers new consumer
                [
                    'consumer_name' => 'RAMESH KUMAR',
                    'meter_no' => 'MTR-1001-OLD',
                    'bill_month' => 'JUL, 2026',
                    'bill_date' => '2026-07-15',
                    'due_date' => '2026-08-05',
                    'current_reading' => 100,
                    'previous_reading' => 50,
                    'units_consumed' => 50,
                    'total_amount' => 600.0,
                    'mru' => '0477',
                ],
                // Call 2: August Cycle -> Smart meter replaced (meter_no changed to SMART-999)
                [
                    'consumer_name' => 'RAMESH KUMAR',
                    'meter_no' => 'SMART-999',
                    'bill_month' => 'AUG, 2026',
                    'bill_date' => '2026-08-15',
                    'due_date' => '2026-09-05',
                    'current_reading' => 180,
                    'previous_reading' => 100,
                    'units_consumed' => 80,
                    'total_amount' => 950.0,
                    'mru' => '0477',
                ],
                // Call 3: September Cycle -> Name extraction was partial/empty (Non-destructive check)
                [
                    'consumer_name' => null,
                    'meter_no' => 'SMART-999',
                    'bill_month' => 'SEP, 2026',
                    'bill_date' => '2026-09-15',
                    'due_date' => '2026-10-05',
                    'current_reading' => 250,
                    'previous_reading' => 180,
                    'units_consumed' => 70,
                    'total_amount' => 820.0,
                    'mru' => '0477',
                ]
            );

        // STEP 1: Parse July Cycle -> Should Auto-Register into Master List
        $res1 = $mockParser->parse($user->id, 7, 2026, $mru->id);
        $this->assertEquals(1, $res1['success']);

        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $user->id,
            'ca_number' => '1001',
            'consumer_name' => 'RAMESH KUMAR',
            'meter_no' => 'MTR-1001-OLD',
        ]);

        // STEP 2: Parse August Cycle (Smart Meter replacement) -> Should Update Master Meter No
        $fakePdfPathAug = "users/{$user->id}/pdfs/2026/8/0477/1001.pdf";
        Storage::disk('local')->put($fakePdfPathAug, '%PDF-1.4 Fake PDF Content');

        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1001',
            'billing_month' => 8,
            'billing_year' => 2026,
            'download_status' => 'downloaded',
            'pdf_path' => $fakePdfPathAug,
        ]);

        $res2 = $mockParser->parse($user->id, 8, 2026, $mru->id);
        $this->assertEquals(1, $res2['success']);

        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $user->id,
            'ca_number' => '1001',
            'consumer_name' => 'RAMESH KUMAR',
            'meter_no' => 'SMART-999', // Updated to smart meter!
        ]);

        // STEP 3: Parse September Cycle with empty name -> Should PRESERVE master name (Non-destructive)
        $fakePdfPathSep = "users/{$user->id}/pdfs/2026/9/0477/1001.pdf";
        Storage::disk('local')->put($fakePdfPathSep, '%PDF-1.4 Fake PDF Content');

        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '1001',
            'billing_month' => 9,
            'billing_year' => 2026,
            'download_status' => 'downloaded',
            'pdf_path' => $fakePdfPathSep,
        ]);

        $res3 = $mockParser->parse($user->id, 9, 2026, $mru->id);
        $this->assertEquals(1, $res3['success']);

        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $user->id,
            'ca_number' => '1001',
            'consumer_name' => 'RAMESH KUMAR', // Still intact!
            'meter_no' => 'SMART-999',
        ]);
    }
}
