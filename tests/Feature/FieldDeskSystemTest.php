<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\FieldDeskAction;
use App\Models\FieldDeskCategory;
use App\Models\Mru;
use App\Models\User;
use App\Services\FieldDeskService;
use Carbon\Carbon;
use Database\Seeders\FieldDeskCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldDeskSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $agent;

    protected User $otherAgent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(FieldDeskCategorySeeder::class);

        $this->agent = User::where('email', 'test@example.com')->first();
        if (! $this->agent) {
            $this->agent = User::factory()->create(['status' => 'active']);
        }

        $this->otherAgent = User::factory()->create([
            'email' => 'other-agent@example.com',
            'status' => 'active',
        ]);
    }

    public function test_field_desk_categories_are_seeded_and_can_be_retrieved(): void
    {
        $this->assertDatabaseHas('field_desk_categories', ['code' => 'payment_promise']);
        $this->assertDatabaseHas('field_desk_categories', ['code' => 'issue_correction']);
        $this->assertDatabaseHas('field_desk_categories', ['code' => 'scheduled_visit']);
        $this->assertDatabaseHas('field_desk_categories', ['code' => 'general_note']);

        $response = $this->actingAs($this->agent)->getJson(route('api.field-desk.categories'));

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(4, 'categories');
    }

    public function test_user_can_access_field_desk_workspace_tier1(): void
    {
        $response = $this->actingAs($this->agent)->get(route('field-desk.index'));

        $response->assertStatus(200)
            ->assertViewIs('field-desk.index')
            ->assertViewHas('categories')
            ->assertViewHas('counts');
    }

    public function test_user_can_create_action_with_consumer_auto_linking_and_activity_logging(): void
    {
        $mru = Mru::create([
            'user_id' => $this->agent->id,
            'code' => 'MRU_FD_01',
            'name' => 'Basalgaon Ward 4',
            'status' => 'active',
        ]);

        $consumer = ConsumerAccount::create([
            'user_id' => $this->agent->id,
            'ca_number' => '10230041576',
            'mru_id' => $mru->id,
            'consumer_name' => 'Ram Kumar',
            'mobile' => '9876543210',
            'baseline_amount' => 3450.00,
        ]);

        $category = FieldDeskCategory::where('code', 'payment_promise')->first();

        $targetDate = Carbon::today()->addDays(2)->toDateString();

        $response = $this->actingAs($this->agent)->postJson(route('api.field-desk.store'), [
            'ca_number' => '10230041576',
            'category_id' => $category->id,
            'target_date' => $targetDate,
            'priority' => 'high',
            'target_amount' => 1200.00,
            'payment_mode' => 'upi_phonepe',
            'private_note' => 'PhonePe: 9876543210 • Salary on 10th',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('field_desk_actions', [
            'user_id' => $this->agent->id,
            'ca_number' => '10230041576',
            'consumer_account_id' => $consumer->id,
            'category_id' => $category->id,
            'priority' => 'high',
            'target_amount' => 1200.00,
            'payment_mode' => 'upi_phonepe',
            'status' => 'open',
            'reschedule_count' => 0,
        ]);

        $action = FieldDeskAction::where('ca_number', '10230041576')->first();

        $this->assertDatabaseHas('field_desk_activities', [
            'action_id' => $action->id,
            'user_id' => $this->agent->id,
            'action_type' => 'created',
        ]);
    }

    public function test_agenda_filtering_by_timeline_today_overdue_upcoming_and_resolved(): void
    {
        $category = FieldDeskCategory::where('code', 'payment_promise')->first();

        // 1. Due today action
        $dueToday = FieldDeskAction::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_TODAY_001',
            'category_id' => $category->id,
            'target_date' => Carbon::today()->toDateString(),
            'original_target_date' => Carbon::today()->toDateString(),
            'status' => 'open',
        ]);

        // 2. Overdue action (3 days ago)
        $overdue = FieldDeskAction::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_OVERDUE_002',
            'category_id' => $category->id,
            'target_date' => Carbon::today()->subDays(3)->toDateString(),
            'original_target_date' => Carbon::today()->subDays(3)->toDateString(),
            'status' => 'open',
        ]);

        // 3. Upcoming action (4 days in future)
        $upcoming = FieldDeskAction::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_UPCOMING_003',
            'category_id' => $category->id,
            'target_date' => Carbon::today()->addDays(4)->toDateString(),
            'original_target_date' => Carbon::today()->addDays(4)->toDateString(),
            'status' => 'open',
        ]);

        // 4. Resolved action
        $resolved = FieldDeskAction::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_RESOLVED_004',
            'category_id' => $category->id,
            'target_date' => Carbon::today()->toDateString(),
            'original_target_date' => Carbon::today()->toDateString(),
            'status' => 'completed',
            'resolved_at' => Carbon::now(),
            'collected_amount' => 500.00,
        ]);

        // Filter: today
        $resToday = $this->actingAs($this->agent)->getJson(route('api.field-desk.data', ['timeline' => 'today']));
        $resToday->assertStatus(200)
            ->assertJsonPath('counts.due_today', 1)
            ->assertJsonPath('counts.overdue', 1)
            ->assertJsonPath('counts.upcoming', 1)
            ->assertJsonPath('counts.resolved_this_month', 1);

        $todayCas = collect($resToday->json('data'))->pluck('ca_number')->all();
        $this->assertContains('CA_TODAY_001', $todayCas);
        $this->assertNotContains('CA_OVERDUE_002', $todayCas);
        $this->assertNotContains('CA_UPCOMING_003', $todayCas);

        // Filter: overdue
        $resOverdue = $this->actingAs($this->agent)->getJson(route('api.field-desk.data', ['timeline' => 'overdue']));
        $overdueCas = collect($resOverdue->json('data'))->pluck('ca_number')->all();
        $this->assertContains('CA_OVERDUE_002', $overdueCas);
        $this->assertNotContains('CA_TODAY_001', $overdueCas);

        // Filter: upcoming
        $resUpcoming = $this->actingAs($this->agent)->getJson(route('api.field-desk.data', ['timeline' => 'upcoming']));
        $upcomingCas = collect($resUpcoming->json('data'))->pluck('ca_number')->all();
        $this->assertContains('CA_UPCOMING_003', $upcomingCas);
        $this->assertNotContains('CA_TODAY_001', $upcomingCas);

        // Filter: resolved
        $resResolved = $this->actingAs($this->agent)->getJson(route('api.field-desk.data', ['timeline' => 'resolved']));
        $resolvedCas = collect($resResolved->json('data'))->pluck('ca_number')->all();
        $this->assertContains('CA_RESOLVED_004', $resolvedCas);
    }

    public function test_multi_tenant_isolation_prevents_cross_user_access(): void
    {
        $category = FieldDeskCategory::where('code', 'payment_promise')->first();

        // Created by Agent A
        $action = FieldDeskAction::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_ISOLATION_999',
            'category_id' => $category->id,
            'target_date' => Carbon::today()->addDays(2)->toDateString(),
            'original_target_date' => Carbon::today()->addDays(2)->toDateString(),
            'status' => 'open',
            'private_note' => 'Confidential dossier note',
        ]);

        // Agent B attempts to view show()
        $this->actingAs($this->otherAgent)
            ->getJson(route('api.field-desk.show', $action->id))
            ->assertStatus(404);

        // Agent B attempts to update()
        $this->actingAs($this->otherAgent)
            ->putJson(route('api.field-desk.update', $action->id), [
                'private_note' => 'Hacked note',
            ])
            ->assertStatus(404);

        // Agent B attempts to destroy()
        $this->actingAs($this->otherAgent)
            ->deleteJson(route('api.field-desk.destroy', $action->id))
            ->assertStatus(404);

        // Agent B requests agenda feed -> action should not appear
        $res = $this->actingAs($this->otherAgent)->getJson(route('api.field-desk.data'));
        $cas = collect($res->json('data'))->pluck('ca_number')->all();
        $this->assertNotContains('CA_ISOLATION_999', $cas);
    }

    public function test_quick_reschedule_pushes_target_date_and_logs_activity(): void
    {
        $category = FieldDeskCategory::where('code', 'payment_promise')->first();

        $action = FieldDeskAction::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_SNOOZE_123',
            'category_id' => $category->id,
            'target_date' => Carbon::today()->toDateString(),
            'original_target_date' => Carbon::today()->toDateString(),
            'status' => 'open',
            'reschedule_count' => 0,
        ]);

        $response = $this->actingAs($this->agent)
            ->postJson(route('api.field-desk.reschedule', $action->id), [
                'days' => 2,
                'reason' => 'Requested weekend callback',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $expectedDate = Carbon::today()->addDays(2)->toDateString();

        $refreshed = $action->fresh();
        $this->assertEquals($expectedDate, $refreshed->target_date->toDateString());
        $this->assertEquals('rescheduled', $refreshed->status);
        $this->assertEquals(1, $refreshed->reschedule_count);

        $this->assertDatabaseHas('field_desk_activities', [
            'action_id' => $action->id,
            'action_type' => 'rescheduled',
            'note' => 'Requested weekend callback',
        ]);
    }

    public function test_complete_action_records_collected_amount_and_resolution_note(): void
    {
        $category = FieldDeskCategory::where('code', 'payment_promise')->first();

        $action = FieldDeskAction::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_COMPLETE_456',
            'category_id' => $category->id,
            'target_date' => Carbon::today()->toDateString(),
            'original_target_date' => Carbon::today()->toDateString(),
            'target_amount' => 1500.00,
            'collected_amount' => 0.00,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->agent)
            ->postJson(route('api.field-desk.complete', $action->id), [
                'collected_amount' => 1500.00,
                'note' => 'Paid in full via PhonePe',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('field_desk_actions', [
            'id' => $action->id,
            'status' => 'completed',
            'collected_amount' => 1500.00,
            'resolution_note' => 'Paid in full via PhonePe',
        ]);

        $this->assertDatabaseHas('field_desk_activities', [
            'action_id' => $action->id,
            'action_type' => 'completed',
            'amount_recorded' => 1500.00,
        ]);
    }

    public function test_main_dashboard_bridge_for_consumer_endpoint(): void
    {
        $category = FieldDeskCategory::where('code', 'issue_correction')->first();

        $consumer = ConsumerAccount::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_BRIDGE_789',
            'consumer_name' => 'Sita Devi',
            'mobile' => '9123456780',
            'baseline_amount' => 850.00,
        ]);

        $action = FieldDeskAction::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_BRIDGE_789',
            'consumer_account_id' => $consumer->id,
            'category_id' => $category->id,
            'target_date' => Carbon::today()->addDays(3)->toDateString(),
            'original_target_date' => Carbon::today()->addDays(3)->toDateString(),
            'status' => 'open',
            'private_note' => 'Meter display blank. Replacement promised.',
        ]);

        $response = $this->actingAs($this->agent)
            ->getJson(route('api.field-desk.consumer', 'CA_BRIDGE_789'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'ca_number' => 'CA_BRIDGE_789',
                'consumer' => [
                    'name' => 'Sita Devi',
                    'mobile' => '9123456780',
                ],
                'action' => [
                    'id' => $action->id,
                    'category_code' => 'issue_correction',
                    'category_name' => 'Technical / Grievance',
                    'private_note' => 'Meter display blank. Replacement promised.',
                ],
            ]);
    }

    public function test_main_dashboard_get_data_attaches_field_desk_action_summary(): void
    {
        $category = FieldDeskCategory::where('code', 'payment_promise')->first();

        $mru = Mru::create([
            'user_id' => $this->agent->id,
            'code' => 'MRU_DASH_TEST',
            'name' => 'Dash Village',
            'status' => 'active',
        ]);

        $consumer = ConsumerAccount::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_DASH_111',
            'mru_id' => $mru->id,
            'consumer_name' => 'Dash Consumer',
        ]);

        $bill = BillRecord::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_DASH_111',
            'mru_id' => $mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
            'consumer_name' => 'Dash Consumer',
            'total_amount' => 2400.00,
            'units_consumed' => 50,
            'download_status' => 'downloaded',
            'parse_status' => 'parsed',
        ]);

        FieldDeskAction::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_DASH_111',
            'consumer_account_id' => $consumer->id,
            'category_id' => $category->id,
            'target_date' => Carbon::today()->toDateString(),
            'original_target_date' => Carbon::today()->toDateString(),
            'target_amount' => 1000.00,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->agent)->getJson('/dashboard/data?month=8&year=2026');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $matchedItem = collect($data)->firstWhere('ca_number', 'CA_DASH_111');
        $this->assertNotNull($matchedItem);
        $this->assertNotNull($matchedItem['field_desk_action']);
        $this->assertEquals('payment_promise', $matchedItem['field_desk_action']['category_code']);
        $this->assertTrue($matchedItem['field_desk_action']['is_due_today']);
        $this->assertEquals(1000.00, $matchedItem['field_desk_action']['target_amount']);
    }

    public function test_whatsapp_link_generation_with_localized_templates(): void
    {
        $service = app(FieldDeskService::class);
        $payCat = FieldDeskCategory::where('code', 'payment_promise')->first();
        $issueCat = FieldDeskCategory::where('code', 'issue_correction')->first();
        $visitCat = FieldDeskCategory::where('code', 'scheduled_visit')->first();

        $consumer = ConsumerAccount::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_WA_999',
            'consumer_name' => 'Mahesh Yadav',
            'mobile' => '+91 98765-43210',
        ]);

        // 1. Payment Promise
        $actionPay = FieldDeskAction::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_WA_999',
            'consumer_account_id' => $consumer->id,
            'category_id' => $payCat->id,
            'target_date' => Carbon::today()->toDateString(),
            'original_target_date' => Carbon::today()->toDateString(),
            'target_amount' => 1850.00,
            'status' => 'open',
        ]);

        $waLink = $service->generateWhatsAppLink($actionPay);
        $this->assertNotNull($waLink);
        $this->assertStringContainsString('https://wa.me/919876543210?text=', $waLink);
        $this->assertStringContainsString('Mahesh Yadav', urldecode($waLink));
        $this->assertStringContainsString('CA: CA_WA_999', urldecode($waLink));
        $this->assertStringContainsString('1,850.00', urldecode($waLink));

        // 2. Issue Correction
        $actionIssue = FieldDeskAction::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_WA_999',
            'consumer_account_id' => $consumer->id,
            'category_id' => $issueCat->id,
            'target_date' => Carbon::today()->addDays(3)->toDateString(),
            'original_target_date' => Carbon::today()->addDays(3)->toDateString(),
            'status' => 'open',
        ]);
        $textIssue = $service->generateWhatsAppText($actionIssue);
        $this->assertStringContainsString('issue ke sambandh me', $textIssue);

        // 3. Scheduled Visit
        $actionVisit = FieldDeskAction::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_WA_999',
            'consumer_account_id' => $consumer->id,
            'category_id' => $visitCat->id,
            'target_date' => Carbon::today()->addDays(1)->toDateString(),
            'original_target_date' => Carbon::today()->addDays(1)->toDateString(),
            'status' => 'open',
        ]);
        $textVisit = $service->generateWhatsAppText($actionVisit);
        $this->assertStringContainsString('inspection ke liye hum', $textVisit);
    }

    public function test_action_model_lifecycle_auto_sets_and_clears_resolved_at(): void
    {
        $category = FieldDeskCategory::where('code', 'payment_promise')->first();

        $action = FieldDeskAction::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_LIFECYCLE_101',
            'category_id' => $category->id,
            'target_date' => Carbon::today()->toDateString(),
            'original_target_date' => Carbon::today()->toDateString(),
            'status' => 'open',
        ]);

        $this->assertNull($action->resolved_at);

        // Transition to completed -> resolved_at auto-set
        $action->status = 'completed';
        $action->save();

        $action->refresh();
        $this->assertNotNull($action->resolved_at);

        // Transition back to open -> resolved_at cleared
        $action->status = 'open';
        $action->save();

        $action->refresh();
        $this->assertNull($action->resolved_at);
    }

    public function test_update_action_target_date_increments_reschedule_count_and_status_transition(): void
    {
        $category = FieldDeskCategory::where('code', 'payment_promise')->first();

        $action = FieldDeskAction::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_UPDATE_202',
            'category_id' => $category->id,
            'target_date' => Carbon::today()->toDateString(),
            'original_target_date' => Carbon::today()->toDateString(),
            'status' => 'open',
            'reschedule_count' => 0,
        ]);

        $newDate = Carbon::today()->addDays(5)->toDateString();

        $response = $this->actingAs($this->agent)->putJson(route('api.field-desk.update', $action->id), [
            'target_date' => $newDate,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $action->refresh();
        $this->assertEquals(1, $action->reschedule_count);
        $this->assertEquals('rescheduled', $action->status);
        $this->assertEquals($newDate, $action->target_date->toDateString());

        $this->assertDatabaseHas('field_desk_activities', [
            'action_id' => $action->id,
            'action_type' => 'rescheduled',
        ]);
    }

    public function test_mobile_sanitizer_rejects_invalid_prefixes_and_sanitizes_complex_formats(): void
    {
        $service = app(FieldDeskService::class);

        // Valid mobile formats
        $this->assertEquals('9876543210', $service->sanitizeMobile('9876543210'));
        $this->assertEquals('9876543210', $service->sanitizeMobile('+91 98765-43210'));
        $this->assertEquals('9876543210', $service->sanitizeMobile('09876543210'));
        $this->assertEquals('9876543210', $service->sanitizeMobile('919876543210'));
        $this->assertEquals('7876543210', $service->sanitizeMobile('7876543210'));
        $this->assertEquals('6876543210', $service->sanitizeMobile('6876543210'));

        // Invalid: 10-digit non-mobile (e.g. CA number starting with 1, 2, 3, 4, 5)
        $this->assertNull($service->sanitizeMobile('1023004157'));
        $this->assertNull($service->sanitizeMobile('2023004157'));
        $this->assertNull($service->sanitizeMobile('0000000000'));
        $this->assertNull($service->sanitizeMobile(''));
        $this->assertNull($service->sanitizeMobile(null));
        $this->assertNull($service->sanitizeMobile('12345'));
    }

    public function test_deterministic_quick_reschedule_from_today_when_action_is_overdue(): void
    {
        $category = FieldDeskCategory::where('code', 'payment_promise')->first();

        // Overdue by 5 days
        $action = FieldDeskAction::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_OVERDUE_SNOOZE',
            'category_id' => $category->id,
            'target_date' => Carbon::today()->subDays(5)->toDateString(),
            'original_target_date' => Carbon::today()->subDays(5)->toDateString(),
            'status' => 'open',
            'reschedule_count' => 0,
        ]);

        $service = app(FieldDeskService::class);
        $updated = $service->quickReschedule($action, 2, 'Snoozed overdue', $this->agent->id);

        // When overdue, base date is today, so target becomes Today + 2 days
        $this->assertEquals(Carbon::today()->addDays(2)->toDateString(), $updated->target_date->toDateString());
        $this->assertEquals(1, $updated->reschedule_count);
        $this->assertEquals('rescheduled', $updated->status);
    }

    public function test_auto_creates_consumer_account_from_bill_record_if_not_present_on_store(): void
    {
        $mru = Mru::create([
            'user_id' => $this->agent->id,
            'code' => 'MRU_AUTO_LINK',
            'name' => 'Auto Link Village',
            'status' => 'active',
        ]);

        $bill = BillRecord::create([
            'user_id' => $this->agent->id,
            'ca_number' => '10239999999',
            'mru_id' => $mru->id,
            'billing_month' => 9,
            'billing_year' => 2026,
            'consumer_name' => 'Auto Linked Consumer',
            'meter_no' => 'MTR-8888',
            'total_amount' => 1750.00,
            'tariff_category' => 'DS-II',
            'billing_basis' => 'OK',
            'download_status' => 'downloaded',
            'parse_status' => 'parsed',
        ]);

        $category = FieldDeskCategory::where('code', 'payment_promise')->first();

        $response = $this->actingAs($this->agent)->postJson(route('api.field-desk.store'), [
            'ca_number' => '10239999999',
            'category_id' => $category->id,
            'target_date' => Carbon::today()->addDays(3)->toDateString(),
            'target_amount' => 1750.00,
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        // Verify consumer_accounts row was created with bill details
        $this->assertDatabaseHas('consumer_accounts', [
            'user_id' => $this->agent->id,
            'ca_number' => '10239999999',
            'mru_id' => $mru->id,
            'consumer_name' => 'Auto Linked Consumer',
            'meter_no' => 'MTR-8888',
        ]);

        // Verify action linked the newly created consumer_account_id
        $action = FieldDeskAction::where('ca_number', '10239999999')->first();
        $this->assertNotNull($action->consumer_account_id);
        $this->assertEquals($mru->id, $action->mru_id);
    }

    public function test_for_consumer_falls_back_to_bill_record_details_when_consumer_account_missing(): void
    {
        $mru = Mru::create([
            'user_id' => $this->agent->id,
            'code' => 'MRU_FALLBACK',
            'name' => 'Fallback Village',
            'status' => 'active',
        ]);

        BillRecord::create([
            'user_id' => $this->agent->id,
            'ca_number' => '10237777777',
            'mru_id' => $mru->id,
            'billing_month' => 9,
            'billing_year' => 2026,
            'consumer_name' => 'Fallback Bill Consumer',
            'meter_no' => 'MTR-FALLBACK',
            'total_amount' => 3200.00,
            'tariff_category' => 'DS-II',
            'billing_basis' => 'OK',
            'download_status' => 'downloaded',
            'parse_status' => 'parsed',
        ]);

        $response = $this->actingAs($this->agent)
            ->getJson(route('api.field-desk.consumer', '10237777777'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'ca_number' => '10237777777',
                'consumer' => [
                    'name' => 'Fallback Bill Consumer',
                    'meter_no' => 'MTR-FALLBACK',
                    'mru_code' => 'MRU_FALLBACK',
                ],
            ]);
    }

    public function test_whatsapp_link_with_partial_amount_reflects_remaining_balance(): void
    {
        $service = app(FieldDeskService::class);
        $payCat = FieldDeskCategory::where('code', 'payment_promise')->first();

        $consumer = ConsumerAccount::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_PARTIAL_WA',
            'consumer_name' => 'Partial Consumer',
            'mobile' => '9876543210',
        ]);

        $action = FieldDeskAction::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_PARTIAL_WA',
            'consumer_account_id' => $consumer->id,
            'category_id' => $payCat->id,
            'target_date' => Carbon::today()->toDateString(),
            'original_target_date' => Carbon::today()->toDateString(),
            'target_amount' => 1500.00,
            'collected_amount' => 500.00,
            'status' => 'open',
        ]);

        $text = $service->generateWhatsAppText($action);
        // Reminds about remaining balance 1,000.00
        $this->assertStringContainsString('1,000.00', $text);
    }

    public function test_user_can_create_action_with_gps_coordinates_and_sync_to_consumer_account(): void
    {
        $consumer = ConsumerAccount::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_GPS_TEST_101',
            'consumer_name' => 'Geocoded Consumer',
            'mobile' => '9876500001',
        ]);

        $category = FieldDeskCategory::where('code', 'payment_promise')->first();

        $response = $this->actingAs($this->agent)->postJson(route('api.field-desk.store'), [
            'ca_number' => 'CA_GPS_TEST_101',
            'category_id' => $category->id,
            'target_date' => Carbon::today()->toDateString(),
            'priority' => 'high',
            'latitude' => 26.12345678,
            'longitude' => 85.98765432,
            'location_accuracy' => 4.5,
            'save_to_consumer' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        // Verify action stored coordinates
        $this->assertDatabaseHas('field_desk_actions', [
            'ca_number' => 'CA_GPS_TEST_101',
            'latitude' => 26.12345678,
            'longitude' => 85.98765432,
            'location_accuracy' => 4.5,
        ]);

        // Verify consumer account synced coordinates
        $consumer->refresh();
        $this->assertEquals('26.12345678', (string) $consumer->latitude);
        $this->assertEquals('85.98765432', (string) $consumer->longitude);
        $this->assertEquals(4.5, (float) $consumer->location_accuracy);
        $this->assertNotNull($consumer->location_updated_at);
        $this->assertStringContainsString('https://www.google.com/maps?q=26.12345678,85.98765432', $consumer->map_link);
    }

    public function test_user_can_update_consumer_contact_and_gps_via_dedicated_endpoint(): void
    {
        $consumer = ConsumerAccount::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_CONTACT_202',
            'consumer_name' => 'Contact Update Target',
        ]);

        $response = $this->actingAs($this->agent)->postJson('/api/field-desk/consumer/CA_CONTACT_202/contact', [
            'mobile' => '9123456789',
            'latitude' => 25.61234567,
            'longitude' => 85.12345678,
            'location_accuracy' => 6.2,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'consumer' => [
                    'mobile' => '9123456789',
                    'latitude' => 25.61234567,
                    'longitude' => 85.12345678,
                    'location_accuracy' => 6.2,
                ],
            ]);

        $consumer->refresh();
        $this->assertEquals('9123456789', $consumer->mobile);
        $this->assertEquals('25.61234567', (string) $consumer->latitude);
        $this->assertEquals('85.12345678', (string) $consumer->longitude);
        $this->assertEquals(6.2, (float) $consumer->location_accuracy);
        $this->assertNotNull($consumer->location_updated_at);
    }

    public function test_api_field_desk_consumer_endpoint_returns_gps_and_contact(): void
    {
        ConsumerAccount::create([
            'user_id' => $this->agent->id,
            'ca_number' => 'CA_ENDPOINT_303',
            'consumer_name' => 'Endpoint Verification',
            'mobile' => '9988776655',
            'latitude' => 26.55555555,
            'longitude' => 85.44444444,
            'location_accuracy' => 8.0,
            'location_updated_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->agent)->getJson(route('api.field-desk.consumer', 'CA_ENDPOINT_303'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'ca_number' => 'CA_ENDPOINT_303',
                'consumer' => [
                    'mobile' => '9988776655',
                    'latitude' => 26.55555555,
                    'longitude' => 85.44444444,
                    'location_accuracy' => 8.0,
                ],
            ]);
    }
}
