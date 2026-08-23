<?php

namespace Tests\Feature;

use App\Models\AgentSubscription;
use App\Models\BillingBasisHistory;
use App\Models\BillingCycle;
use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\PlanOverageCharge;
use App\Models\User;
use App\Services\BillingBasisTrackingService;
use App\Services\BillTagService;
use App\Services\QuotaUsageReportService;
use App\Services\StatusTagReportService;
use App\Services\UsageSummaryService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageTrackingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;
    protected BillTagService $tagService;
    protected StatusTagReportService $statusTagReportService;
    protected BillingBasisTrackingService $basisService;
    protected QuotaUsageReportService $quotaService;
    protected UsageSummaryService $summaryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $this->admin = User::where('email', 'admin@nbpdcl-saas.com')->first();
        $this->agent = User::where('email', 'test@example.com')->first();

        $this->tagService = app(BillTagService::class);
        $this->statusTagReportService = app(StatusTagReportService::class);
        $this->basisService = app(BillingBasisTrackingService::class);
        $this->quotaService = app(QuotaUsageReportService::class);
        $this->summaryService = app(UsageSummaryService::class);
    }

    /**
     * 1. Tag breakdown report correctly reflects a NEWLY ADDED tag dynamically (proves it is not hardcoded).
     */
    public function test_tag_breakdown_report_dynamically_reflects_newly_added_tag(): void
    {
        // Add a brand new tag via Admin Tag Console
        $this->actingAs($this->admin)->post(route('admin.tags.store'), [
            'code' => 'SOLAR_NET',
            'label' => 'Solar Net Metering Consumer',
            'short_label' => 'Solar Net',
            'color' => 'cyan',
        ]);

        $mru = Mru::create([
            'user_id' => $this->agent->id,
            'code' => 'MRU_SOLAR_01',
            'name' => 'MRU Solar',
            'status' => 'active',
        ]);

        // Create a bill with the new tag
        BillRecord::create([
            'user_id' => $this->agent->id,
            'ca_number' => '2001001001',
            'mru_id' => $mru->id,
            'billing_month' => 8,
            'billing_year' => 2026,
            'consumer_name' => 'Solar User',
            'total_amount' => 500.00,
            'tag' => 'SOLAR_NET',
        ]);

        $report = $this->statusTagReportService->getMonthlyTagBreakdown($this->agent->id, 8, 2026);

        $codes = array_column($report['tags'], 'code');
        $this->assertContains('SOLAR_NET', $codes);

        $solarItem = collect($report['tags'])->firstWhere('code', 'SOLAR_NET');
        $this->assertNotNull($solarItem);
        $this->assertEquals('Solar Net Metering Consumer', $solarItem['label']);
        $this->assertEquals(1, $solarItem['count']);
        $this->assertEquals(100.0, $solarItem['percentage']);
    }

    /**
     * 2. Tag breakdown report still shows historical counts for a tag that was later DELETED by admin.
     */
    public function test_tag_breakdown_report_preserves_historical_deleted_tags(): void
    {
        // Add a custom tag
        $this->actingAs($this->admin)->post(route('admin.tags.store'), [
            'code' => 'OBSOLETE_TAG',
            'label' => 'Obsolete Tag To Delete',
            'short_label' => 'Obsolete',
            'color' => 'slate',
        ]);

        // Tag a bill record
        BillRecord::create([
            'user_id' => $this->agent->id,
            'ca_number' => '2001001002',
            'billing_month' => 5,
            'billing_year' => 2026,
            'consumer_name' => 'Historical Consumer',
            'total_amount' => 420.00,
            'tag' => 'OBSOLETE_TAG',
        ]);

        // Admin now deletes OBSOLETE_TAG from active config
        $this->actingAs($this->admin)->delete(route('admin.tags.destroy', 'OBSOLETE_TAG'));

        // Query historical report for Month 5, 2026
        $report = $this->statusTagReportService->getMonthlyTagBreakdown($this->agent->id, 5, 2026);

        $codes = array_column($report['tags'], 'code');
        $this->assertContains('OBSOLETE_TAG', $codes);

        $item = collect($report['tags'])->firstWhere('code', 'OBSOLETE_TAG');
        $this->assertEquals(1, $item['count']);
        $this->assertFalse($item['is_active']);
    }

    /**
     * 3. Consecutive-estimate detection correctly tracks LK/MD and resets to 0 on subsequent 'OK' cycle.
     */
    public function test_consecutive_estimate_detection_and_reset(): void
    {
        $caNumber = '999888777666';

        // Cycle 1: Month 5, 2026 — Basis = LK (Count = 1, No Alert)
        $h1 = $this->basisService->recordBillingBasis(
            userId: $this->agent->id,
            caNumber: $caNumber,
            mruId: null,
            month: 5,
            year: 2026,
            basis: 'LK'
        );
        $this->assertEquals(1, $h1->consecutive_count);
        $this->assertFalse($h1->is_consecutive_alert);

        // Cycle 2: Month 6, 2026 — Basis = MD (Count = 2, Alert = TRUE)
        $h2 = $this->basisService->recordBillingBasis(
            userId: $this->agent->id,
            caNumber: $caNumber,
            mruId: null,
            month: 6,
            year: 2026,
            basis: 'MD'
        );
        $this->assertEquals(2, $h2->consecutive_count);
        $this->assertTrue($h2->is_consecutive_alert);

        // Cycle 3: Month 7, 2026 — Basis = LK (Count = 3, Alert = TRUE)
        $h3 = $this->basisService->recordBillingBasis(
            userId: $this->agent->id,
            caNumber: $caNumber,
            mruId: null,
            month: 7,
            year: 2026,
            basis: 'LK'
        );
        $this->assertEquals(3, $h3->consecutive_count);
        $this->assertTrue($h3->is_consecutive_alert);

        // Cycle 4: Month 8, 2026 — Official Reading Taken, Basis = OK (Count Resets to 0, Alert = FALSE)
        $h4 = $this->basisService->recordBillingBasis(
            userId: $this->agent->id,
            caNumber: $caNumber,
            mruId: null,
            month: 8,
            year: 2026,
            basis: 'OK'
        );
        $this->assertEquals(0, $h4->consecutive_count);
        $this->assertFalse($h4->is_consecutive_alert);

        // Cycle 5: Month 9, 2026 — Basis = LK again (Count = 1, No Alert)
        $h5 = $this->basisService->recordBillingBasis(
            userId: $this->agent->id,
            caNumber: $caNumber,
            mruId: null,
            month: 9,
            year: 2026,
            basis: 'LK'
        );
        $this->assertEquals(1, $h5->consecutive_count);
        $this->assertFalse($h5->is_consecutive_alert);
    }

    /**
     * 4. Quota Usage Report numbers match Plan Management System records exactly.
     */
    public function test_quota_usage_report_matches_plan_management_system_records(): void
    {
        $plan = Plan::create([
            'name' => 'Usage Plan Pro',
            'included_mrus' => 2,
            'included_consumers' => 500,
            'extra_mru_rate' => 150.00,
            'extra_consumer_rate' => 0.50,
            'is_active' => true,
        ]);

        $sub = AgentSubscription::create([
            'user_id' => $this->agent->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'duration_months' => 1,
            'billing_start' => now()->startOfMonth(),
            'billing_end' => now()->endOfMonth(),
            'base_price_paid' => 1000.00,
            'included_mrus_locked' => 2,
            'included_consumers_locked' => 500,
            'extra_mru_rate_locked' => 150.00,
            'extra_consumer_rate_locked' => 0.50,
        ]);

        // 3 active MRUs (1 extra)
        $mru1 = Mru::create(['user_id' => $this->agent->id, 'code' => 'M1', 'name' => 'MRU 1', 'status' => 'active']);
        $mru2 = Mru::create(['user_id' => $this->agent->id, 'code' => 'M2', 'name' => 'MRU 2', 'status' => 'active']);
        $mru3 = Mru::create(['user_id' => $this->agent->id, 'code' => 'M3', 'name' => 'MRU 3', 'status' => 'active']);

        // Billing Cycle with 700 consumers (200 extra)
        BillingCycle::create([
            'user_id' => $this->agent->id,
            'mru_id' => $mru1->id,
            'cycle_month' => 8,
            'cycle_year' => 2026,
            'consumer_count_at_creation' => 700,
            'included_quota_used' => 500,
            'extra_consumer_count' => 200,
            'extra_consumer_charge' => 100.00,
            'status' => 'completed',
        ]);

        // Overage charges recorded in plan_overage_charges
        PlanOverageCharge::create([
            'user_id' => $this->agent->id,
            'charge_type' => 'mru',
            'reference_type' => 'mru',
            'reference_id' => $mru3->id,
            'amount' => 150.00,
            'created_at' => now()->setDate(2026, 8, 15),
        ]);

        PlanOverageCharge::create([
            'user_id' => $this->agent->id,
            'charge_type' => 'consumer',
            'reference_type' => 'billing_cycle',
            'reference_id' => 1,
            'amount' => 100.00,
            'created_at' => now()->setDate(2026, 8, 15),
        ]);

        $usage = $this->quotaService->getMonthlyQuotaUsage($this->agent->id, 8, 2026);

        $this->assertEquals(2, $usage['mru']['included']);
        $this->assertEquals(3, $usage['mru']['used']);
        $this->assertEquals(1, $usage['mru']['extra']);
        $this->assertTrue($usage['mru']['is_over_quota']);

        $this->assertEquals(500, $usage['consumer']['included']);
        $this->assertEquals(700, $usage['consumer']['used']);
        $this->assertEquals(200, $usage['consumer']['extra']);
        $this->assertTrue($usage['consumer']['is_over_quota']);

        $this->assertEquals(150.00, $usage['overage_charges']['mru_charges']);
        $this->assertEquals(100.00, $usage['overage_charges']['consumer_charges']);
        $this->assertEquals(250.00, $usage['overage_charges']['total_charges']);
    }

    /**
     * 5. Zero WalletService Calls Check: Strictly read-only reporting module.
     */
    public function test_usage_tracking_system_makes_zero_wallet_calls(): void
    {
        $walletMock = $this->mock(WalletService::class);
        $walletMock->shouldNotReceive('debit');
        $walletMock->shouldNotReceive('credit');
        $walletMock->shouldNotReceive('adminAdjust');

        // Execute all tracking & report operations
        $this->basisService->recordBillingBasis(
            userId: $this->agent->id,
            caNumber: '555444333',
            mruId: null,
            month: 8,
            year: 2026,
            basis: 'LK'
        );

        $this->summaryService->getMonthlySummary($this->agent->id, 8, 2026);
        $this->statusTagReportService->getMonthlyStatusBreakdown($this->agent->id, 8, 2026);
        $this->statusTagReportService->getMonthlyTagBreakdown($this->agent->id, 8, 2026);
        $this->quotaService->getMonthlyQuotaUsage($this->agent->id, 8, 2026);
        $this->quotaService->getUsageTrend($this->agent->id, 6);
        $this->summaryService->getAdminAggregateSummary(8, 2026);

        // Also test HTTP controllers
        $this->actingAs($this->agent)->get(route('reports.usage'));
        $this->actingAs($this->agent)->get(route('reports.status_tag'));
        $this->actingAs($this->agent)->get(route('reports.quota'));
        $this->actingAs($this->agent)->get(route('reports.flagged'));
        $this->actingAs($this->admin)->get(route('admin.reports.index'));
        $this->actingAs($this->admin)->get(route('admin.reports.status_tag'));
        $this->actingAs($this->admin)->get(route('admin.reports.quota'));
        $this->actingAs($this->admin)->get(route('admin.reports.flagged'));

        $this->assertTrue(true);
    }

    /**
     * 6. Status and Tag CSV Export test.
     */
    public function test_status_tag_csv_export(): void
    {
        BillRecord::create([
            'user_id' => $this->agent->id,
            'ca_number' => '1002003004',
            'consumer_name' => 'CSV Export User',
            'billing_month' => 8,
            'billing_year' => 2026,
            'review_status' => 'submitted',
            'tag' => 'OK',
            'total_amount' => 650.00,
            'billing_basis' => 'OK',
        ]);

        $response = $this->actingAs($this->agent)->get(route('reports.status_tag.export_csv', [
            'month' => 8,
            'year' => 2026,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    /**
     * 7. Admin Quota Usage Aggregate sorting by overage_spend.
     */
    public function test_admin_quota_usage_aggregate_sorting(): void
    {
        $agent2 = User::factory()->create([
            'name' => 'High Spending Agent',
            'email' => 'highspend@example.com',
        ]);
        $agent2->assignRole('user');

        // Give agent2 an overage charge
        PlanOverageCharge::create([
            'user_id' => $agent2->id,
            'charge_type' => 'mru',
            'reference_type' => 'mru',
            'reference_id' => 99,
            'amount' => 950.00,
            'created_at' => now()->setDate(2026, 8, 10),
        ]);

        $aggregate = $this->quotaService->getAdminAggregateQuotaUsage(8, 2026, 'overage_spend');

        $this->assertNotEmpty($aggregate['rows']);
        // Top row should have highest overage spend
        $this->assertEquals($agent2->id, $aggregate['rows'][0]['user_id']);
        $this->assertEquals(950.00, $aggregate['rows'][0]['overage_spend']);
    }
}
