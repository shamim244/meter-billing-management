<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\MeterReadingHistory;
use App\Models\Mru;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\MeterReadingHistoryService;
use App\Services\SmartAverageCalculationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoricalSpikeFiltrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        SystemSetting::clearRuntimeCache();
    }

    protected function tearDown(): void
    {
        SystemSetting::clearRuntimeCache();
        parent::tearDown();
    }

    /**
     * Test MD and LK bases are excluded from the smart average calculation.
     */
    public function test_md_and_lk_months_excluded_from_smart_average(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '9991', 'name' => 'FILTER_TEST_MRU', 'status' => 'active']);
        $caNumber = '999900001001';

        $calcService = app(SmartAverageCalculationService::class);
        $historyService = app(MeterReadingHistoryService::class);

        // Create consumer account
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'consumer_name' => 'TEST CONSUMER FILTER',
            'tariff_category' => 'DS1D',
            'billing_basis' => 'OK',
            'status' => 'active',
        ]);

        // Historical months: OK months (50, 48, 52 kWh) and non-OK months: MD (250 kWh), LK (180 kWh)
        MeterReadingHistory::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 4,
            'billing_year' => 2026,
            'reading_source' => 'pdf',
            'current_reading' => '1050',
            'previous_reading' => '1000',
            'units_consumed' => 50,
            'billing_basis' => 'OK',
            'is_closed' => true,
        ]);

        MeterReadingHistory::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 5,
            'billing_year' => 2026,
            'reading_source' => 'pdf',
            'current_reading' => '1098',
            'previous_reading' => '1050',
            'units_consumed' => 48,
            'billing_basis' => 'OK',
            'is_closed' => true,
        ]);

        MeterReadingHistory::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 6,
            'billing_year' => 2026,
            'reading_source' => 'pdf',
            'current_reading' => '1150',
            'previous_reading' => '1098',
            'units_consumed' => 52,
            'billing_basis' => 'OK',
            'is_closed' => true,
        ]);

        // Non-OK months
        MeterReadingHistory::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 2,
            'billing_year' => 2026,
            'reading_source' => 'pdf',
            'current_reading' => '850',
            'previous_reading' => '600',
            'units_consumed' => 250,
            'billing_basis' => 'MD',
            'is_closed' => true,
        ]);

        MeterReadingHistory::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 3,
            'billing_year' => 2026,
            'reading_source' => 'pdf',
            'current_reading' => '1000',
            'previous_reading' => '820',
            'units_consumed' => 180,
            'billing_basis' => 'LK',
            'is_closed' => true,
        ]);

        // Calculate smart average for July (month 7, 2026)
        $result = $calcService->calculateSmartAverage(
            $caNumber,
            7,
            2026,
            'OK',
            null,
            $consumer
        );

        // Average should be based strictly on median of OK months [48, 50, 52] = 50 kWh
        $this->assertEquals(50, $result['avg_units']);
        $this->assertEquals(2, $result['filtered_bases_count']);
        $this->assertEquals(0, $result['spikes_filtered_count']);
        $this->assertEquals(3, $result['clean_units_count']);

        // Ingestion-time check: record from PDF with MD and LK in extracted history
        $sampleBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '999900001002',
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '2000',
            'current_reading' => '2050',
            'units_consumed' => 50,
            'billing_basis' => 'OK',
        ]);

        $extractedHistory = [
            ['month' => 7, 'year' => 2026, 'units' => 50, 'basis' => 'OK'],
            ['month' => 6, 'year' => 2026, 'units' => 200, 'basis' => 'MD'],
            ['month' => 5, 'year' => 2026, 'units' => 170, 'basis' => 'LK'],
        ];

        $historyService->recordFromPdf($sampleBill, $extractedHistory);

        $mdRecord = MeterReadingHistory::where('user_id', $user->id)
            ->where('ca_number', '999900001002')
            ->where('billing_month', 6)
            ->firstOrFail();

        $this->assertFalse($mdRecord->isActiveForAverage());
        $this->assertEquals('MD', $mdRecord->billing_basis);

        $lkRecord = MeterReadingHistory::where('user_id', $user->id)
            ->where('ca_number', '999900001002')
            ->where('billing_month', 5)
            ->firstOrFail();

        $this->assertFalse($lkRecord->isActiveForAverage());
        $this->assertEquals('LK', $lkRecord->billing_basis);
    }

    /**
     * Test spike trimming (e.g., 380 kWh spike removed from 45 kWh median).
     */
    public function test_spike_trimming_removes_380_kwh_spike_from_45_kwh_median(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '9992', 'name' => 'SPIKE_TEST_MRU', 'status' => 'active']);
        $caNumber = '999900001003';

        $calcService = app(SmartAverageCalculationService::class);

        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'consumer_name' => 'TEST CONSUMER SPIKE',
            'tariff_category' => 'DS1D',
            'billing_basis' => 'OK',
            'status' => 'active',
        ]);

        // Historical months: 45, 44, 46, 45 kWh, and an extreme 380 kWh spike
        $historicalReadings = [
            ['month' => 1, 'year' => 2026, 'units' => 45],
            ['month' => 2, 'year' => 2026, 'units' => 44],
            ['month' => 3, 'year' => 2026, 'units' => 46],
            ['month' => 4, 'year' => 2026, 'units' => 45],
            ['month' => 5, 'year' => 2026, 'units' => 380], // Extreme spike!
        ];

        foreach ($historicalReadings as $h) {
            MeterReadingHistory::create([
                'user_id' => $user->id,
                'mru_id' => $mru->id,
                'ca_number' => $caNumber,
                'billing_month' => $h['month'],
                'billing_year' => $h['year'],
                'reading_source' => 'pdf',
                'units_consumed' => $h['units'],
                'billing_basis' => 'OK',
                'is_closed' => true,
            ]);
        }

        // Target calculation month: June 2026
        $result = $calcService->calculateSmartAverage(
            $caNumber,
            6,
            2026,
            'OK',
            null,
            $consumer
        );

        // 380 kWh > (2.0 * 45 = 90) AND (380 - 45 = 335 >= 30) -> Filtered out!
        $this->assertEquals(1, $result['spikes_filtered_count']);
        $this->assertEquals(45, $result['avg_units']);
        $this->assertEquals(4, $result['clean_units_count']);
    }

    /**
     * Test minimum unit buffer protection (5 kWh to 12 kWh not flagged as spike).
     */
    public function test_minimum_unit_buffer_protection_avoids_small_number_false_positives(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '9993', 'name' => 'BUFFER_TEST_MRU', 'status' => 'active']);
        $caNumber = '999900001004';

        $calcService = app(SmartAverageCalculationService::class);

        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'consumer_name' => 'TEST CONSUMER BUFFER',
            'tariff_category' => 'DS1D',
            'billing_basis' => 'OK',
            'status' => 'active',
        ]);

        // Historical months: 5, 5, 5, 5, and 12 kWh
        // Even though 12 > (2.0 * 5 = 10), the delta is 12 - 5 = 7 kWh, which is BELOW the 30 kWh buffer!
        $historicalReadings = [
            ['month' => 1, 'year' => 2026, 'units' => 5],
            ['month' => 2, 'year' => 2026, 'units' => 5],
            ['month' => 3, 'year' => 2026, 'units' => 5],
            ['month' => 4, 'year' => 2026, 'units' => 5],
            ['month' => 5, 'year' => 2026, 'units' => 12],
        ];

        foreach ($historicalReadings as $h) {
            MeterReadingHistory::create([
                'user_id' => $user->id,
                'mru_id' => $mru->id,
                'ca_number' => $caNumber,
                'billing_month' => $h['month'],
                'billing_year' => $h['year'],
                'reading_source' => 'pdf',
                'units_consumed' => $h['units'],
                'billing_basis' => 'OK',
                'is_closed' => true,
            ]);
        }

        $result = $calcService->calculateSmartAverage(
            $caNumber,
            6,
            2026,
            'OK',
            null,
            $consumer
        );

        // 12 kWh must NOT be trimmed as a spike due to the 30 kWh floor buffer
        $this->assertEquals(0, $result['spikes_filtered_count']);
        $this->assertEquals(5, $result['clean_units_count']);
        $this->assertEquals(5, $result['avg_units']);
    }

    /**
     * Test Agriculture (IAS1) bypass behavior and fallback multiplier.
     */
    public function test_agriculture_ias1_bypass_behavior_and_fallback_multiplier(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '9994', 'name' => 'AGRI_TEST_MRU', 'status' => 'active']);
        $caNumber = '999900001005';

        $calcService = app(SmartAverageCalculationService::class);

        // Consumer with Agriculture (IAS1) tariff
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'consumer_name' => 'KISHAN AGRI PUMP',
            'tariff_category' => 'IAS1',
            'billing_basis' => 'OK',
            'status' => 'active',
        ]);

        // Historical months: normal off-season 50, 50, 50 kWh and an intense irrigation surge of 350 kWh
        $historicalReadings = [
            ['month' => 1, 'year' => 2026, 'units' => 50],
            ['month' => 2, 'year' => 2026, 'units' => 50],
            ['month' => 3, 'year' => 2026, 'units' => 50],
            ['month' => 4, 'year' => 2026, 'units' => 350], // Irrigation pump surge
        ];

        foreach ($historicalReadings as $h) {
            MeterReadingHistory::create([
                'user_id' => $user->id,
                'mru_id' => $mru->id,
                'ca_number' => $caNumber,
                'billing_month' => $h['month'],
                'billing_year' => $h['year'],
                'reading_source' => 'pdf',
                'units_consumed' => $h['units'],
                'billing_basis' => 'OK',
                'is_closed' => true,
            ]);
        }

        // 1. With agriculture_spike_bypass ON (Default): Irrigation surge is bypassed / not trimmed
        SystemSetting::set('agriculture_spike_bypass', true);
        $resultWithBypass = $calcService->calculateSmartAverage(
            $caNumber,
            5,
            2026,
            'OK',
            null,
            $consumer
        );

        $this->assertTrue($resultWithBypass['is_agriculture_bypass']);
        $this->assertEquals(0, $resultWithBypass['spikes_filtered_count']);
        $this->assertEquals(4, $resultWithBypass['clean_units_count']);

        // 2. With agriculture_spike_bypass OFF: Fallback multiplier (4.0x) is applied
        SystemSetting::set('agriculture_spike_bypass', false);
        SystemSetting::set('agriculture_spike_multiplier', 4.0);

        // 350 > (4.0 * 50 = 200) AND (350 - 50 = 300 >= 30) -> Now trimmed!
        $resultWithoutBypass = $calcService->calculateSmartAverage(
            $caNumber,
            5,
            2026,
            'OK',
            null,
            $consumer
        );

        $this->assertFalse($resultWithoutBypass['is_agriculture_bypass']);
        $this->assertEquals(1, $resultWithoutBypass['spikes_filtered_count']);
        $this->assertEquals(3, $resultWithoutBypass['clean_units_count']);
        $this->assertEquals(50, $resultWithoutBypass['avg_units']);
    }

    /**
     * Test Admin settings update and toggle switching.
     */
    public function test_admin_settings_update_and_toggle_switching(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $payload = [
            'download_driver' => 'auto',
            'extraction_engine' => 'auto',
            'wss_url' => 'https://wss.nbpdcl.co.in/test',
            'aes_key' => 'test-key-2026',
            'legacy_url' => 'https://api.bsphcl.co.in/test',
            'timeout' => 45,
            'concurrency' => 10,
            'has_spike_settings' => '1',
            'extraction_filter_enabled' => '0', // Toggle OFF
            'calculation_filter_enabled' => '1',
            'filter_non_ok_bases' => '1',
            'filter_zero_unit_months' => '0', // Toggle OFF
            'global_spike_multiplier' => '2.5',
            'min_spike_unit_buffer' => '50',
            'agriculture_spike_bypass' => '0', // Toggle OFF
            'agriculture_spike_multiplier' => '3.5',
            'commercial_spike_multiplier' => '3.0',
            'domestic_spike_multiplier' => '1.8',
        ];

        $response = $this->actingAs($admin)->post(route('admin.bills.engine-settings.update'), $payload);
        $response->assertRedirect(route('admin.bills.engine-settings'));
        $response->assertSessionHas('status');

        // Verify SystemSetting values were properly stored
        $this->assertFalse((bool) SystemSetting::get('extraction_filter_enabled'));
        $this->assertTrue((bool) SystemSetting::get('calculation_filter_enabled'));
        $this->assertTrue((bool) SystemSetting::get('filter_non_ok_bases'));
        $this->assertFalse((bool) SystemSetting::get('filter_zero_unit_months'));
        $this->assertEquals(2.5, (float) SystemSetting::get('global_spike_multiplier'));
        $this->assertEquals(50, (int) SystemSetting::get('min_spike_unit_buffer'));
        $this->assertFalse((bool) SystemSetting::get('agriculture_spike_bypass'));
        $this->assertEquals(3.5, (float) SystemSetting::get('agriculture_spike_multiplier'));
        $this->assertEquals(3.0, (float) SystemSetting::get('commercial_spike_multiplier'));
        $this->assertEquals(1.8, (float) SystemSetting::get('domestic_spike_multiplier'));

        // Test Reset to defaults
        $resetResponse = $this->actingAs($admin)->post(route('admin.bills.engine-settings.reset'));
        $resetResponse->assertRedirect(route('admin.bills.engine-settings'));

        $this->assertTrue((bool) SystemSetting::get('extraction_filter_enabled'));
        $this->assertTrue((bool) SystemSetting::get('calculation_filter_enabled'));
        $this->assertTrue((bool) SystemSetting::get('filter_non_ok_bases'));
        $this->assertTrue((bool) SystemSetting::get('filter_zero_unit_months'));
        $this->assertEquals(2.0, (float) SystemSetting::get('global_spike_multiplier'));
        $this->assertEquals(30, (int) SystemSetting::get('min_spike_unit_buffer'));
        $this->assertTrue((bool) SystemSetting::get('agriculture_spike_bypass'));
        $this->assertEquals(4.0, (float) SystemSetting::get('agriculture_spike_multiplier'));
        $this->assertEquals(2.5, (float) SystemSetting::get('commercial_spike_multiplier'));
        $this->assertEquals(2.0, (float) SystemSetting::get('domestic_spike_multiplier'));
    }

    /**
     * Test empty ghost rows (0 units, null readings) are eliminated from entering meter_reading_histories.
     */
    public function test_empty_ghost_rows_eliminated_from_meter_reading_histories(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '9995', 'name' => 'GHOST_TEST_MRU', 'status' => 'active']);
        $caNumber = '999900001006';

        $historyService = app(MeterReadingHistoryService::class);

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '500',
            'current_reading' => '550',
            'units_consumed' => 50,
            'billing_basis' => 'OK',
        ]);

        $extractedHistory = [
            ['month' => 7, 'year' => 2026, 'units' => 45, 'basis' => 'OK'],
            ['month' => 6, 'year' => 2026, 'units' => 0, 'basis' => 'OK'],  // Ghost row: 0 units
            ['month' => 5, 'year' => 2026, 'units' => 48, 'basis' => 'OK'],
            ['month' => 4, 'year' => 2026, 'units' => 0, 'basis' => 'OK'],  // Ghost row: 0 units
        ];

        $historyService->recordFromPdf($bill, $extractedHistory);

        // Verify valid months exist
        $this->assertDatabaseHas('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 7,
            'units_consumed' => 45,
        ]);

        $this->assertDatabaseHas('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 5,
            'units_consumed' => 48,
        ]);

        // Verify ghost rows (0 units) were eliminated
        $this->assertDatabaseMissing('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 6,
        ]);

        $this->assertDatabaseMissing('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 4,
        ]);
    }

    /**
     * Test category-specific multipliers for Commercial (NDS1D) and Domestic (DS1D).
     */
    public function test_commercial_and_domestic_category_multipliers(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '9996', 'name' => 'CATEGORY_TEST_MRU', 'status' => 'active']);

        $calcService = app(SmartAverageCalculationService::class);

        // Case A: Commercial (NDS1D) with multiplier 2.5x
        $commercialCa = '999900001007';
        $commercialConsumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $commercialCa,
            'consumer_name' => 'SHARMA SWEETS',
            'tariff_category' => 'NDS1D',
            'billing_basis' => 'OK',
            'status' => 'active',
        ]);

        // Median = 100 kWh. Multiplier for commercial = 2.5x -> Threshold = 250 kWh
        // Month with 230 kWh is below 250 kWh -> NOT a spike!
        // Month with 280 kWh is above 250 kWh and delta 180 >= 30 -> IS a spike!
        $commMonths = [
            ['month' => 1, 'year' => 2026, 'units' => 100],
            ['month' => 2, 'year' => 2026, 'units' => 100],
            ['month' => 3, 'year' => 2026, 'units' => 100],
            ['month' => 4, 'year' => 2026, 'units' => 230], // 230 <= 250 -> Retained
            ['month' => 5, 'year' => 2026, 'units' => 280], // 280 > 250 -> Trimmed
        ];

        foreach ($commMonths as $h) {
            MeterReadingHistory::create([
                'user_id' => $user->id,
                'mru_id' => $mru->id,
                'ca_number' => $commercialCa,
                'billing_month' => $h['month'],
                'billing_year' => $h['year'],
                'reading_source' => 'pdf',
                'units_consumed' => $h['units'],
                'billing_basis' => 'OK',
                'is_closed' => true,
            ]);
        }

        $commResult = $calcService->calculateSmartAverage(
            $commercialCa,
            6,
            2026,
            'OK',
            null,
            $commercialConsumer
        );

        $this->assertEquals(2.5, $commResult['multiplier_used']);
        $this->assertEquals(1, $commResult['spikes_filtered_count']);
        $this->assertEquals(4, $commResult['clean_units_count']);
    }

    /**
     * Test filter_zero_unit_months toggle behavior (when OFF, zero units are included).
     */
    public function test_filter_zero_unit_months_toggle_behavior(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '9997', 'name' => 'ZERO_TEST_MRU', 'status' => 'active']);
        $caNumber = '999900001008';

        $calcService = app(SmartAverageCalculationService::class);

        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'consumer_name' => 'TEST ZERO UNIT TOGGLE',
            'tariff_category' => 'DS1D',
            'billing_basis' => 'OK',
            'status' => 'active',
        ]);

        // Historical months: 100, 60, and a 0 kWh frozen month
        $months = [
            ['month' => 1, 'year' => 2026, 'units' => 100],
            ['month' => 2, 'year' => 2026, 'units' => 60],
            ['month' => 3, 'year' => 2026, 'units' => 0],
        ];

        foreach ($months as $h) {
            MeterReadingHistory::create([
                'user_id' => $user->id,
                'mru_id' => $mru->id,
                'ca_number' => $caNumber,
                'billing_month' => $h['month'],
                'billing_year' => $h['year'],
                'reading_source' => 'pdf',
                'units_consumed' => $h['units'],
                'billing_basis' => 'OK',
                'is_closed' => true,
            ]);
        }

        // Case 1: filter_zero_unit_months is ON (Default) -> 0 kWh month is excluded!
        SystemSetting::set('filter_zero_unit_months', true);
        $resultOn = $calcService->calculateSmartAverage($caNumber, 4, 2026, 'OK', null, $consumer);

        $this->assertEquals(2, $resultOn['clean_units_count']);
        // Median of [60, 100] = 80
        $this->assertEquals(80, $resultOn['avg_units']);

        // Case 2: filter_zero_unit_months is OFF -> 0 kWh month IS included!
        SystemSetting::set('filter_zero_unit_months', false);
        $resultOff = $calcService->calculateSmartAverage($caNumber, 4, 2026, 'OK', null, $consumer);

        $this->assertEquals(3, $resultOff['clean_units_count']);
        // Median of [0, 60, 100] = 60
        $this->assertEquals(60, $resultOff['avg_units']);
    }

    /**
     * Test exclusion of PL, DL, and EST non-OK bases.
     */
    public function test_pl_dl_and_est_non_ok_bases_exclusion(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '9998', 'name' => 'NON_OK_BASES_MRU', 'status' => 'active']);
        $caNumber = '999900001009';

        $calcService = app(SmartAverageCalculationService::class);

        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'consumer_name' => 'TEST NON OK BASES',
            'tariff_category' => 'DS1D',
            'billing_basis' => 'OK',
            'status' => 'active',
        ]);

        // OK months: 50, 50, 50
        MeterReadingHistory::create([
            'user_id' => $user->id, 'mru_id' => $mru->id, 'ca_number' => $caNumber,
            'billing_month' => 1, 'billing_year' => 2026, 'reading_source' => 'pdf',
            'units_consumed' => 50, 'billing_basis' => 'OK', 'is_closed' => true,
        ]);
        MeterReadingHistory::create([
            'user_id' => $user->id, 'mru_id' => $mru->id, 'ca_number' => $caNumber,
            'billing_month' => 2, 'billing_year' => 2026, 'reading_source' => 'pdf',
            'units_consumed' => 50, 'billing_basis' => 'OK', 'is_closed' => true,
        ]);
        MeterReadingHistory::create([
            'user_id' => $user->id, 'mru_id' => $mru->id, 'ca_number' => $caNumber,
            'billing_month' => 3, 'billing_year' => 2026, 'reading_source' => 'pdf',
            'units_consumed' => 50, 'billing_basis' => 'OK', 'is_closed' => true,
        ]);

        // Non-OK bases: PL (Premises Locked), DL (Defective), EST (Estimated)
        MeterReadingHistory::create([
            'user_id' => $user->id, 'mru_id' => $mru->id, 'ca_number' => $caNumber,
            'billing_month' => 4, 'billing_year' => 2026, 'reading_source' => 'pdf',
            'units_consumed' => 150, 'billing_basis' => 'PL', 'is_closed' => true,
        ]);
        MeterReadingHistory::create([
            'user_id' => $user->id, 'mru_id' => $mru->id, 'ca_number' => $caNumber,
            'billing_month' => 5, 'billing_year' => 2026, 'reading_source' => 'pdf',
            'units_consumed' => 200, 'billing_basis' => 'DL', 'is_closed' => true,
        ]);
        MeterReadingHistory::create([
            'user_id' => $user->id, 'mru_id' => $mru->id, 'ca_number' => $caNumber,
            'billing_month' => 6, 'billing_year' => 2026, 'reading_source' => 'pdf',
            'units_consumed' => 300, 'billing_basis' => 'EST', 'is_closed' => true,
        ]);

        // With filter_non_ok_bases ON: PL, DL, EST are excluded
        SystemSetting::set('filter_non_ok_bases', true);
        $result = $calcService->calculateSmartAverage($caNumber, 7, 2026, 'OK', null, $consumer);

        $this->assertEquals(3, $result['filtered_bases_count']);
        $this->assertEquals(3, $result['clean_units_count']);
        $this->assertEquals(50, $result['avg_units']);
    }

    /**
     * Test Kutir Jyoti Rural tariff resolves to domestic multiplier.
     */
    public function test_kutir_jyoti_rural_tariff_resolves_domestic_multiplier(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '9999', 'name' => 'KJ_TEST_MRU', 'status' => 'active']);
        $caNumber = '999900001010';

        $calcService = app(SmartAverageCalculationService::class);
        $historyService = app(MeterReadingHistoryService::class);

        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'consumer_name' => 'RAMESH MANJHI',
            'tariff_category' => 'Kutir Jyoti Rural',
            'billing_basis' => 'OK',
            'status' => 'active',
        ]);

        $params = $historyService->resolveSpikeParameters('Kutir Jyoti Rural');
        $this->assertEquals('domestic', $params['category']);
        $this->assertEquals(2.0, $params['multiplier']);

        // Historical readings: 40, 40, 40, and 100 kWh spike
        $months = [
            ['month' => 1, 'year' => 2026, 'units' => 40],
            ['month' => 2, 'year' => 2026, 'units' => 40],
            ['month' => 3, 'year' => 2026, 'units' => 40],
            ['month' => 4, 'year' => 2026, 'units' => 100], // > 2.0 * 40 = 80 AND delta 60 >= 30
        ];

        foreach ($months as $h) {
            MeterReadingHistory::create([
                'user_id' => $user->id,
                'mru_id' => $mru->id,
                'ca_number' => $caNumber,
                'billing_month' => $h['month'],
                'billing_year' => $h['year'],
                'reading_source' => 'pdf',
                'units_consumed' => $h['units'],
                'billing_basis' => 'OK',
                'is_closed' => true,
            ]);
        }

        $result = $calcService->calculateSmartAverage($caNumber, 5, 2026, 'OK', null, $consumer);

        $this->assertEquals(2.0, $result['multiplier_used']);
        $this->assertEquals(1, $result['spikes_filtered_count']);
        $this->assertEquals(3, $result['clean_units_count']);
        $this->assertEquals(40, $result['avg_units']);
    }

    /**
     * Test calculation_filter_enabled toggle completely disables Layer 2 filtering.
     */
    public function test_calculation_filter_enabled_toggle_disables_layer_two_filtering(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '9990', 'name' => 'BYPASS_MRU', 'status' => 'active']);
        $caNumber = '999900001011';

        $calcService = app(SmartAverageCalculationService::class);

        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'consumer_name' => 'FILTER OFF TEST',
            'tariff_category' => 'DS1D',
            'billing_basis' => 'OK',
            'status' => 'active',
        ]);

        // Historical months: 50, 50, non-OK (MD 250), and extreme spike (350)
        MeterReadingHistory::create([
            'user_id' => $user->id, 'mru_id' => $mru->id, 'ca_number' => $caNumber,
            'billing_month' => 1, 'billing_year' => 2026, 'reading_source' => 'pdf',
            'units_consumed' => 50, 'billing_basis' => 'OK', 'is_closed' => true,
        ]);
        MeterReadingHistory::create([
            'user_id' => $user->id, 'mru_id' => $mru->id, 'ca_number' => $caNumber,
            'billing_month' => 2, 'billing_year' => 2026, 'reading_source' => 'pdf',
            'units_consumed' => 50, 'billing_basis' => 'OK', 'is_closed' => true,
        ]);
        MeterReadingHistory::create([
            'user_id' => $user->id, 'mru_id' => $mru->id, 'ca_number' => $caNumber,
            'billing_month' => 3, 'billing_year' => 2026, 'reading_source' => 'pdf',
            'units_consumed' => 250, 'billing_basis' => 'MD', 'is_closed' => true,
        ]);
        MeterReadingHistory::create([
            'user_id' => $user->id, 'mru_id' => $mru->id, 'ca_number' => $caNumber,
            'billing_month' => 4, 'billing_year' => 2026, 'reading_source' => 'pdf',
            'units_consumed' => 350, 'billing_basis' => 'OK', 'is_closed' => true,
        ]);

        // Turn OFF calculation_filter_enabled
        SystemSetting::set('calculation_filter_enabled', false);

        $result = $calcService->calculateSmartAverage($caNumber, 5, 2026, 'OK', null, $consumer);

        $this->assertEquals(0, $result['spikes_filtered_count']);
        $this->assertEquals(0, $result['filtered_bases_count']);
        $this->assertEquals(4, $result['clean_units_count']);
    }

    /**
     * Test partial API update does not reset unmentioned toggles to false.
     */
    public function test_partial_api_update_preserves_unmentioned_toggles(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        SystemSetting::set('extraction_filter_enabled', true);
        SystemSetting::set('calculation_filter_enabled', true);
        SystemSetting::set('filter_non_ok_bases', true);

        // Send partial payload without has_spike_settings
        $payload = [
            'download_driver' => 'auto',
            'extraction_engine' => 'auto',
            'wss_url' => 'https://wss.nbpdcl.co.in/test',
            'aes_key' => 'test-key-2026',
            'legacy_url' => 'https://api.bsphcl.co.in/test',
            'timeout' => 45,
            'concurrency' => 10,
            'commercial_spike_multiplier' => '3.2',
        ];

        $response = $this->actingAs($admin)->post(route('admin.bills.engine-settings.update'), $payload);
        $response->assertRedirect(route('admin.bills.engine-settings'));

        // Verify commercial multiplier was updated
        $this->assertEquals(3.2, (float) SystemSetting::get('commercial_spike_multiplier'));

        // Verify unmentioned toggles were NOT accidentally wiped out
        $this->assertTrue((bool) SystemSetting::get('extraction_filter_enabled'));
        $this->assertTrue((bool) SystemSetting::get('calculation_filter_enabled'));
        $this->assertTrue((bool) SystemSetting::get('filter_non_ok_bases'));
    }
}
