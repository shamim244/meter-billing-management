<?php

namespace App\Services;
namespace App\Http\Controllers;

use App\Models\Mru;
use App\Services\BillingBasisTrackingService;
use App\Services\QuotaUsageReportService;
use App\Services\StatusTagReportService;
use App\Services\UsageSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UsageReportController extends Controller
{
    protected UsageSummaryService $summaryService;
    protected StatusTagReportService $statusTagService;
    protected QuotaUsageReportService $quotaService;
    protected BillingBasisTrackingService $basisService;

    public function __construct(
        UsageSummaryService $summaryService,
        StatusTagReportService $statusTagService,
        QuotaUsageReportService $quotaService,
        BillingBasisTrackingService $basisService
    ) {
        $this->summaryService = $summaryService;
        $this->statusTagService = $statusTagService;
        $this->quotaService = $quotaService;
        $this->basisService = $basisService;
    }

    /**
     * Agent Usage ROI Overview Dashboard.
     */
    public function index(Request $request): View
    {
        $userId = Auth::id();
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $summary = $this->summaryService->getMonthlySummary($userId, $month, $year);
        $trend = $this->quotaService->getUsageTrend($userId, 6);
        $mrus = Mru::where('user_id', $userId)->orderBy('code')->get();

        return view('reports.index', compact('summary', 'trend', 'mrus', 'month', 'year'));
    }

    /**
     * Agent Monthly Status & Tag Breakdown Report with Drill-Down list.
     */
    public function statusTagReport(Request $request): View
    {
        $userId = Auth::id();
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        $mruId = $request->filled('mru_id') ? (int) $request->get('mru_id') : null;
        $status = $request->get('status', 'all');
        $tag = $request->get('tag', 'all');

        $statusBreakdown = $this->statusTagService->getMonthlyStatusBreakdown($userId, $month, $year, $mruId);
        $tagBreakdown = $this->statusTagService->getMonthlyTagBreakdown($userId, $month, $year, $mruId);

        $consumers = $this->statusTagService->getConsumersByFilter(
            userId: $userId,
            month: $month,
            year: $year,
            mruId: $mruId,
            status: $status,
            tag: $tag,
            perPage: 25,
            page: (int) $request->get('page', 1)
        );

        $mrus = Mru::where('user_id', $userId)->orderBy('code')->get();

        return view('reports.status-tag', compact(
            'statusBreakdown',
            'tagBreakdown',
            'consumers',
            'mrus',
            'month',
            'year',
            'mruId',
            'status',
            'tag'
        ));
    }

    /**
     * Agent Quota Usage & 6-Month Trend Report.
     */
    public function quotaReport(Request $request): View
    {
        $userId = Auth::id();
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $quotaUsage = $this->quotaService->getMonthlyQuotaUsage($userId, $month, $year);
        $trend = $this->quotaService->getUsageTrend($userId, 6);

        return view('reports.quota', compact('quotaUsage', 'trend', 'month', 'year'));
    }

    /**
     * Agent Flagged Consecutive Estimates Report.
     */
    public function flaggedEstimates(Request $request): View
    {
        $userId = Auth::id();
        $mruId = $request->filled('mru_id') ? (int) $request->get('mru_id') : null;
        $month = $request->filled('month') ? (int) $request->get('month') : null;
        $year = $request->filled('year') ? (int) $request->get('year') : null;

        $flagged = $this->basisService->getFlaggedConsumers($userId, $mruId, $month, $year);
        $mrus = Mru::where('user_id', $userId)->orderBy('code')->get();

        return view('reports.flagged', compact('flagged', 'mrus', 'mruId', 'month', 'year'));
    }

    /**
     * CSV Export for Status & Tag Report.
     */
    public function exportStatusTagCsv(Request $request): StreamedResponse
    {
        $userId = Auth::id();
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        return $this->statusTagService->exportCsv($userId, $month, $year, [
            'mru_id' => $request->get('mru_id'),
            'status' => $request->get('status'),
            'tag' => $request->get('tag'),
        ]);
    }
}
