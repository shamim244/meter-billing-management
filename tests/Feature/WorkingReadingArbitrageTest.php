<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use App\Services\SmartAverageCalculationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkingReadingArbitrageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_arbitrage_uses_previous_plus_average_when_previous_is_greater(): void
    {
        $service = app(SmartAverageCalculationService::class);

        // Previous (1200) > PDF (1100), Average = 50 -> Working = 1200 + 50 = 1250
        $projected = $service->calculateProjectedReading(1200, 50, 1100, 0);

        $this->assertEquals(1250, $projected);
    }

    public function test_arbitrage_uses_pdf_plus_average_when_pdf_is_greater(): void
    {
        $service = app(SmartAverageCalculationService::class);

        // PDF (1100) > Previous (1000), Average = 50 -> Working = 1100 + 50 = 1150
        $projected = $service->calculateProjectedReading(1000, 50, 1100, 0);

        $this->assertEquals(1150, $projected);
    }

    public function test_arbitrage_uses_previous_when_pdf_is_null_or_zero(): void
    {
        $service = app(SmartAverageCalculationService::class);

        // Previous (1000), PDF is null, Average = 50 -> Working = 1000 + 50 = 1050
        $projectedNull = $service->calculateProjectedReading(1000, 50, null, 0);
        $this->assertEquals(1050, $projectedNull);

        // Previous (1000), PDF is 0, Average = 50 -> Working = 1000 + 50 = 1050
        $projectedZero = $service->calculateProjectedReading(1000, 50, 0, 0);
        $this->assertEquals(1050, $projectedZero);
    }

    public function test_arbitrage_uses_pdf_when_previous_is_null_or_zero(): void
    {
        $service = app(SmartAverageCalculationService::class);

        // Previous is null, PDF (1100), Average = 50 -> Working = 1100 + 50 = 1150
        $projectedNull = $service->calculateProjectedReading(null, 50, 1100, 0);
        $this->assertEquals(1150, $projectedNull);

        // Previous is 0, PDF (1100), Average = 50 -> Working = 1100 + 50 = 1150
        $projectedZero = $service->calculateProjectedReading(0, 50, 1100, 0);
        $this->assertEquals(1150, $projectedZero);
    }

    public function test_arbitrage_respects_tuning_percentage_with_greater_anchor(): void
    {
        $service = app(SmartAverageCalculationService::class);

        // PDF (1100) > Prev (1000). Avg = 50. Adjustment = +20% -> Tuned Avg = 60
        // Working = 1100 + 60 = 1160
        $projected = $service->calculateProjectedReading(1000, 50, 1100, 20);

        $this->assertEquals(1160, $projected);
    }

    public function test_update_working_reading_cascades_using_arbitrage_to_future_bills(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => 'TEST_ARBITRAGE', 'name' => 'Arbitrage MRU', 'status' => 'active']);
        $caNumber = '999988887777';

        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'consumer_name' => 'Arbitrage Test Consumer',
            'status' => 'active',
        ]);

        // Cycle 1: Month 7 (July)
        $julyBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 7,
            'billing_year' => 2026,
            'working_reading' => '1000',
            'previous_reading' => '950',
            'units_consumed' => 50,
            'review_status' => 'pending',
            'reading_source' => 'manual',
        ]);

        // Cycle 2: Month 8 (August) - has PDF reading 1200 which is higher than July chain
        $augustBill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 8,
            'billing_year' => 2026,
            'working_reading' => '1050',
            'current_reading' => '1200', // PDF reading is 1200!
            'previous_reading' => '1000',
            'units_consumed' => 50,
            'review_status' => 'pending',
            'reading_source' => 'auto',
        ]);

        // Worker updates July reading to 1050
        $response = $this->actingAs($user)->postJson(route('bills.update-working-reading'), [
            'id' => $julyBill->id,
            'working_reading' => '1050',
            'source' => 'manual',
        ]);

        $response->assertOk();

        // August should cascade: max(1050, 1200) + 50 = 1200 + 50 = 1250
        $augustBill->refresh();
        $this->assertEquals('1250', $augustBill->working_reading);
    }

    public function test_bulk_project_readings_applies_arbitrage_formula(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => 'TEST_BULK_ARB', 'name' => 'Bulk Arb MRU', 'status' => 'active']);
        $caNumber = '999988886666';

        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'consumer_name' => 'Bulk Arb Consumer',
            'baseline_previous_reading' => '1000',
            'status' => 'active',
        ]);

        // August bill: DB prev is 1000, but PDF reading is 1150, avg is 50
        $bill = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $caNumber,
            'billing_month' => 8,
            'billing_year' => 2026,
            'current_reading' => '1150', // PDF reading
            'previous_reading' => '1000',
            'units_consumed' => 50,
            'working_reading' => null,
            'review_status' => 'pending',
            'reading_source' => 'auto',
        ]);

        $response = $this->actingAs($user)->postJson(route('bills.bulk-project-readings'), [
            'month' => 8,
            'year' => 2026,
            'mru_id' => $mru->id,
        ]);

        $response->assertOk();

        $bill->refresh();
        // Anchor is max(1000, 1150) = 1150. Working = 1150 + 50 = 1200
        $this->assertEquals('1200', $bill->working_reading);
    }
}
