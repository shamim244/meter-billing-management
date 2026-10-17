<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BidirectionalAverageTuningGuiAndApiTest extends TestCase
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
            'email' => 'bidirectional_tester@nbpdcl.test',
            'status' => 'active',
        ]);

        $keyResult = ApiKey::generate($this->user, 'Test Suite Key');
        $this->plainApiKey = $keyResult['plainTextToken'];

        $this->mru = Mru::create([
            'user_id' => $this->user->id,
            'code' => '9905',
            'name' => 'BIDIRECTIONAL_TEST_MRU',
            'status' => 'active',
        ]);
    }

    public function test_browser_gui_tune_updates_consumer_and_reflects_in_api(): void
    {
        $ca = '99050000111';

        // 1. Setup consumer with historical bill (50 units baseline)
        ConsumerAccount::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'consumer_name' => 'GUI TO API CONSUMER',
            'status' => 'active',
            'billing_basis' => 'OK',
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'billing_month' => 9,
            'billing_year' => 2026,
            'previous_reading' => '1000',
            'current_reading' => '1050',
            'units_consumed' => 50,
            'billing_basis' => 'OK',
            'review_status' => 'submitted',
        ]);

        $activeBill = BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'billing_month' => 10,
            'billing_year' => 2026,
            'previous_reading' => '1050',
            'working_reading' => '1100',
            'reading_source' => 'auto',
            'review_status' => 'pending',
            'billing_basis' => 'OK',
        ]);

        // 2. GUI action: User modifies tuning via web dashboard modal (POST /consumers/{ca}/average-tuning)
        $guiResponse = $this->actingAs($this->user)->postJson("/consumers/{$ca}/average-tuning", [
            'min_avg_units' => 65,
            'max_avg_units' => 110,
            'custom_avg_units' => null,
            'is_avg_excluded' => false,
            'month' => 10,
            'year' => 2026,
        ]);

        $guiResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('consumer.min_avg_units', 65)
            ->assertJsonPath('consumer.max_avg_units', 110);

        // 3. API verification: External client calls GET /api/v1/bills
        $apiBillRes = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->getJson("/api/v1/bills?mru_id={$this->mru->id}&month=10&year=2026&search={$ca}");

        $apiBillRes->assertOk();
        $billData = collect($apiBillRes->json('data'))->firstWhere('ca_number', $ca);

        $this->assertNotNull($billData);
        // Pure baseline guaranteed
        $this->assertEquals(50, $billData['smart_avg_units']);
        $this->assertEquals(50, $billData['base_avg_units']);
        // Tuned companion field reflects the GUI update (clamped to min floor 65)
        $this->assertEquals(65, $billData['tuned_avg_units']);
        $this->assertEquals(65, $billData['min_avg_units']);
        $this->assertEquals(110, $billData['max_avg_units']);
        $this->assertTrue($billData['is_clamped_min']);

        // 4. API verification: Mobile sync download gets the GUI tuning
        $syncRes = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->getJson("/api/v1/sync/mrus/{$this->mru->id}/download");

        $syncRes->assertOk();
        $syncData = collect($syncRes->json('consumers'))->firstWhere('ca_number', $ca);

        $this->assertNotNull($syncData);
        $this->assertEquals(50, $syncData['smart_average_kwh']);
        $this->assertEquals(50, $syncData['base_avg_units']);
        $this->assertEquals(65, $syncData['tuned_avg_units']);
        $this->assertEquals(65, $syncData['min_avg_units']);
        $this->assertEquals(110, $syncData['max_avg_units']);
    }

    public function test_api_tune_updates_consumer_and_reflects_in_browser_gui_dashboard(): void
    {
        $ca = '99050000222';

        ConsumerAccount::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'consumer_name' => 'API TO GUI CONSUMER',
            'status' => 'active',
            'billing_basis' => 'OK',
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'billing_month' => 9,
            'billing_year' => 2026,
            'previous_reading' => '2000',
            'current_reading' => '2050',
            'units_consumed' => 50,
            'billing_basis' => 'OK',
            'review_status' => 'submitted',
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'billing_month' => 10,
            'billing_year' => 2026,
            'previous_reading' => '2050',
            'working_reading' => '2100',
            'reading_source' => 'auto',
            'review_status' => 'pending',
            'billing_basis' => 'OK',
        ]);

        // 1. API action: External script updates tuning via POST /api/v1/consumers/{$ca}/average-tuning
        $apiPostRes = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->postJson("/api/v1/consumers/{$ca}/average-tuning", [
                'min_avg_units' => 75,
                'max_avg_units' => 125,
                'custom_avg_units' => 80,
                'is_avg_excluded' => false,
                'month' => 10,
                'year' => 2026,
            ]);

        $apiPostRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('consumer.min_avg_units', 75)
            ->assertJsonPath('consumer.max_avg_units', 125)
            ->assertJsonPath('consumer.custom_avg_units', 80);

        // 2. GUI verification: Browser user loads web dashboard data (GET /dashboard/data)
        $guiDataRes = $this->actingAs($this->user)
            ->getJson("/dashboard/data?mru_id={$this->mru->id}&month=10&year=2026");

        $guiDataRes->assertOk();
        $dashboardItems = collect($guiDataRes->json('data'))->keyBy('ca_number');
        $item = $dashboardItems->get($ca);

        $this->assertNotNull($item);
        // Dashboard card properties reflect the API tuning
        $this->assertEquals(75, $item['min_avg_units']);
        $this->assertEquals(125, $item['max_avg_units']);
        $this->assertEquals(80, $item['custom_avg_units']);
        // When custom override is active, both GUI and API respect the explicit custom override
        $this->assertTrue($item['is_custom_override']);
        $this->assertEquals(80, $item['smart_avg_units']);
    }

    public function test_submitted_account_is_protected_from_tuning_mutation_in_both_gui_and_api(): void
    {
        $ca = '99050000333';

        ConsumerAccount::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'consumer_name' => 'LOCKED SUBMITTED CONSUMER',
            'status' => 'active',
            'billing_basis' => 'OK',
        ]);

        // Submitted bill with working reading 3045
        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'billing_month' => 10,
            'billing_year' => 2026,
            'previous_reading' => '3000',
            'working_reading' => '3045',
            'units_consumed' => 45,
            'reading_source' => 'auto',
            'review_status' => 'submitted', // Finalized!
            'billing_basis' => 'OK',
        ]);

        // GUI verification with batch tuning (+50%)
        $guiRes = $this->actingAs($this->user)
            ->getJson("/dashboard/data?mru_id={$this->mru->id}&month=10&year=2026&tuning_enabled=1&adjustment_percent=50&exclude_submitted=1");

        $guiRes->assertOk();
        $item = collect($guiRes->json('data'))->firstWhere('ca_number', $ca);

        $this->assertNotNull($item);
        $this->assertTrue($item['is_avg_excluded']);
        $this->assertEquals('3045', (string) $item['working_reading']);

        // API verification: GET /api/v1/bills
        $apiRes = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->getJson("/api/v1/bills?mru_id={$this->mru->id}&month=10&year=2026&search={$ca}");

        $apiRes->assertOk();
        $apiItem = collect($apiRes->json('data'))->firstWhere('ca_number', $ca);

        $this->assertNotNull($apiItem);
        $this->assertEquals('submitted', $apiItem['review_status']);
        $this->assertEquals('3045', (string) $apiItem['working_reading']);
    }

    public function test_inverted_tuning_bounds_rejected_with_422_in_both_gui_and_api(): void
    {
        $ca = '99050000444';

        // 1. GUI rejection check (POST /consumers/{ca}/average-tuning)
        $guiRes = $this->actingAs($this->user)->postJson("/consumers/{$ca}/average-tuning", [
            'min_avg_units' => 100,
            'max_avg_units' => 40,
        ]);
        $guiRes->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonFragment(['message' => 'Min Floor (100 kWh) cannot be greater than Max Ceiling (40 kWh).']);

        // 2. API rejection check (POST /api/v1/consumers/{ca}/average-tuning)
        $apiRes = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->postJson("/api/v1/consumers/{$ca}/average-tuning", [
                'min_avg_units' => 100,
                'max_avg_units' => 40,
            ]);
        $apiRes->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonFragment(['message' => 'Min Floor (100 kWh) cannot be greater than Max Ceiling (40 kWh).']);
    }

    public function test_api_apply_tuning_query_parameter_controls_suggested_reading_and_preserves_dual_layer_averages(): void
    {
        $ca = '99050000555';

        ConsumerAccount::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'consumer_name' => 'APPLY TUNING PARAM CONSUMER',
            'status' => 'active',
            'billing_basis' => 'OK',
            'min_avg_units' => 60, // Min floor clamp (above 50)
            'max_avg_units' => 100,
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'billing_month' => 9,
            'billing_year' => 2026,
            'previous_reading' => '5000',
            'current_reading' => '5050',
            'units_consumed' => 50,
            'billing_basis' => 'OK',
            'review_status' => 'submitted',
        ]);

        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $ca,
            'billing_month' => 10,
            'billing_year' => 2026,
            'previous_reading' => '5050',
            'working_reading' => null,
            'reading_source' => 'auto',
            'review_status' => 'pending',
            'billing_basis' => 'OK',
            'min_avg_units' => 60,
            'max_avg_units' => 100,
        ]);

        // Default GET /api/v1/bills: returns pure baseline (50) and clamped companion (60)
        $defaultRes = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->getJson("/api/v1/bills?mru_id={$this->mru->id}&month=10&year=2026&search={$ca}");

        $defaultRes->assertOk();
        $defaultItem = collect($defaultRes->json('data'))->firstWhere('ca_number', $ca);
        $this->assertEquals(50, $defaultItem['smart_avg_units']);
        $this->assertEquals(50, $defaultItem['base_avg_units']);
        $this->assertEquals(60, $defaultItem['tuned_avg_units']);

        // Explicit GET /api/v1/bills?apply_tuning=true
        $tunedRes = $this->withHeader('X-API-Key', $this->plainApiKey)
            ->getJson("/api/v1/bills?mru_id={$this->mru->id}&month=10&year=2026&search={$ca}&apply_tuning=true");

        $tunedRes->assertOk();
        $tunedItem = collect($tunedRes->json('data'))->firstWhere('ca_number', $ca);
        $this->assertEquals(50, $tunedItem['smart_avg_units']);
        $this->assertEquals(50, $tunedItem['base_avg_units']);
        $this->assertEquals(60, $tunedItem['tuned_avg_units']);
    }
}
