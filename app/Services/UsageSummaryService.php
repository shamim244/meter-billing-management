<?php

namespace App\Services;

use App\Models\BillRecord;
use App\Models\BillingBasisHistory;
use App\Models\BillingCycle;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;

class UsageSummaryService
{
    protected BillingBasisTrackingService $basisService;
    protected StatusTagReportService $statusTagService;
    protected QuotaUsageReportService $quotaService;

    public function __construct(
        BillingBasisTrackingService $basisService,
        StatusTagReportService $statusTagService,
        QuotaUsageReportService $quotaService
    ) {
        $this->basisService = $basisService;
        $this->statusTagService = $statusTagService;
        $this->quotaService = $quotaService;
    }

    /**
     * Assemble the Agent Monthly Usage Summary ("ROI Dashboard" object per PRD 2.1).
     */
    public function getMonthlySummary(int $userId, int $month, int $year): array
    {
        // 1. Bills processed this billing period
        $billsProcessed = BillRecord::where('user_id', $userId)
            ->where('billing_month', $month)
            ->where('billing_year', $year)
            ->where('parse_status', 'parsed')
            ->count();

        if ($billsProcessed === 0) {
            $billsProcessed = BillRecord::where('user_id', $userId)
                ->where('billing_month', $month)
                ->where('billing_year', $year)
                ->count();
        }

        // 2. MRUs active
        $mrusActive = Mru::where('user_id', $userId)
            ->where('status', 'active')
            ->count();

        // 3. Data coverage %
        $totalConsumers = ConsumerAccount::where('user_id', $userId)
            ->whereHas('mru', fn($q) => $q->where('status', 'active'))
            ->count();

        if ($totalConsumers === 0) {
            $totalConsumers = ConsumerAccount::where('user_id', $userId)->count();
        }

        $dataCoverage = $totalConsumers > 0 
            ? min(100.0, round(($billsProcessed / $totalConsumers) * 100, 1)) 
            : ($billsProcessed > 0 ? 100.0 : 0.0);

        // 4. Flagged consumers (consecutive estimate LK/MD alerts)
        $flaggedConsumersCount = $this->basisService->getFlaggedConsumerCount($userId, $month, $year);

        // 5. Historical depth (distinct months stored)
        $historicalDepthMonths = BillRecord::where('user_id', $userId)
            ->select('billing_year', 'billing_month')
            ->distinct()
            ->get()
            ->count();

        // 6. Underlying reports
        $quotaUsage = $this->quotaService->getMonthlyQuotaUsage($userId, $month, $year);
        $statusBreakdown = $this->statusTagService->getMonthlyStatusBreakdown($userId, $month, $year);
        $tagBreakdown = $this->statusTagService->getMonthlyTagBreakdown($userId, $month, $year);

        return [
            'month' => $month,
            'year' => $year,
            'period_label' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
            'roi_summary' => [
                'bills_processed' => $billsProcessed,
                'mrus_active' => $mrusActive,
                'total_consumers' => $totalConsumers,
                'data_coverage_percentage' => $dataCoverage,
                'flagged_consumers_count' => $flaggedConsumersCount,
                'historical_depth_months' => (int) $historicalDepthMonths,
            ],
            'quota_usage' => $quotaUsage,
            'status_breakdown' => $statusBreakdown,
            'tag_breakdown' => $tagBreakdown,
        ];
    }

    /**
     * Assemble Admin Aggregate Summary across all Agents (per PRD 2.3).
     */
    public function getAdminAggregateSummary(int $month, int $year, array $filters = []): array
    {
        $totalBillsPlatform = BillRecord::where('billing_month', $month)
            ->where('billing_year', $year)
            ->count();

        $totalActiveMrus = Mru::where('status', 'active')->count();

        $totalFlaggedPlatform = BillingBasisHistory::where('billing_month', $month)
            ->where('billing_year', $year)
            ->where('is_consecutive_alert', true)
            ->count();

        // Per-Agent breakdown
        $agents = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))->get();

        $agentRows = [];
        foreach ($agents as $agent) {
            $summary = $this->getMonthlySummary($agent->id, $month, $year);
            $agentRows[] = [
                'user_id' => $agent->id,
                'name' => $agent->name,
                'email' => $agent->email,
                'bills_processed' => $summary['roi_summary']['bills_processed'],
                'mrus_active' => $summary['roi_summary']['mrus_active'],
                'data_coverage' => $summary['roi_summary']['data_coverage_percentage'],
                'flagged_consumers' => $summary['roi_summary']['flagged_consumers_count'],
                'overage_spend' => $summary['quota_usage']['overage_charges']['total_charges'],
            ];
        }

        return [
            'month' => $month,
            'year' => $year,
            'totals' => [
                'total_bills_processed' => $totalBillsPlatform,
                'total_active_mrus' => $totalActiveMrus,
                'total_flagged_consumers' => $totalFlaggedPlatform,
                'total_agents' => count($agentRows),
            ],
            'agent_breakdown' => $agentRows,
        ];
    }
}
