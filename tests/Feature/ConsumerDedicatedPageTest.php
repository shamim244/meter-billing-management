<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\FieldDeskAction;
use App\Models\FieldDeskCategory;
use App\Models\MeterInspection;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConsumerDedicatedPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_operator_can_view_dedicated_consumer_page(): void
    {
        $user = User::factory()->create();
        $mru = Mru::create(['user_id' => $user->id, 'code' => 'MRU_D1', 'name' => 'Dedicated MRU']);
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '88880002221',
            'consumer_name' => 'Dev Test Consumer',
            'meter_no' => 'MTR-99881',
            'tariff_category' => 'DS1D',
            'billing_basis' => 'OK',
            'address' => 'Village Rampur Ward 4',
        ]);

        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => $consumer->ca_number,
            'bill_number' => 'BILL-2026-09-01',
            'billing_year' => 2026,
            'billing_month' => 9,
            'total_amount' => 1450.00,
            'units_consumed' => 120,
            'previous_reading' => 3000,
            'current_reading' => 3120,
            'bill_date' => now(),
            'due_date' => now()->addDays(15),
        ]);

        BillStatus::create([
            'user_id' => $user->id,
            'ca_number' => $consumer->ca_number,
            'billing_year' => 2026,
            'billing_month' => 9,
            'status' => 'Submitted',
            'reading_value' => '3120',
        ]);

        MeterInspection::create([
            'user_id' => $user->id,
            'consumer_account_id' => $consumer->id,
            'ca_number' => $consumer->ca_number,
            'physical_reading' => '3180',
            'inspected_at' => now()->subDays(2),
            'notes' => 'On-site meter inspection verified',
        ]);

        $category = FieldDeskCategory::firstOrCreate(
            ['code' => 'payment_promise'],
            ['name' => 'Payment Commitment', 'icon' => '💳', 'color' => 'indigo', 'is_active' => true]
        );

        FieldDeskAction::create([
            'user_id' => $user->id,
            'consumer_account_id' => $consumer->id,
            'ca_number' => $consumer->ca_number,
            'category_id' => $category->id,
            'original_target_date' => now()->addDays(3),
            'target_date' => now()->addDays(3),
            'status' => 'open',
            'priority' => 'high',
        ]);

        $response = $this->actingAs($user)->get(route('consumers.show', ['ca' => $consumer->ca_number]));

        $response->assertOk()
            ->assertViewIs('consumers.show')
            ->assertViewHasAll([
                'account',
                'bills',
                'statuses',
                'gapSummary',
                'inspections',
                'fieldDeskActions',
                'meterMatrix',
                'stats',
                'defaultTab',
            ])
            ->assertSee('Dev Test Consumer')
            ->assertSee('88880002221')
            ->assertSee('MTR-99881')
            ->assertSee('₹1,450')
            ->assertSee('On-site meter inspection verified');
    }

    public function test_backward_compatibility_history_route_renders_consumer_page(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '88880002222',
            'consumer_name' => 'Alias Route Consumer',
        ]);

        $response = $this->actingAs($user)->get('/bills/history/'.$consumer->ca_number);

        $response->assertOk()
            ->assertViewIs('consumers.show')
            ->assertSee('Alias Route Consumer');
    }

    public function test_operator_cannot_view_another_operators_consumer(): void
    {
        $operator1 = User::factory()->create();
        $operator2 = User::factory()->create();

        $consumer = ConsumerAccount::create([
            'user_id' => $operator2->id,
            'ca_number' => '88880002223',
            'consumer_name' => 'Private Operator 2 Consumer',
        ]);

        $response = $this->actingAs($operator1)->get(route('consumers.show', ['ca' => $consumer->ca_number]));

        $response->assertNotFound();
    }

    public function test_admin_can_view_any_consumer_page(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->assignRole($adminRole);

        $operator = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $operator->id,
            'ca_number' => '88880002224',
            'consumer_name' => 'Operator Scoped Consumer',
        ]);

        $response = $this->actingAs($admin)->get(route('consumers.show', ['ca' => $consumer->ca_number]));

        $response->assertOk()
            ->assertViewIs('consumers.show')
            ->assertSee('Operator Scoped Consumer');
    }

    public function test_authorized_operator_can_update_consumer_profile(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '88880002225',
            'consumer_name' => 'Original Name',
            'mobile' => '9876543210',
            'address' => 'Old Address',
            'tariff_category' => 'DS1D',
            'billing_basis' => 'OK',
        ]);

        $payload = [
            'consumer_name' => 'Updated Name',
            'father_name' => 'Father Name',
            'mobile' => '9998887776',
            'address' => 'New Mohalla Ward 2',
            'meter_no' => 'MTR-NEW-01',
            'tariff_category' => 'NDS1D',
            'billing_basis' => 'OK',
            'custom_safe_ceiling' => 150,
            'gap_recovery_mode' => 'hold_advance',
        ];

        $response = $this->actingAs($user)->putJson(route('consumers.update', ['ca' => $consumer->ca_number]), $payload);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $consumer->refresh();
        $this->assertEquals('Updated Name', $consumer->consumer_name);
        $this->assertEquals('Father Name', $consumer->father_name);
        $this->assertEquals('9998887776', $consumer->mobile);
        $this->assertEquals('New Mohalla Ward 2', $consumer->address);
        $this->assertEquals('MTR-NEW-01', $consumer->meter_no);
        $this->assertEquals('NDS1D', $consumer->tariff_category);
        $this->assertEquals(150, $consumer->custom_safe_ceiling);
        $this->assertEquals('hold_advance', $consumer->gap_recovery_mode);
    }

    public function test_operator_cannot_update_another_operators_consumer(): void
    {
        $operator1 = User::factory()->create();
        $operator2 = User::factory()->create();

        $consumer = ConsumerAccount::create([
            'user_id' => $operator2->id,
            'ca_number' => '88880002226',
            'consumer_name' => 'Protected Operator 2 Consumer',
        ]);

        $response = $this->actingAs($operator1)->putJson(route('consumers.update', ['ca' => $consumer->ca_number]), [
            'consumer_name' => 'Hacked Name',
        ]);

        $response->assertNotFound();
    }
}
