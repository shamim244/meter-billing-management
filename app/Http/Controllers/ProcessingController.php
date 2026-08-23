<?php

namespace App\Http\Controllers;

use App\Models\BillingCycle;
use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Services\BillDownloadService;
use App\Services\BillParseService;
use App\Services\Plan\ConsumerQuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class ProcessingController extends Controller
{
    protected BillDownloadService $downloadService;
    protected BillParseService $parseService;
    protected ConsumerQuotaService $consumerQuotaService;

    public function __construct(
        BillDownloadService $downloadService,
        BillParseService $parseService,
        ConsumerQuotaService $consumerQuotaService
    ) {
        $this->downloadService = $downloadService;
        $this->parseService = $parseService;
        $this->consumerQuotaService = $consumerQuotaService;
    }

    /**
     * Display the dedicated Data Processing Hub page.
     */
    public function index(Request $request): View
    {
        $userId = Auth::id();
        $mrus = Mru::withCount('consumerAccounts')->orderBy('code')->get();

        $selectedMruId = $request->get('mru_id', $mrus->first()?->id ?? null);
        $selectedMonth = (int) $request->get('month', now()->month);
        $selectedYear = (int) $request->get('year', now()->year);

        // Build dynamic MRU periods map
        $allBills = BillRecord::select('mru_id', 'billing_month', 'billing_year')
            ->distinct()
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get();

        $mruPeriodsMap = [];
        foreach ($mrus as $m) {
            $periodsForMru = $allBills->where('mru_id', $m->id)->unique(function ($p) {
                return "{$p->billing_month}_{$p->billing_year}";
            })->map(function ($p) {
                return [
                    'key' => "{$p->billing_month}_{$p->billing_year}",
                    'month' => (int) $p->billing_month,
                    'year' => (int) $p->billing_year,
                    'label' => date('M, Y', mktime(0, 0, 0, (int)$p->billing_month, 1, (int)$p->billing_year)),
                ];
            })->values()->toArray();

            $mruPeriodsMap[(string)$m->id] = $periodsForMru;
        }

        // Auto-select latest existing cycle for selected MRU if not specified in request
        if (!$request->has('month') && !empty($mruPeriodsMap[(string)$selectedMruId])) {
            $latestPeriod = $mruPeriodsMap[(string)$selectedMruId][0];
            $selectedMonth = $latestPeriod['month'];
            $selectedYear = $latestPeriod['year'];
        }

        return view('processing.index', compact(
            'mrus',
            'mruPeriodsMap',
            'selectedMruId',
            'selectedMonth',
            'selectedYear'
        ));
    }

    /**
     * Return live status metrics for the selected MRU & Cycle.
     */
    public function getStatus(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $mruId = $request->get('mru_id');
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $consumerQuery = ConsumerAccount::where('user_id', $userId)->where('status', 'active');
        if (!empty($mruId)) {
            $consumerQuery->where('mru_id', $mruId);
        }
        $totalCas = $consumerQuery->count();

        $billQuery = BillRecord::where('user_id', $userId)
            ->where('billing_month', $month)
            ->where('billing_year', $year);
        if (!empty($mruId)) {
            $billQuery->where('mru_id', $mruId);
        }

        $records = $billQuery->get();

        $downloadedCount = $records->where('download_status', 'downloaded')->whereNotNull('pdf_path')->count();
        $missingDownloads = max(0, $totalCas - $downloadedCount);

        $parsedCount = $records->where('parse_status', 'parsed')->count();
        $pendingParse = max(0, $downloadedCount - $parsedCount);

        $failedBills = $records->filter(function ($r) {
            return $r->download_status === 'failed' || (!empty($r->error_message) && $r->download_status !== 'downloaded');
        })->map(function ($r) {
            return [
                'id' => $r->id,
                'ca_number' => $r->ca_number,
                'consumer_name' => $r->consumer_name ?: 'Consumer ' . $r->ca_number,
                'error_message' => $r->error_message ?: 'Download failed or connection timeout',
                'download_status' => $r->download_status,
            ];
        })->values();

        $downloadPercent = $totalCas > 0 ? (int) round(($downloadedCount / $totalCas) * 100) : 0;
        $parsePercent = $downloadedCount > 0 ? (int) round(($parsedCount / $downloadedCount) * 100) : 0;

        return response()->json([
            'success' => true,
            'stats' => [
                'total_cas' => $totalCas,
                'downloaded_count' => $downloadedCount,
                'missing_downloads' => $missingDownloads,
                'failed_count' => $failedBills->count(),
                'pdf_bills_count' => $downloadedCount,
                'parsed_count' => $parsedCount,
                'pending_parse' => $pendingParse,
                'download_percent' => $downloadPercent,
                'parse_percent' => $parsePercent,
                'failed_bills' => $failedBills,
            ]
        ]);
    }

    /**
     * Run Bill Downloader task.
     */
    public function runDownloader(Request $request): JsonResponse
    {
        $request->validate([
            'mru_id' => 'nullable|integer',
            'billing_month' => 'required|integer|between:1,12',
            'billing_year' => 'required|integer|between:2020,2035',
            'mode' => 'nullable|string|in:all,missing_only,failed_only',
            'ca_numbers' => 'nullable|array',
            'ca_numbers.*' => 'string',
        ]);

        $userId = Auth::id();
        $mruId = $request->input('mru_id');
        $month = (int) $request->input('billing_month');
        $year = (int) $request->input('billing_year');
        $mode = $request->input('mode', 'all');
        $explicitCas = $request->input('ca_numbers');

        if (!empty($explicitCas) && is_array($explicitCas)) {
            $targetCas = array_values(array_unique(array_filter($explicitCas)));
        } else {
            $query = ConsumerAccount::where('user_id', $userId)->where('status', 'active');
            if (!empty($mruId)) {
                $query->where('mru_id', $mruId);
            }
            $allCas = $query->pluck('ca_number')->toArray();

            if (empty($allCas)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active consumers found in this workspace to download.',
                ], 422);
            }

            if ($mode === 'missing_only') {
                $downloadedCas = BillRecord::where('user_id', $userId)
                    ->where('billing_month', $month)
                    ->where('billing_year', $year)
                    ->where('download_status', 'downloaded')
                    ->whereNotNull('pdf_path')
                    ->pluck('ca_number')
                    ->toArray();

                $targetCas = array_values(array_diff($allCas, $downloadedCas));
            } elseif ($mode === 'failed_only') {
                $targetCas = BillRecord::where('user_id', $userId)
                    ->where('billing_month', $month)
                    ->where('billing_year', $year)
                    ->where('download_status', 'failed')
                    ->pluck('ca_number')
                    ->toArray();
            } else {
                $targetCas = $allCas;
            }
        }

        if (empty($targetCas)) {
            return response()->json([
                'success' => true,
                'message' => 'All bills are already downloaded for this period!',
                'results' => ['total' => 0, 'success' => 0, 'failed' => 0],
            ]);
        }

        $results = $this->downloadService->download($targetCas, $userId, $month, $year, $mruId);

        return response()->json([
            'success' => true,
            'message' => "Downloaded {$results['success']} out of {$results['total']} bills.",
            'results' => $results,
        ]);
    }

    /**
     * Run Bill Parser task (Extracts data from existing PDFs).
     */
    public function runParser(Request $request): JsonResponse
    {
        $request->validate([
            'mru_id' => 'nullable|integer',
            'billing_month' => 'required|integer|between:1,12',
            'billing_year' => 'required|integer|between:2020,2035',
            'mode' => 'nullable|string|in:all,pending_only',
        ]);

        $userId = Auth::id();
        $mruId = $request->input('mru_id');
        $month = (int) $request->input('billing_month');
        $year = (int) $request->input('billing_year');
        $pendingOnly = ($request->input('mode') === 'pending_only');

        $results = $this->parseService->parse($userId, $month, $year, $mruId, $pendingOnly);

        return response()->json([
            'success' => true,
            'message' => "Parsed and extracted {$results['success']} out of {$results['total']} bill records.",
            'results' => $results,
        ]);
    }

    /**
     * Get live process.log file contents.
     */
    public function getLogs(): JsonResponse
    {
        $userId = Auth::id();
        $logPath = storage_path("app/users/{$userId}/process.log");

        $content = '';
        if (File::exists($logPath)) {
            $content = File::get($logPath);
        }

        return response()->json([
            'success' => true,
            'logs' => $content,
        ]);
    }

    /**
     * Clear process.log file.
     */
    public function clearLogs(): JsonResponse
    {
        $userId = Auth::id();
        $logPath = storage_path("app/users/{$userId}/process.log");

        if (File::exists($logPath)) {
            File::put($logPath, "");
        }

        return response()->json([
            'success' => true,
            'message' => 'Logs cleared successfully.',
        ]);
    }

    /**
     * Create a new billing cycle with consumer quota check and pay-gate.
     */
    public function createCycle(Request $request): JsonResponse
    {
        $request->validate([
            'mru_id' => 'required|integer',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:2020,2035',
            'pay_overage' => 'nullable|boolean',
        ]);

        $userId = Auth::id();
        $mru = Mru::where('user_id', $userId)->findOrFail($request->input('mru_id'));

        $month = (int) $request->input('month');
        $year = (int) $request->input('year');
        $payOverage = $request->boolean('pay_overage', false);

        $consumerCount = ConsumerAccount::where('mru_id', $mru->id)
            ->where('status', 'active')
            ->count();

        $result = $this->consumerQuotaService->consumeConsumerQuota(
            user: $userId,
            mru: $mru,
            month: $month,
            year: $year,
            consumerCount: $consumerCount,
            payOverage: $payOverage
        );

        if (!$result['allowed']) {
            return response()->json([
                'success' => false,
                'requires_overage' => $result['requires_payment'] ?? false,
                'overage_type' => 'consumer_cycle',
                'amount_due' => $result['amount_due'] ?? 0,
                'extra_count' => $result['extra_count'] ?? 0,
                'message' => $result['reason'] ?? $result['message'] ?? 'Consumer quota exceeded.',
            ], 402);
        }

        return response()->json([
            'success' => true,
            'message' => "Billing cycle for {$mru->name} ({$month}/{$year}) initialized successfully.",
            'cycle' => $result['cycle'],
        ], 201);
    }

    /**
     * Explicit sync action for an existing billing cycle when new consumers are added.
     */
    public function syncCycle(BillingCycle $cycle, Request $request): JsonResponse
    {
        if ($cycle->user_id !== Auth::id()) {
            abort(403);
        }

        $payOverage = $request->boolean('pay_overage', false);
        $result = $this->consumerQuotaService->syncCycleConsumerCount($cycle, $payOverage);

        if (!$result['synced']) {
            return response()->json([
                'success' => false,
                'requires_overage' => $result['requires_payment'] ?? false,
                'amount_due' => $result['amount_due'] ?? 0,
                'diff' => $result['diff'] ?? 0,
                'message' => $result['reason'] ?? $result['message'] ?? 'Sync required overage payment.',
            ], 402);
        }

        return response()->json([
            'success' => true,
            'message' => 'Billing cycle consumer count synchronized successfully.',
            'cycle' => $result['cycle'],
            'additional_charge' => $result['additional_charge'],
        ]);
    }
}
