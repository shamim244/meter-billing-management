<?php

namespace Tests\Feature;

use App\Models\AgentSubscription;
use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\MeterReadingHistory;
use App\Models\Mru;
use App\Models\Plan;
use App\Models\User;
use App\Services\BillParseService;
use App\Services\MeterReadingHistoryService;
use App\Services\SmartAverageCalculationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeterReadingHistoryAndTuningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_sequential_compounding_math_50_minus_20_plus_10_equals_44(): void
    {
        $service = app(SmartAverageCalculationService::class);

        // User case: Normal 50 kWh. Decrease 20% -> 40 kWh. Then increase 10% -> 44 kWh.
        $result = $service->compoundAdjustAverageUnits(50, [-20, 10]);

        $this->assertEquals(50, $result['base_units']);
        $this->assertEquals(44, $result['tuned_units']);
        $this->assertEquals(-12.0, $result['net_percent']); // (44 - 50)/50 = -12%
        $this->assertCount(2, $result['steps']);
        $this->assertEquals(40.0, $result['steps'][0]['after']);
        $this->assertEquals(44.0, $result['steps'][1]['after']);
    }

    public function test_meter_reading_history_records_pdf_and_working_with_working_precedence(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '9999', 'name' => 'TEST_VILLAGE', 'status' => 'active']);
        $historyService = app(MeterReadingHistoryService::class);

        $caNumber = '999900001111';

        // 1. July Bill Record (PDF extraction)
        $julyBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 7,
            'billing_year' => 2026,
            'bill_month_label' => 'JUL, 2026',
            'previous_reading' => '300',
            'current_reading' => '350',
            'units_consumed' => 50,
            'billing_basis' => 'OK',
        ]);

        $historyService->recordFromPdf($julyBill);

        $this->assertDatabaseHas('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 7,
            'billing_year' => 2026,
            'reading_source' => 'pdf',
            'current_reading' => '350',
            'units_consumed' => 50,
        ]);

        // 2. Worker enters live reading for July: 360 (60 units)
        $historyService->recordFromWorkingReading($julyBill, '360', 60, true);

        $this->assertDatabaseHas('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 7,
            'billing_year' => 2026,
            'reading_source' => 'working',
            'working_reading' => '360',
            'units_consumed' => 60,
            'is_closed' => true,
        ]);

        // 3. Query historical units for August: Working (60) takes precedence over PDF (50)
        $prior = $historyService->getPriorHistoricalUnits($user->id, $caNumber, 8, 2026);
        $this->assertCount(1, $prior);
        $this->assertEquals(60, $prior->first()['units']);
        $this->assertEquals('working', $prior->first()['source']);
    }

    public function test_2d_monthly_matrix_compiles_side_by_side_pdf_and_working_readings(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '9999', 'name' => 'TEST_VILLAGE', 'status' => 'active']);
        $caNumber = '999900002222';
        $historyService = app(MeterReadingHistoryService::class);

        // July bill with both PDF and Working
        $bill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 7,
            'billing_year' => 2026,
            'bill_month_label' => 'JUL, 2026',
            'previous_reading' => '200',
            'current_reading' => '250',
            'units_consumed' => 50,
            'billing_basis' => 'OK',
        ]);

        $historyService->recordFromPdf($bill);
        $historyService->recordFromWorkingReading($bill, '255', 55, false);

        $matrix = $historyService->getConsumerMonthlyMatrix($user->id, $caNumber);

        $this->assertEquals($caNumber, $matrix['ca_number']);
        $this->assertEquals(2, $matrix['periods_count']); // June (Month 6 baseline: 200) + July (Month 7: 255)
        $period = collect($matrix['periods'])->firstWhere('month', 7);

        $this->assertTrue($period['has_pdf']);
        $this->assertTrue($period['has_working']);
        $this->assertEquals('250', $period['pdf_reading']);
        $this->assertEquals('255', $period['working_reading']);
        $this->assertEquals(55, $period['effective_units']); // Working priority
    }

    public function test_api_save_tuning_and_compounded_dashboard_data(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '9999', 'name' => 'TEST_VILLAGE', 'status' => 'active']);
        $caNumber = '999900003333';

        // July historical bill: 50 kWh
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 7,
            'billing_year' => 2026,
            'current_reading' => '450',
            'previous_reading' => '400',
            'units_consumed' => 50,
            'billing_basis' => 'OK',
        ]);

        // August active cycle
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '450',
            'billing_basis' => 'OK',
        ]);

        // 1. Post tuning preferences: -20% then +10%
        $tuneRes = $this->actingAs($user)->postJson('/dashboard/tuning', [
            'steps' => [-20, 10],
            'base_units' => 50,
        ]);

        $tuneRes->assertStatus(200);
        $tuneRes->assertJson([
            'success' => true,
            'tuning' => [
                'base_units' => 50,
                'tuned_units' => 44,
                'net_percent' => -12.0,
            ],
        ]);

        // 2. Fetch Dashboard data: smart average should be tuned to 44 kWh
        $dashRes = $this->actingAs($user)->getJson("/dashboard/data?mru_id={$mru->id}&month=8&year=2026");
        $dashRes->assertStatus(200);

        $billData = collect($dashRes->json('data'))->firstWhere('ca_number', $caNumber);
        $this->assertNotNull($billData);
        $this->assertEquals(44, $billData['smart_avg_units']);
        $this->assertEquals(50, $billData['base_avg_units']);
        $this->assertEquals(-12.0, $billData['tuning_percent']);

        // 3. Reset tuning back to baseline 0%
        $resetRes = $this->actingAs($user)->postJson('/dashboard/tuning/reset');
        $resetRes->assertStatus(200);

        $dashRes2 = $this->actingAs($user)->getJson("/dashboard/data?mru_id={$mru->id}&month=8&year=2026");
        $billData2 = collect($dashRes2->json('data'))->firstWhere('ca_number', $caNumber);
        $this->assertEquals(50, $billData2['smart_avg_units']);
    }

    public function test_api_matrix_endpoint_returns_json(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '9999', 'name' => 'TEST_VILLAGE', 'status' => 'active']);
        $caNumber = '999900004444';

        MeterReadingHistory::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 8,
            'billing_year' => 2026,
            'reading_source' => 'working',
            'working_reading' => '650',
            'units_consumed' => 45,
            'billing_basis' => 'OK',
            'is_closed' => false,
        ]);

        $response = $this->actingAs($user)->getJson("/bills/matrix/{$caNumber}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'ca_number' => $caNumber,
                'periods_count' => 1,
            ],
        ]);
    }

    public function test_bulk_project_readings_uses_compounded_tuning_and_records_history(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '9999', 'name' => 'TEST_VILLAGE', 'status' => 'active']);
        $caNumber = '999900005555';

        // Historical bill (50 kWh)
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 7,
            'billing_year' => 2026,
            'previous_reading' => '100',
            'current_reading' => '150',
            'units_consumed' => 50,
            'billing_basis' => 'OK',
        ]);

        // Active bill (Aug 2026) with previous_reading 150
        $augBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '150',
            'billing_basis' => 'OK',
        ]);

        // Run bulk project with compounding steps [-20, 10] => 50 -> 40 -> 44 kWh
        $response = $this->actingAs($user)->postJson('/bills/bulk-project-readings', [
            'month' => 8,
            'year' => 2026,
            'mru_id' => $mru->id,
            'tuning_steps' => [-20, 10],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'count' => 1]);

        // 150 + 44 = 194
        $augBill->refresh();
        $this->assertEquals('194', $augBill->working_reading);
        $this->assertEquals(44, $augBill->units_consumed);

        // Also verify recorded into meter_reading_histories
        $this->assertDatabaseHas('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 8,
            'billing_year' => 2026,
            'reading_source' => 'working',
            'working_reading' => '194',
            'units_consumed' => 44,
        ]);
    }

    public function test_sync_history_artisan_command_backfills_all_bills(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '9999', 'name' => 'TEST_VILLAGE', 'status' => 'active']);

        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '999900006666',
            'billing_month' => 7,
            'billing_year' => 2026,
            'previous_reading' => '100',
            'current_reading' => '140',
            'units_consumed' => 40,
            'working_reading' => '145',
            'billing_basis' => 'OK',
        ]);

        $this->artisan('readings:sync-history', ['--user_id' => $user->id])
            ->assertSuccessful();

        // Should have created 3 entries: Month 6 baseline from PDF previous reading, Month 7 pdf, Month 7 working
        $this->assertEquals(3, MeterReadingHistory::where('user_id', $user->id)->count());
    }

    public function test_user_month_by_month_chained_subtraction_august_september_october(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '8888', 'name' => 'CHAIN_VILLAGE', 'status' => 'active']);
        $caNumber = '888800001234';

        $historyService = app(MeterReadingHistoryService::class);

        // 1. Source 1 (PDF): August bill has previous 400 (July) and current 450 (August)
        $augBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 8,
            'billing_year' => 2026,
            'bill_month_label' => 'AUG, 2026',
            'previous_reading' => '400',
            'current_reading' => '450',
            'units_consumed' => 50,
            'billing_basis' => 'OK',
        ]);
        $historyService->recordFromPdf($augBill);

        // Verify July baseline decoded from August PDF
        $this->assertDatabaseHas('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 7,
            'billing_year' => 2026,
            'reading_source' => 'pdf',
            'current_reading' => '400',
        ]);

        // Verify August recorded from PDF
        $this->assertDatabaseHas('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 8,
            'billing_year' => 2026,
            'reading_source' => 'pdf',
            'current_reading' => '450',
            'units_consumed' => 50,
        ]);

        // 2. Source 2 (Working Sequence): Operator works in August -> records working reading 450
        $historyService->recordFromWorkingReading($augBill, '450', 50, true);

        // 3. Move to September: auto-calculate projects +50 kWh
        // September Previous Reading = August Working Reading (450)
        $septBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 9,
            'billing_year' => 2026,
            'bill_month_label' => 'SEP, 2026',
            'previous_reading' => '450',
            'working_reading' => '500', // 450 + 50
            'units_consumed' => 50,     // 500 - 450
            'billing_basis' => 'OK',
        ]);
        $historyService->recordFromWorkingReading($septBill, '500', 50, true);

        // Verify September in history
        $this->assertDatabaseHas('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 9,
            'billing_year' => 2026,
            'reading_source' => 'working',
            'working_reading' => '500',
            'units_consumed' => 50,
        ]);

        // 4. Move to October: auto-calculate projects +40 kWh
        // October Previous Reading = September Working Reading (500)
        $octBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 10,
            'billing_year' => 2026,
            'bill_month_label' => 'OCT, 2026',
            'previous_reading' => '500',
            'working_reading' => '540', // 500 + 40
            'units_consumed' => 40,     // 540 - 500
            'billing_basis' => 'OK',
        ]);
        $historyService->recordFromWorkingReading($octBill, '540', 40, false);

        // Verify October in history
        $this->assertDatabaseHas('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 10,
            'billing_year' => 2026,
            'reading_source' => 'working',
            'working_reading' => '540',
            'units_consumed' => 40,
        ]);

        // 5. Verify 2D Matrix contains the full chained monthly sequence
        $matrix = $historyService->getConsumerMonthlyMatrix($user->id, $caNumber);
        $this->assertEquals(4, $matrix['periods_count']); // July, Aug, Sept, Oct

        // Check September period: Sept (500) - Aug (450) = 50 kWh
        $septPeriod = collect($matrix['periods'])->firstWhere('month', 9);
        $this->assertNotNull($septPeriod);
        $this->assertEquals(500, $septPeriod['effective_reading']);
        $this->assertEquals(50, $septPeriod['effective_units']);
        $this->assertEquals('500 - 450 = 50 kWh', $septPeriod['delta_formula']);

        // Check October period: Oct (540) - Sept (500) = 40 kWh
        $octPeriod = collect($matrix['periods'])->firstWhere('month', 10);
        $this->assertNotNull($octPeriod);
        $this->assertEquals(540, $octPeriod['effective_reading']);
        $this->assertEquals(40, $octPeriod['effective_units']);
        $this->assertEquals('540 - 500 = 40 kWh', $octPeriod['delta_formula']);
    }

    public function test_real_pdf_bill_decodes_meter_readings_and_12_month_consumption_history(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $parseService = app(BillParseService::class);
        $historyService = app(MeterReadingHistoryService::class);

        // Raw OCR text exactly as provided from real NBPDCL zero bill
        $sampleText = <<<'TXT'
North Bihar Power Distribution Company Ltd.
 " 'kwU; fcy / Zero Bill "
ukFkZ fcgkj i‚oj fMLVªhC;w'ku dEiuh fyfeVsM
fo|qr fcy 
 
çeaMy BARSOI/144 voj&çeaMy BARSOI_NEW/1442 ç'kk[kk BARSOI_NEW/14421
uke]irk ,oa VsfyQksu la [kkrk la[;k NSC/MACHHAIL/BPL
/01
th,lVhvkbZu 10AAECN1588M2ZB
AJIBUL SHEKH TAMIJ ALI miHkksäk la[;k 10230045267 fcy ekg APR, 2026
VILL-MACHHAIL ,TOLA-MACHHAIL ,e vkj ;q NISARBHATI 10-05-2026 rd ns; jkf'k -0.00
VILL-MACHHAIL ,TOLA-MACHHAIL
PANCH-BASALGAON ,BLOCK-BARSOI
fcy la[;k 20260410230045267 20-05-2026 rd ns; jkf'k -0.00
 DIST-KATIHAR ,74*****487 fcy frfFk 25-04-2026 20-05-2026 ds ckn ns; jkf'k -0.00
fo|qr dusD'ku dh frfFk : 28-10-2016
dusD'ku fooj.kh cdk;k fooj.kh(31-MAR-26 rd)
Js.kh KJ tekur dh jkf'k 0.00 vfxze tek
Qst 1 QhMj Sudhani(Rural) ÅtkZ cdk;k -0.08
,fj;k RURAL VªkalQkeZj la[;k NA foyEc vfèkHkkj cdk;k ¼C;kt½ 0.00
Loh—r@lafonk Hkkj 250 WT :V@iksy la[;k NA/31311883111 vU; cdk;k 0.00
ntZ Hkkj 0.10 fcy dk vkèkkj Normal(OK) dqy cdk;k¼v½ -0.08
ehVj iBu fooj.kh
ehVj la[;k orZeku ekg iwoZ ekg varj xq.kd [kir
MS frfFk iBu frfFk iBu
3805078 25-04-2026 1810 31-MAR-26 1798 12 1 12
ikoj QSDVj 0.0 fcy fnol 25(25)
dqy [kir 12
fu%'kqYd ;wfuV 12 vuqnkfur ;wfuV 0
orZeku fcy
ÅtkZ 'kqYd 89.04
fQDLM@fMekaM pktZ 16.67
vfèkD; fMekaM pktZ 0.00
fo|qr dj 5.34
dSisflVj çHkkj 0.00
vU; 'kqYd 0.00
mi&tksM+ 111.05
jkT; ljdkj vuqnku¼fu%'kqYd ;wfuV½ -111.05
jkT; ljdkj vuqnku¼vuqnkfur ;wfuV½ 0.00
orZeku foi= jkf'k 0.00
vU; fooj.kh
foyEc vfèkHkkj ¼C;kt½ 0.00
jhfe'ku ¼ ;fn dksbZ ½ 0.00
vfrfjä frekgh NwV -0.00
tekur jkf'k ij C;kt¼&½ 0.00
dqy jkf'k -0.08
lle; Hkqxrku ij NwV 0.00
10-05-2026 rd ns; jkf'k -0.00
20-05-2026 rd ns; jkf'k -0.00
20-05-2026 ds ckn ns; jkf'k -0.00
vafre Hkqxrku fooj.kh
jkf'k 145.00
jlhn la[;k NBPS1000307261
534
VªkatSDlu la
frfFk 19-10-2025
fcy lqèkkj fooj.kh
jkf'k 0.00
uksV
vU; cdk;k fooj.kh
vU; cdk;k 0.00
[kir fooj.kh
ekg [kir
APR/26 12(OK,N)
MAR/26 13(OK,N)
FEB/26 9(OK,N)
JAN/26 4(OK,N)
DEC/25 8(OK,N)
NOV/25 14(OK,N)
OCT/25 42(OK,N)
AUG/25 58(OK,N)
JUL/25 62(OK,N)
JUN/25 113(OK,N)
FEB/25 58(OK,N)
JAN/25 34(LK,A)
lHkh ?kjsyw miHkksäkvksa ls vc 125 ;wfuV rd fctyh [kir ij dksbZ 'kqYd ugha fy;k tk,xk | ;g ykHk
tqykbZ ekg dh [kir ls ykxw gSa |
TXT;

        $extracted = $parseService->extractBillData($sampleText);

        $this->assertEquals(1810, $extracted['current_reading']);
        $this->assertEquals(1798, $extracted['previous_reading']);
        $this->assertEquals(12, $extracted['units_consumed']);
        $this->assertEquals('3805078', $extracted['meter_no']);
        $this->assertEquals('NISARBHATI', $extracted['mru']);
        $this->assertEquals('OK', $extracted['billing_basis']);

        // Verify 12 months in consumption_history
        $this->assertCount(12, $extracted['consumption_history']);
        $this->assertEquals(4, $extracted['consumption_history'][0]['month']);
        $this->assertEquals(2026, $extracted['consumption_history'][0]['year']);
        $this->assertEquals(12, $extracted['consumption_history'][0]['units']);

        $this->assertEquals(3, $extracted['consumption_history'][1]['month']);
        $this->assertEquals(2026, $extracted['consumption_history'][1]['year']);
        $this->assertEquals(13, $extracted['consumption_history'][1]['units']);

        // Now save to bill and record to MeterReadingHistory
        $bill = BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '10230045267',
            'billing_month' => 4,
            'billing_year' => 2026,
            'bill_month_label' => 'APR, 2026',
            'previous_reading' => '1798',
            'current_reading' => '1810',
            'units_consumed' => 12,
            'billing_basis' => 'OK',
        ]);

        $historyService->recordFromPdf($bill, $extracted['consumption_history']);

        // Assert April is present
        $this->assertDatabaseHas('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => '10230045267',
            'billing_month' => 4,
            'billing_year' => 2026,
            'current_reading' => '1810',
            'previous_reading' => '1798',
            'units_consumed' => 12,
        ]);

        // Assert March is present with deduced previous reading (1785)
        $this->assertDatabaseHas('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => '10230045267',
            'billing_month' => 3,
            'billing_year' => 2026,
            'current_reading' => '1798',
            'previous_reading' => '1785',
            'units_consumed' => 13,
        ]);

        // Assert February is present with deduced previous reading (1776)
        $this->assertDatabaseHas('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => '10230045267',
            'billing_month' => 2,
            'billing_year' => 2026,
            'current_reading' => '1785',
            'previous_reading' => '1776',
            'units_consumed' => 9,
        ]);

        // 2D Matrix compiles all 12 decoded historical months
        $matrix = $historyService->getConsumerMonthlyMatrix($user->id, '10230045267');
        $this->assertEquals(12, $matrix['periods_count']);
    }

    public function test_cycle_creation_and_working_flow_august_to_september_and_september_to_october_stores_in_database(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        // Setup active subscription plan for MRU quota
        $plan = Plan::firstOrCreate(
            ['slug' => 'test-plan'],
            [
                'name' => 'Test Plan',
                'description' => 'Test',
                'base_price' => 0.00,
                'included_mrus' => 5,
                'included_consumers' => 5000,
                'extra_mru_rate' => 0.00,
                'extra_consumer_rate' => 0.00,
                'duration_unit' => 'month',
                'duration_value' => 1,
                'is_active' => true,
            ]
        );

        AgentSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'duration_unit' => 'month',
            'duration_value' => 1,
            'duration_months' => 1,
            'base_price_paid' => 0.00,
            'included_mrus_locked' => 5,
            'included_consumers_locked' => 5000,
            'extra_mru_rate_locked' => 0.00,
            'extra_consumer_rate_locked' => 0.00,
            'billing_start' => now(),
            'billing_end' => now()->addMonth(),
            'status' => 'active',
            'lifecycle_status' => 'active',
        ]);

        $mru = Mru::create([
            'user_id' => $user->id,
            'code' => '9988',
            'name' => 'FLOW_TEST_MRU',
            'status' => 'active',
            'is_over_quota' => false,
        ]);

        $caNumber = '102300998877';

        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'status' => 'active',
            'consumer_name' => 'FLOW TEST USER',
            'meter_no' => 'M998877',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'OK',
            'baseline_amount' => 0.00,
            'baseline_previous_reading' => '400',
        ]);

        // 1. August 2026: Operator works August reading
        $augBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 8,
            'billing_year' => 2026,
            'bill_month_label' => 'AUG, 2026',
            'previous_reading' => '400',
            'billing_basis' => 'OK',
        ]);

        // User updates August working reading to 450
        $augUpdateRes = $this->actingAs($user)->postJson('/bills/update-working-reading', [
            'id' => $augBill->id,
            'working_reading' => '450',
        ]);
        $augUpdateRes->assertStatus(200);

        // Verify August stored in bill_records: units = 450 - 400 = 50 kWh
        $this->assertDatabaseHas('bill_records', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '400',
            'working_reading' => '450',
            'units_consumed' => 50,
        ]);

        // Verify August stored in meter_reading_histories
        $this->assertDatabaseHas('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 8,
            'billing_year' => 2026,
            'previous_reading' => '400',
            'working_reading' => '450',
            'units_consumed' => 50,
            'reading_source' => 'working',
        ]);

        // 2. Operator creates new cycle for September (Month 9, 2026)
        $septCycleRes = $this->actingAs($user)->postJson('/mrus/billing-cycle', [
            'mru_id' => $mru->id,
            'billing_month' => 9,
            'billing_year' => 2026,
            'action_type' => 'create_only',
        ]);
        $septCycleRes->assertStatus(200);

        // Verify September: previous reading resolved from August working reading (450)
        // Auto-projected working reading = 450 + 50 = 500; units = 50 kWh
        $this->assertDatabaseHas('bill_records', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 9,
            'billing_year' => 2026,
            'previous_reading' => '450',
            'working_reading' => '500',
            'units_consumed' => 50,
        ]);

        // Verify September stored in meter_reading_histories
        $this->assertDatabaseHas('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 9,
            'billing_year' => 2026,
            'previous_reading' => '450',
            'working_reading' => '500',
            'units_consumed' => 50,
            'reading_source' => 'working',
        ]);

        // 3. Operator works September: enters working reading 520
        $septBill = BillRecord::where('user_id', $user->id)
            ->where('ca_number', $caNumber)
            ->where('billing_month', 9)
            ->where('billing_year', 2026)
            ->firstOrFail();

        $septUpdateRes = $this->actingAs($user)->postJson('/bills/update-working-reading', [
            'id' => $septBill->id,
            'working_reading' => '520',
        ]);
        $septUpdateRes->assertStatus(200);

        // Verify September updated: 520 - 450 = 70 kWh
        $this->assertDatabaseHas('bill_records', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 9,
            'billing_year' => 2026,
            'previous_reading' => '450',
            'working_reading' => '520',
            'units_consumed' => 70,
        ]);

        $this->assertDatabaseHas('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 9,
            'billing_year' => 2026,
            'previous_reading' => '450',
            'working_reading' => '520',
            'units_consumed' => 70,
            'reading_source' => 'working',
        ]);

        // 4. Operator creates new cycle for October (Month 10, 2026)
        $octCycleRes = $this->actingAs($user)->postJson('/mrus/billing-cycle', [
            'mru_id' => $mru->id,
            'billing_month' => 10,
            'billing_year' => 2026,
            'action_type' => 'create_only',
        ]);
        $octCycleRes->assertStatus(200);

        // Verify October: previous reading resolved from September working reading (520)
        // Auto-projected reading from median(50, 70) = 60; 520 + 60 = 580; units = 60 kWh
        $this->assertDatabaseHas('bill_records', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 10,
            'billing_year' => 2026,
            'previous_reading' => '520',
            'working_reading' => '580',
            'units_consumed' => 60,
        ]);

        // Verify October stored in meter_reading_histories
        $this->assertDatabaseHas('meter_reading_histories', [
            'user_id' => $user->id,
            'ca_number' => $caNumber,
            'billing_month' => 10,
            'billing_year' => 2026,
            'previous_reading' => '520',
            'working_reading' => '580',
            'units_consumed' => 60,
            'reading_source' => 'working',
        ]);

        // 5. Verify 2D Matrix contains all 3 consecutive periods with chained delta formulas
        $historyService = app(MeterReadingHistoryService::class);
        $matrix = $historyService->getConsumerMonthlyMatrix($user->id, $caNumber);
        $this->assertEquals(3, $matrix['periods_count']);

        // Check September period: 520 - 450 = 70 kWh
        $septPeriod = collect($matrix['periods'])->firstWhere('month', 9);
        $this->assertEquals('520 - 450 = 70 kWh', $septPeriod['delta_formula']);

        // Check October period: 580 - 520 = 60 kWh
        $octPeriod = collect($matrix['periods'])->firstWhere('month', 10);
        $this->assertEquals('580 - 520 = 60 kWh', $octPeriod['delta_formula']);
    }
}
