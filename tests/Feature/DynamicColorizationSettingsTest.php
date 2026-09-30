<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\Mru;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicColorizationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        SystemSetting::clearRuntimeCache();

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');

        $this->agent = User::factory()->create(['status' => 'active']);
        $this->agent->assignRole('user');
    }

    public function test_non_admin_cannot_access_or_update_engine_settings(): void
    {
        $response = $this->actingAs($this->agent)->get(route('admin.bills.engine-settings'));
        $response->assertStatus(403);

        $postResponse = $this->actingAs($this->agent)->post(route('admin.bills.engine-settings.update'), [
            'has_color_settings' => 1,
            'dynamic_colorization_enabled' => 1,
            'color_amount_safe_ceiling' => 600,
        ]);
        $postResponse->assertStatus(403);
    }

    public function test_admin_can_view_dynamic_colorization_settings_section(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.bills.engine-settings'));
        $response->assertStatus(200);
        $response->assertSee('Dynamic Visual Colorization & Threshold Ranges', false);
        $response->assertSee('Rural Low-Cap');
        $response->assertSee('Balanced Default');
        $response->assertSee('Urban High-Cap');
        $response->assertSee('color_amount_safe_ceiling');
        $response->assertSee('color_units_danger_floor');
    }

    public function test_admin_can_update_colorization_thresholds_and_persist_in_system_setting(): void
    {
        $payload = [
            'has_color_settings' => 1,
            'dynamic_colorization_enabled' => 1,
            'color_amount_safe_ceiling' => 800,
            'color_amount_warning_ceiling' => 2500,
            'color_amount_danger_floor' => 5000,
            'color_units_safe_ceiling' => 65,
            'color_units_warning_ceiling' => 140,
            'color_units_danger_floor' => 250,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.bills.engine-settings.update'), $payload);
        $response->assertRedirect(route('admin.bills.engine-settings'));
        $response->assertSessionHas('status');

        $this->assertTrue((bool) SystemSetting::get('dynamic_colorization_enabled'));
        $this->assertEquals(800.0, (float) SystemSetting::get('color_amount_safe_ceiling'));
        $this->assertEquals(2500.0, (float) SystemSetting::get('color_amount_warning_ceiling'));
        $this->assertEquals(5000.0, (float) SystemSetting::get('color_amount_danger_floor'));
        $this->assertEquals(65, (int) SystemSetting::get('color_units_safe_ceiling'));
        $this->assertEquals(140, (int) SystemSetting::get('color_units_warning_ceiling'));
        $this->assertEquals(250, (int) SystemSetting::get('color_units_danger_floor'));
    }

    public function test_admin_can_disable_dynamic_colorization(): void
    {
        SystemSetting::set('dynamic_colorization_enabled', true);

        $payload = [
            'has_color_settings' => 1,
            // When unchecked in HTML form, dynamic_colorization_enabled is not sent
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.bills.engine-settings.update'), $payload);
        $response->assertRedirect(route('admin.bills.engine-settings'));

        $this->assertFalse((bool) SystemSetting::get('dynamic_colorization_enabled'));
    }

    public function test_reset_to_defaults_restores_standard_thresholds(): void
    {
        // First set non-default custom values
        SystemSetting::set('dynamic_colorization_enabled', false);
        SystemSetting::set('color_amount_safe_ceiling', 999.0);
        SystemSetting::set('color_amount_warning_ceiling', 3333.0);
        SystemSetting::set('color_amount_danger_floor', 7777.0);
        SystemSetting::set('color_units_safe_ceiling', 90);
        SystemSetting::set('color_units_warning_ceiling', 190);
        SystemSetting::set('color_units_danger_floor', 350);

        $response = $this->actingAs($this->admin)->post(route('admin.bills.engine-settings.reset'));
        $response->assertRedirect(route('admin.bills.engine-settings'));
        $response->assertSessionHas('status');

        // Verify restored defaults
        $this->assertTrue((bool) SystemSetting::get('dynamic_colorization_enabled'));
        $this->assertEquals(500.0, (float) SystemSetting::get('color_amount_safe_ceiling'));
        $this->assertEquals(1500.0, (float) SystemSetting::get('color_amount_warning_ceiling'));
        $this->assertEquals(2500.0, (float) SystemSetting::get('color_amount_danger_floor'));
        $this->assertEquals(50, (int) SystemSetting::get('color_units_safe_ceiling'));
        $this->assertEquals(120, (int) SystemSetting::get('color_units_warning_ceiling'));
        $this->assertEquals(200, (int) SystemSetting::get('color_units_danger_floor'));
    }

    public function test_dashboard_data_endpoint_returns_color_settings_and_correct_zone_classifications(): void
    {
        // Ensure default thresholds: 500 / 1500 / 2500 and 50 / 120 / 200
        SystemSetting::set('dynamic_colorization_enabled', true);
        SystemSetting::set('color_amount_safe_ceiling', 500.0);
        SystemSetting::set('color_amount_warning_ceiling', 1500.0);
        SystemSetting::set('color_amount_danger_floor', 2500.0);
        SystemSetting::set('color_units_safe_ceiling', 50);
        SystemSetting::set('color_units_warning_ceiling', 120);
        SystemSetting::set('color_units_danger_floor', 200);

        $mru = Mru::create([
            'user_id' => $this->agent->id,
            'code' => 'TEST_COLOR_MRU',
            'name' => 'Colorization Test MRU',
        ]);

        // Record 1: Safe Zone (Amount <= 500, Units <= 50)
        BillRecord::create([
            'user_id' => $this->agent->id,
            'mru_id' => $mru->id,
            'ca_number' => '999900000001',
            'consumer_name' => 'Safe Consumer',
            'billing_month' => 9,
            'billing_year' => 2026,
            'total_amount' => 320.00,
            'units_consumed' => 35,
            'billing_basis' => 'OK',
            'current_reading' => '1035',
            'previous_reading' => '1000',
        ]);

        // Record 2: Moderate Zone (Amount between 500 and 2500, Units between 50 and 200)
        BillRecord::create([
            'user_id' => $this->agent->id,
            'mru_id' => $mru->id,
            'ca_number' => '999900000002',
            'consumer_name' => 'Moderate Consumer',
            'billing_month' => 9,
            'billing_year' => 2026,
            'total_amount' => 1750.00,
            'units_consumed' => 110,
            'billing_basis' => 'OK',
            'current_reading' => '2110',
            'previous_reading' => '2000',
        ]);

        // Record 3: Alert Zone (Amount >= 2500, Units >= 200)
        BillRecord::create([
            'user_id' => $this->agent->id,
            'mru_id' => $mru->id,
            'ca_number' => '999900000003',
            'consumer_name' => 'High Alert Consumer',
            'billing_month' => 9,
            'billing_year' => 2026,
            'total_amount' => 3800.00,
            'units_consumed' => 260,
            'billing_basis' => 'OK',
            'current_reading' => '3260',
            'previous_reading' => '3000',
        ]);

        $response = $this->actingAs($this->agent)->getJson(route('dashboard.data', [
            'month' => 9,
            'year' => 2026,
            'mru_id' => $mru->id,
        ]));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verify color_settings in root JSON
        $colorSettings = $response->json('color_settings');
        $this->assertNotNull($colorSettings);
        $this->assertTrue($colorSettings['enabled']);
        $this->assertEquals(500.0, $colorSettings['amount_safe_ceiling']);
        $this->assertEquals(1500.0, $colorSettings['amount_warning_ceiling']);
        $this->assertEquals(2500.0, $colorSettings['amount_danger_floor']);
        $this->assertEquals(50, $colorSettings['units_safe_ceiling']);
        $this->assertEquals(120, $colorSettings['units_warning_ceiling']);
        $this->assertEquals(200, $colorSettings['units_danger_floor']);

        // Verify records have zone indicators
        $records = collect($response->json('data'))->keyBy('ca_number');

        // Record 1
        $rec1 = $records->get('999900000001');
        $this->assertNotNull($rec1);
        $this->assertEquals('safe', $rec1['amount_zone']);
        $this->assertEquals('safe', $rec1['avg_units_zone']);

        // Record 2
        $rec2 = $records->get('999900000002');
        $this->assertNotNull($rec2);
        $this->assertEquals('moderate', $rec2['amount_zone']);
        $this->assertEquals('moderate', $rec2['avg_units_zone']);

        // Record 3
        $rec3 = $records->get('999900000003');
        $this->assertNotNull($rec3);
        $this->assertEquals('alert', $rec3['amount_zone']);
        $this->assertEquals('alert', $rec3['avg_units_zone']);
    }

    public function test_custom_thresholds_dynamically_adjust_zone_classifications(): void
    {
        // Custom thresholds: higher safe ceiling ₹1000, lower alert floor ₹1500
        SystemSetting::set('dynamic_colorization_enabled', true);
        SystemSetting::set('color_amount_safe_ceiling', 1000.0);
        SystemSetting::set('color_amount_warning_ceiling', 1200.0);
        SystemSetting::set('color_amount_danger_floor', 1500.0);
        SystemSetting::set('color_units_safe_ceiling', 100);
        SystemSetting::set('color_units_warning_ceiling', 150);
        SystemSetting::set('color_units_danger_floor', 180);

        $mru = Mru::create([
            'user_id' => $this->agent->id,
            'code' => 'CUSTOM_THRESH_MRU',
            'name' => 'Custom Threshold MRU',
        ]);

        // Bill with ₹800 is now 'safe' because safe ceiling is 1000
        BillRecord::create([
            'user_id' => $this->agent->id,
            'mru_id' => $mru->id,
            'ca_number' => '999900000004',
            'consumer_name' => 'Shifted Consumer',
            'billing_month' => 9,
            'billing_year' => 2026,
            'total_amount' => 800.00,
            'units_consumed' => 190, // >= 180 danger floor -> alert
            'billing_basis' => 'OK',
            'current_reading' => '1190',
            'previous_reading' => '1000',
        ]);

        $response = $this->actingAs($this->agent)->getJson(route('dashboard.data', [
            'month' => 9,
            'year' => 2026,
            'mru_id' => $mru->id,
        ]));

        $response->assertStatus(200);
        $rec = collect($response->json('data'))->firstWhere('ca_number', '999900000004');
        $this->assertNotNull($rec);
        $this->assertEquals('safe', $rec['amount_zone']);
        $this->assertEquals('alert', $rec['avg_units_zone']);
    }

    public function test_dashboard_view_contains_color_settings_and_helpers(): void
    {
        $response = $this->actingAs($this->agent)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertViewHas('colorSettings');
        $response->assertSee('getAmountStyle', false);
        $response->assertSee('getAvgUnitStyle', false);
        $response->assertSee('Advance / Credit', false);
        $response->assertSee('Alert', false);
    }

    public function test_validation_rejects_inverted_amount_thresholds(): void
    {
        // Safe ceiling > Warning ceiling
        $response1 = $this->actingAs($this->admin)->post(route('admin.bills.engine-settings.update'), [
            'has_color_settings' => 1,
            'color_amount_safe_ceiling' => 2000,
            'color_amount_warning_ceiling' => 1000,
            'color_amount_danger_floor' => 3000,
        ]);
        $response1->assertSessionHasErrors('color_amount_safe_ceiling');

        // Warning ceiling > Danger floor
        $response2 = $this->actingAs($this->admin)->post(route('admin.bills.engine-settings.update'), [
            'has_color_settings' => 1,
            'color_amount_safe_ceiling' => 500,
            'color_amount_warning_ceiling' => 3500,
            'color_amount_danger_floor' => 2000,
        ]);
        $response2->assertSessionHasErrors('color_amount_warning_ceiling');
    }

    public function test_validation_rejects_inverted_units_thresholds(): void
    {
        // Units safe ceiling > Warning ceiling
        $response1 = $this->actingAs($this->admin)->post(route('admin.bills.engine-settings.update'), [
            'has_color_settings' => 1,
            'color_units_safe_ceiling' => 150,
            'color_units_warning_ceiling' => 100,
            'color_units_danger_floor' => 200,
        ]);
        $response1->assertSessionHasErrors('color_units_safe_ceiling');

        // Units warning ceiling > Danger floor
        $response2 = $this->actingAs($this->admin)->post(route('admin.bills.engine-settings.update'), [
            'has_color_settings' => 1,
            'color_units_safe_ceiling' => 50,
            'color_units_warning_ceiling' => 250,
            'color_units_danger_floor' => 200,
        ]);
        $response2->assertSessionHasErrors('color_units_warning_ceiling');
    }

    public function test_partial_threshold_updates_are_supported_without_overwriting_unrelated_settings(): void
    {
        SystemSetting::set('color_amount_safe_ceiling', 500.0);
        SystemSetting::set('color_amount_warning_ceiling', 1500.0);
        SystemSetting::set('color_amount_danger_floor', 2500.0);

        // Update only safe ceiling to 400 without sending other fields
        $response = $this->actingAs($this->admin)->post(route('admin.bills.engine-settings.update'), [
            'color_amount_safe_ceiling' => 400,
        ]);
        $response->assertRedirect(route('admin.bills.engine-settings'));
        $response->assertSessionHasNoErrors();

        $this->assertEquals(400.0, (float) SystemSetting::get('color_amount_safe_ceiling'));
        $this->assertEquals(1500.0, (float) SystemSetting::get('color_amount_warning_ceiling'));
        $this->assertEquals(2500.0, (float) SystemSetting::get('color_amount_danger_floor'));
    }

    public function test_exact_boundary_values_and_negative_amounts_for_zones(): void
    {
        SystemSetting::set('dynamic_colorization_enabled', true);
        SystemSetting::set('color_amount_safe_ceiling', 500.0);
        SystemSetting::set('color_amount_warning_ceiling', 1500.0);
        SystemSetting::set('color_amount_danger_floor', 2500.0);
        SystemSetting::set('color_units_safe_ceiling', 50);
        SystemSetting::set('color_units_warning_ceiling', 120);
        SystemSetting::set('color_units_danger_floor', 200);

        $mru = Mru::create([
            'user_id' => $this->agent->id,
            'code' => 'BOUNDARY_MRU',
            'name' => 'Boundary Test MRU',
        ]);

        // Negative amount (credit): must be safe
        BillRecord::create([
            'user_id' => $this->agent->id,
            'mru_id' => $mru->id,
            'ca_number' => '999900000010',
            'consumer_name' => 'Credit Consumer',
            'billing_month' => 9,
            'billing_year' => 2026,
            'total_amount' => -250.00,
            'units_consumed' => 0,
            'billing_basis' => 'OK',
            'current_reading' => '1000',
            'previous_reading' => '1000',
        ]);

        // Exact safe ceiling boundary: 500.00 and 50 kWh -> both safe
        BillRecord::create([
            'user_id' => $this->agent->id,
            'mru_id' => $mru->id,
            'ca_number' => '999900000011',
            'consumer_name' => 'Ceiling Safe Consumer',
            'billing_month' => 9,
            'billing_year' => 2026,
            'total_amount' => 500.00,
            'units_consumed' => 50,
            'billing_basis' => 'OK',
            'current_reading' => '1050',
            'previous_reading' => '1000',
        ]);

        // Exact danger floor boundary: 2500.00 and 200 kWh -> both alert
        BillRecord::create([
            'user_id' => $this->agent->id,
            'mru_id' => $mru->id,
            'ca_number' => '999900000012',
            'consumer_name' => 'Floor Alert Consumer',
            'billing_month' => 9,
            'billing_year' => 2026,
            'total_amount' => 2500.00,
            'units_consumed' => 200,
            'billing_basis' => 'OK',
            'current_reading' => '1200',
            'previous_reading' => '1000',
        ]);

        $response = $this->actingAs($this->agent)->getJson(route('dashboard.data', [
            'month' => 9,
            'year' => 2026,
            'mru_id' => $mru->id,
        ]));

        $response->assertStatus(200);
        $records = collect($response->json('data'))->keyBy('ca_number');

        $recCredit = $records->get('999900000010');
        $this->assertEquals('safe', $recCredit['amount_zone']);
        $this->assertEquals('safe', $recCredit['avg_units_zone']);

        $recSafe = $records->get('999900000011');
        $this->assertEquals('safe', $recSafe['amount_zone']);
        $this->assertEquals('safe', $recSafe['avg_units_zone']);

        $recAlert = $records->get('999900000012');
        $this->assertEquals('alert', $recAlert['amount_zone']);
        $this->assertEquals('alert', $recAlert['avg_units_zone']);
    }
}
