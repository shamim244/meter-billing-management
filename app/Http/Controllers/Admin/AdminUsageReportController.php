<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingBasisHistory;
use App\Models\User;
use App\Services\QuotaUsageReportService;
use App\Services\StatusTagReportService;
use App\Services\UsageSummaryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUsageReportController extends Controller
{
    protected UsageSummaryService $summaryService;
    protected StatusTagReportService $statusTagService;
    protected QuotaUsageReportService $quotaService;

    public function __construct(
        UsageSummaryService $summaryService,
        StatusTagReportService $statusTagService,
        QuotaUsageReportService $quotaService
    ) {
        $this->summaryService = $summaryService;
        $this->statusTagService = $statusTagService;
        $this->quotaService = $quotaService;
    }

    /**
     * Admin Cross-Agent Usage ROI Overview Dashboard.
     */
    public function index(Request $request): View
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $summary = $this->summaryService->getAdminAggregateSummary($month, $year);

        return view('admin.reports.index', compact('summary', 'month', 'year'));
    }

    /**
     * Admin Status & Tag Distribution across all Agents.
     */
    public function statusTagReport(Request $request): View
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        $agentId = $request->filled('agent_id') ? (int) $request->get('agent_id') : null;

        $agents = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))
            ->orderBy('name')
            ->get();

        if ($agentId) {
            $statusBreakdown = $this->statusTagService->getMonthlyStatusBreakdown($agentId, $month, $year);
            $tagBreakdown = $this->statusTagService->getMonthlyTagBreakdown($agentId, $month, $year);
        } else {
            // Aggregate across all agents
            $statusBreakdown = ['submitted' => 0, 'critical' => 0, 'doubt' => 0, 'pending' => 0, 'total' => 0];
            $tagMap = [];
            $totalBills = 0;

            foreach ($agents as $agent) {
                $sb = $this->statusTagService->getMonthlyStatusBreakdown($agent->id, $month, $year);
                $statusBreakdown['submitted'] += $sb['submitted'];
                $statusBreakdown['critical'] += $sb['critical'];
                $statusBreakdown['doubt'] += $sb['doubt'];
                $statusBreakdown['pending'] += $sb['pending'];
                $statusBreakdown['total'] += $sb['total'];

                $tb = $this->statusTagService->getMonthlyTagBreakdown($agent->id, $month, $year);
                foreach ($tb['tags'] as $tagItem) {
                    $c = $tagItem['code'];
                    if (!isset($tagMap[$c])) {
                        $tagMap[$c] = $tagItem;
                        $tagMap[$c]['count'] = 0;
                    }
                    $tagMap[$c]['count'] += $tagItem['count'];
                    $totalBills += $tagItem['count'];
                }
            }

            foreach ($tagMap as &$t) {
                $t['percentage'] = $totalBills > 0 ? round(($t['count'] / $totalBills) * 100, 1) : 0.0;
            }

            $tagBreakdown = [
                'total_bills' => $totalBills,
                'tags' => array_values($tagMap),
            ];
        }

        return view('admin.reports.status-tag', compact(
            'statusBreakdown',
            'tagBreakdown',
            'agents',
            'agentId',
            'month',
            'year'
        ));
    }

    /**
     * Admin Cross-Agent Quota Usage Report (Sortable by overage_spend).
     */
    public function quotaUsageReport(Request $request): View
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        $sortBy = $request->get('sort_by', 'overage_spend');

        $aggregate = $this->quotaService->getAdminAggregateQuotaUsage($month, $year, $sortBy);

        return view('admin.reports.quota', compact('aggregate', 'month', 'year', 'sortBy'));
    }

    /**
     * Admin System-wide Flagged Consecutive Estimates List.
     */
    public function flaggedEstimates(Request $request): View
    {
        $month = $request->filled('month') ? (int) $request->get('month') : null;
        $year = $request->filled('year') ? (int) $request->get('year') : null;
        $agentId = $request->filled('agent_id') ? (int) $request->get('agent_id') : null;

        $query = BillingBasisHistory::with(['user', 'mru', 'consumerAccount'])
            ->where('is_consecutive_alert', true);

        if ($agentId) {
            $query->where('user_id', $agentId);
        }

        if ($month && $year) {
            $query->where('billing_month', $month)->where('billing_year', $year);
        }

        $flagged = $query->orderBy('consecutive_count', 'desc')->paginate(30);

        $agents = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))
            ->orderBy('name')
            ->get();

        return view('admin.reports.flagged', compact('flagged', 'agents', 'agentId', 'month', 'year'));
    }
}
