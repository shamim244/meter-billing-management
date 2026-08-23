<?php

namespace App\Http\Controllers;

use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Services\BillTagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    protected BillTagService $billTagService;

    public function __construct(BillTagService $billTagService)
    {
        $this->billTagService = $billTagService;
    }

    /**
     * Display the main user dashboard.
     */
    public function index(Request $request): View
    {
        $userId = Auth::id();

        // Available MRUs for current user
        $mrus = Mru::withCount('consumerAccounts')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('consumerAccounts', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    })->orWhereHas('billRecords', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
            })
            ->orderBy('code')
            ->get();

        $selectedMruId = (string) $request->get('mru_id', $mrus->first()?->id ?? '');

        // Map of available periods per MRU (MRU -> Child Billing Cycles)
        $rawMruPeriods = BillRecord::select('mru_id', 'billing_month', 'billing_year')
            ->whereNotNull('mru_id')
            ->distinct()
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get();

        $mruPeriodsMap = [];
        foreach ($mrus as $m) {
            $mruPeriodsMap[(string)$m->id] = [];
        }
        foreach ($rawMruPeriods as $rp) {
            $mKey = (string)$rp->mru_id;
            if (!isset($mruPeriodsMap[$mKey])) {
                $mruPeriodsMap[$mKey] = [];
            }
            $periodKey = "{$rp->billing_month}_{$rp->billing_year}";
            if (!collect($mruPeriodsMap[$mKey])->contains('key', $periodKey)) {
                $mruPeriodsMap[$mKey][] = [
                    'key' => $periodKey,
                    'month' => (int) $rp->billing_month,
                    'year' => (int) $rp->billing_year,
                    'label' => date('M, Y', mktime(0, 0, 0, $rp->billing_month, 1, $rp->billing_year)),
                ];
            }
        }

        // Available periods for the selected MRU
        $periods = collect($mruPeriodsMap[$selectedMruId] ?? []);
        $latest = $periods->first();

        $selectedMonth = (int) $request->get('month', $latest['month'] ?? now()->month);
        $selectedYear = (int) $request->get('year', $latest['year'] ?? now()->year);

        // High-level KPI Stats for current user & selected MRU
        $consumersQuery = ConsumerAccount::query();
        if (!empty($selectedMruId)) {
            $consumersQuery->where('mru_id', $selectedMruId);
        }
        $totalConsumers = $consumersQuery->count();
        $totalBillsAllTime = BillRecord::count();

        // Stats for selected month & MRU
        $periodBillsQuery = BillRecord::where('billing_month', $selectedMonth)
            ->where('billing_year', $selectedYear);
        if (!empty($selectedMruId)) {
            $periodBillsQuery->where('mru_id', $selectedMruId);
        }

        $totalPeriodBills = (clone $periodBillsQuery)->count();
        $totalPeriodAmount = (clone $periodBillsQuery)->sum('total_amount');
        $totalPeriodUnits = (clone $periodBillsQuery)->sum('units_consumed');

        // Status counts for selected month & MRU
        $statusSubmittedQuery = BillStatus::where('billing_month', $selectedMonth)
            ->where('billing_year', $selectedYear)
            ->where('status', 'submitted');
        $statusCriticalQuery = BillStatus::where('billing_month', $selectedMonth)
            ->where('billing_year', $selectedYear)
            ->where('status', 'critical');
        $statusDoubtQuery = BillStatus::where('billing_month', $selectedMonth)
            ->where('billing_year', $selectedYear)
            ->where('status', 'doubt');

        if (!empty($selectedMruId)) {
            $mruCas = ConsumerAccount::where('mru_id', $selectedMruId)->pluck('ca_number');
            $statusSubmittedQuery->whereIn('ca_number', $mruCas);
            $statusCriticalQuery->whereIn('ca_number', $mruCas);
            $statusDoubtQuery->whereIn('ca_number', $mruCas);
        }

        $missingPdfCount = (clone $periodBillsQuery)
            ->where(function($q) {
                $q->whereNull('pdf_path')->orWhere('download_status', '!=', 'downloaded');
            })->count();

        $statusCounts = [
            'submitted' => $statusSubmittedQuery->count(),
            'critical' => $statusCriticalQuery->count(),
            'doubt' => $statusDoubtQuery->count(),
            'missing_pdf' => $missingPdfCount,
        ];
        $statusCounts['pending'] = max(0, $totalPeriodBills - ($statusCounts['submitted'] + $statusCounts['critical'] + $statusCounts['doubt']));

        $activeTags = $this->billTagService->getActiveTags();
        $defaultTag = $this->billTagService->getDefaultTag();

        return view('dashboard', compact(
            'periods',
            'mruPeriodsMap',
            'selectedMonth',
            'selectedYear',
            'mrus',
            'selectedMruId',
            'totalConsumers',
            'totalBillsAllTime',
            'totalPeriodBills',
            'totalPeriodAmount',
            'totalPeriodUnits',
            'statusCounts',
            'activeTags',
            'defaultTag'
        ));
    }

    /**
     * AJAX endpoint to fetch filtered, sorted, searched, paginated bill records.
     */
    public function getData(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        $mruId = $request->get('mru_id');
        $filter = $request->get('filter', $request->get('status', 'all'));
        $search = strtolower(trim($request->get('search', '')));
        $page = max(1, (int) $request->get('page', 1));
        $perPage = max(1, (int) $request->get('per_page', 50));
        
        $statusSort = $request->get('status_sort', 'default');
        $sortCol = $request->get('sort_col', 'ca_number');
        $sortAsc = $request->get('sort_asc', 'true') === 'true' || $request->get('sort_asc', true) === true;

        $baseQuery = BillRecord::with(['mru', 'consumerAccount'])
            ->where('billing_month', $month)
            ->where('billing_year', $year);

        if (!empty($mruId)) {
            $baseQuery->where('mru_id', $mruId);
        }

        if (!empty($search)) {
            $escapedSearch = addcslashes($search, '%_\\');
            $baseQuery->where(function ($q) use ($escapedSearch) {
                $q->where('ca_number', 'like', "%{$escapedSearch}%")
                  ->orWhere('consumer_name', 'like', "%{$escapedSearch}%")
                  ->orWhere('meter_no', 'like', "%{$escapedSearch}%");
            });
        }

        // Get user statuses & remarks for this period
        $userStatusModels = BillStatus::where('billing_month', $month)
            ->where('billing_year', $year)
            ->get()
            ->keyBy('ca_number');

        $allRecords = $baseQuery->get();

        // Pre-fetch historical bill records for these CAs to resolve DB Previous Reading and Outlier-Proof Smart Average
        $caNumbers = $allRecords->pluck('ca_number')->unique()->values();
        $historicalBills = BillRecord::where('user_id', $userId)
            ->whereIn('ca_number', $caNumbers)
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get()
            ->groupBy('ca_number');

        $basisHistories = \App\Models\BillingBasisHistory::where('user_id', $userId)
            ->where('billing_month', $month)
            ->where('billing_year', $year)
            ->whereIn('ca_number', $caNumbers)
            ->get()
            ->keyBy('ca_number');

        // Attach review_status, remark, and 4-Box Reading Metrics
        $mapped = $allRecords->map(function ($bill) use ($userStatusModels, $historicalBills, $basisHistories, $month, $year) {
            $st = $userStatusModels[$bill->ca_number] ?? null;
            $bill->review_status = !empty($bill->review_status) && $bill->review_status !== 'pending' ? $bill->review_status : ($st ? $st->status : ($bill->review_status ?: 'pending'));
            $bill->remark = !empty($bill->remark) ? $bill->remark : ($st ? ($st->remark ?? '') : '');
            $bill->tag = !empty($bill->tag) ? $bill->tag : ($st ? ($st->tag ?? 'OK') : 'OK');
            $bill->display_tag = $this->billTagService->getDisplayLabel($bill->tag);
            $bill->full_tag = $this->billTagService->getFullLabel($bill->tag);
            $bill->has_pdf = !empty($bill->pdf_path);

            $bbh = $basisHistories[$bill->ca_number] ?? null;
            $bill->is_consecutive_alert = (bool) ($bbh?->is_consecutive_alert ?? false);
            $bill->consecutive_count = (int) ($bbh?->consecutive_count ?? 0);

            // Master-First Identity Resolution
            $masterName = $bill->consumerAccount?->consumer_name;
            if (!empty($masterName) && !str_starts_with($masterName, 'Consumer ')) {
                $bill->consumer_name = $masterName;
            } elseif (empty($bill->consumer_name)) {
                $bill->consumer_name = "Consumer {$bill->ca_number}";
            }

            $masterMeter = $bill->consumerAccount?->meter_no;
            if (!empty($masterMeter)) {
                $bill->meter_no = $masterMeter;
            }

            $masterTariff = $bill->consumerAccount?->tariff_category;
            $bill->tariff_category = !empty($masterTariff) ? $masterTariff : ($bill->tariff_category ?: '');
            $bill->billing_basis = $bill->billing_basis ?: 'OK';

            // 1. Box 2: Previous Reading from DB
            $history = $historicalBills->get($bill->ca_number, collect());
            
            // Find strictly preceding record in DB
            $prevRecord = $history->first(function ($h) use ($month, $year) {
                return ($h->billing_year < $year) || ($h->billing_year == $year && $h->billing_month < $month);
            });

            // 1. Box 2: Previous Reading (Prioritize previous month's Working Reading; fallback to PDF baseline on First Cycle)
            $dbPrevReading = null;
            $dbPrevMonthLabel = null;
            if ($prevRecord) {
                if (!empty($prevRecord->working_reading)) {
                    $dbPrevReading = (string) $prevRecord->working_reading;
                    $priorMonthName = $prevRecord->bill_month_label ?: date('M, Y', mktime(0, 0, 0, $prevRecord->billing_month, 1, $prevRecord->billing_year));
                    $dbPrevMonthLabel = "From {$priorMonthName} (Working)";
                } elseif (!empty($prevRecord->current_reading)) {
                    $dbPrevReading = (string) $prevRecord->current_reading;
                    $priorMonthName = $prevRecord->bill_month_label ?: date('M, Y', mktime(0, 0, 0, $prevRecord->billing_month, 1, $prevRecord->billing_year));
                    $dbPrevMonthLabel = "From {$priorMonthName} (PDF)";
                } else {
                    $dbPrevReading = (string) ($prevRecord->previous_reading ?? '0');
                    $priorMonthName = $prevRecord->bill_month_label ?: date('M, Y', mktime(0, 0, 0, $prevRecord->billing_month, 1, $prevRecord->billing_year));
                    $dbPrevMonthLabel = "From {$priorMonthName} (Baseline)";
                }
            } else {
                // First Cycle in DB (no prior month in local database yet)
                $consumerAcc = $bill->consumerAccount;
                if (!empty($bill->previous_reading) && is_numeric($bill->previous_reading)) {
                    $dbPrevReading = (string) $bill->previous_reading;
                    $priorMonthNum = $month == 1 ? 12 : $month - 1;
                    $priorYearNum = $month == 1 ? $year - 1 : $year;
                    $priorMonthName = date('M, Y', mktime(0, 0, 0, $priorMonthNum, 1, $priorYearNum));
                    $dbPrevMonthLabel = "From {$priorMonthName} (PDF Baseline)";
                } elseif ($consumerAcc && !empty($consumerAcc->last_working_reading)) {
                    $dbPrevReading = (string) $consumerAcc->last_working_reading;
                    $dbPrevMonthLabel = "From Ledger ({$consumerAcc->last_working_month}/{$consumerAcc->last_working_year})";
                } elseif ($consumerAcc && !empty($consumerAcc->baseline_previous_reading)) {
                    $dbPrevReading = (string) $consumerAcc->baseline_previous_reading;
                    $dbPrevMonthLabel = "From Ledger Baseline";
                } else {
                    $dbPrevReading = '—';
                    $dbPrevMonthLabel = 'Initial Cycle Baseline';
                }
            }

            $bill->db_prev_reading = $dbPrevReading;
            $bill->db_prev_label = $dbPrevMonthLabel;

            // 2. Box 3: Smart Average Usage Calculation (Outlier-Proof, OK vs LK vs MD)
            $avgUnits = 50;
            $avgLabel = '50 kWh (Initial)';
            $avgRange = '42–58 kWh';

            if ($bill->billing_basis === 'MD') {
                $avgUnits = $bill->units_consumed ?: 76;
                $avgLabel = "{$avgUnits} kWh (MD Assessed)";
                $avgRange = 'Flat Assessed';
            } else {
                // Collect units from clean historical months
                $okUnits = $history->filter(fn($h) => ($h->billing_basis ?? 'OK') === 'OK' && $h->units_consumed > 0)->pluck('units_consumed');
                $lkUnits = $history->filter(fn($h) => ($h->billing_basis ?? '') === 'LK' && $h->units_consumed > 0)->pluck('units_consumed');

                if ($okUnits->isNotEmpty()) {
                    $sortedOk = $okUnits->sort()->values();
                    $avgUnits = (int) round($sortedOk->median());
                    $avgLabel = "{$avgUnits} kWh (From OK History)";
                    $minR = max(1, (int) round($avgUnits * 0.85));
                    $maxR = (int) round($avgUnits * 1.15);
                    $avgRange = "{$minR}–{$maxR} kWh";
                } elseif ($lkUnits->isNotEmpty()) {
                    $avgUnits = (int) round($lkUnits->median());
                    $avgLabel = "~{$avgUnits} kWh (LK Approx)";
                    $avgRange = 'Provisional';
                } elseif ($bill->units_consumed > 0) {
                    $avgUnits = (int) $bill->units_consumed;
                    $avgLabel = "{$avgUnits} kWh (This Bill)";
                    $minR = max(1, (int) round($avgUnits * 0.85));
                    $maxR = (int) round($avgUnits * 1.15);
                    $avgRange = "{$minR}–{$maxR} kWh";
                }
            }

            $bill->smart_avg_units = $avgUnits;
            $bill->smart_avg_label = $avgLabel;
            $bill->smart_avg_range = $avgRange;

            // 3. Box 1: Working Reading (Current) & Auto-Fill Projection
            // 80-90% Workflow: Rely on (Previous Working Reading + Smart Average)
            $prevNum = is_numeric($bill->db_prev_reading) ? (int)$bill->db_prev_reading : (is_numeric($bill->previous_reading) ? (int)$bill->previous_reading : 0);
            $projectedReading = $prevNum > 0 ? ($prevNum + $avgUnits) : $avgUnits;

            // Invariant: Working Reading MUST NEVER be less than Official PDF Reading if PDF is present!
            $pdfNum = (!empty($bill->current_reading) && is_numeric($bill->current_reading)) ? (int)$bill->current_reading : null;
            if ($pdfNum !== null && $projectedReading < $pdfNum) {
                // If projected is less than PDF reading, bump to at least PDF reading or (PDF + avg delta)
                $projectedReading = $pdfNum;
            }

            $bill->projected_reading = (string) $projectedReading;

            if (empty($bill->working_reading) || $bill->working_reading == '0') {
                if ($projectedReading > 0) {
                    $bill->working_reading = (string) $projectedReading;
                    $bill->is_projected = true;
                }
            } else {
                $bill->is_projected = false;
            }

            $workNum = is_numeric($bill->working_reading) ? (int)$bill->working_reading : $projectedReading;
            $bill->working_diff_units = ($prevNum > 0 && $workNum >= $prevNum) ? ($workNum - $prevNum) : ($bill->units_consumed ?: $avgUnits);

            // 4. Box 4: Official PDF Reading & Sync / Invariant Status
            $bill->official_pdf_reading = $bill->current_reading ?: null;
            if ($pdfNum !== null) {
                if ($workNum > $pdfNum) {
                    $bill->pdf_sync_status = 'ahead'; // 99.99% normal case: Working > PDF (Forward meter movement)
                    $bill->pdf_delta = $workNum - $pdfNum;
                    $bill->pdf_status_label = "+{$bill->pdf_delta} kWh Ahead";
                } elseif ($workNum === $pdfNum) {
                    $bill->pdf_sync_status = 'matched'; // 0.01% case: Working == PDF
                    $bill->pdf_delta = 0;
                    $bill->pdf_status_label = "Exact Match";
                } else {
                    $bill->pdf_sync_status = 'invalid_behind'; // ERROR: Working < PDF!
                    $bill->pdf_delta = $workNum - $pdfNum;
                    $bill->pdf_status_label = "⚠️ {$bill->pdf_delta} kWh Behind PDF!";
                }
            } else {
                $bill->pdf_sync_status = 'awaiting'; // ⏳ Awaiting PDF (80-90% relying on Prev + Avg)
                $bill->pdf_delta = null;
                $bill->pdf_status_label = "Awaiting PDF";
            }

            return $bill;
        });

        $consumersQuery = ConsumerAccount::query();
        if (!empty($mruId)) {
            $consumersQuery->where('mru_id', $mruId);
        }
        $totalConsumers = $consumersQuery->count();

        // Dynamic counts for active search
        $counts = [
            'all' => $mapped->count(),
            'pending' => $mapped->where('review_status', 'pending')->count(),
            'submitted' => $mapped->where('review_status', 'submitted')->count(),
            'critical' => $mapped->where('review_status', 'critical')->count(),
            'doubt' => $mapped->where('review_status', 'doubt')->count(),
            'missing_pdf' => $mapped->filter(fn($item) => empty($item->pdf_path) || $item->download_status !== 'downloaded')->count(),
            'total_consumers' => $totalConsumers,
        ];

        // Dynamic sum of units and amount for matching search
        $filteredUnits = $mapped->sum('units_consumed');
        $filteredAmount = $mapped->sum('total_amount');

        // Apply filter (all, pending, submitted, critical, doubt)
        if (!empty($filter) && $filter !== 'all') {
            $mapped = $mapped->filter(fn($item) => $item->review_status === $filter);
        }

        // Apply Tag filter (all, OK, BQC, RCQ, 24days, etc.)
        $tagFilter = $request->get('tag_filter', $request->get('tag', 'all'));
        if (!empty($tagFilter) && $tagFilter !== 'all') {
            $mapped = $mapped->filter(fn($item) => strtoupper($item->tag ?? '') === strtoupper($tagFilter));
        }

        // Status Priority Sort Weights
        $statusWeights = null;
        if ($statusSort === 'pdcs') {
            $statusWeights = ['pending' => 1, 'doubt' => 2, 'critical' => 3, 'submitted' => 4];
        } elseif ($statusSort === 'dcps') {
            $statusWeights = ['doubt' => 1, 'critical' => 2, 'pending' => 3, 'submitted' => 4];
        } elseif ($statusSort === 'cdps') {
            $statusWeights = ['critical' => 1, 'doubt' => 2, 'pending' => 3, 'submitted' => 4];
        } elseif ($statusSort === 'spdc') {
            $statusWeights = ['submitted' => 1, 'pending' => 2, 'doubt' => 3, 'critical' => 4];
        }

        // Sort collection
        $sorted = $mapped->sort(function ($a, $b) use ($statusWeights, $sortCol, $sortAsc) {
            if ($statusWeights !== null) {
                $wa = $statusWeights[$a->review_status] ?? 99;
                $wb = $statusWeights[$b->review_status] ?? 99;
                if ($wa !== $wb) {
                    return $wa - $wb;
                }
            }

            $valA = match ($sortCol) {
                'current_reading' => (float) ($a->current_reading ?? 0),
                'previous_reading' => (float) ($a->previous_reading ?? 0),
                'units_consumed', 'units' => (float) ($a->units_consumed ?? 0),
                'total_amount', 'amount' => (float) ($a->total_amount ?? 0),
                'meter_no' => (string) ($a->meter_no ?? ''),
                'bill_month' => (string) ($a->bill_month_label ?? ''),
                default => (string) ($a->ca_number ?? ''),
            };

            $valB = match ($sortCol) {
                'current_reading' => (float) ($b->current_reading ?? 0),
                'previous_reading' => (float) ($b->previous_reading ?? 0),
                'units_consumed', 'units' => (float) ($b->units_consumed ?? 0),
                'total_amount', 'amount' => (float) ($b->total_amount ?? 0),
                'meter_no' => (string) ($b->meter_no ?? ''),
                'bill_month' => (string) ($b->bill_month_label ?? ''),
                default => (string) ($b->ca_number ?? ''),
            };

            if (is_numeric($valA) && is_numeric($valB)) {
                if ($valA == $valB) {
                    return strcmp($a->ca_number, $b->ca_number);
                }
                return ($valA < $valB) ? ($sortAsc ? -1 : 1) : ($sortAsc ? 1 : -1);
            }

            $cmp = strcasecmp((string)$valA, (string)$valB);
            if ($cmp === 0) {
                return strcmp($a->ca_number, $b->ca_number);
            }
            return $sortAsc ? $cmp : -$cmp;
        })->values();

        $totalMatching = $sorted->count();
        $totalPages = max(1, (int) ceil($totalMatching / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $items = $sorted->slice($offset, $perPage)->values();

        $availablePeriods = BillRecord::select('billing_month', 'billing_year')
            ->when(!empty($mruId), function ($q) use ($mruId) {
                $q->where('mru_id', $mruId);
            })
            ->distinct()
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get()
            ->unique(fn($p) => "{$p->billing_month}_{$p->billing_year}")
            ->map(function ($p) {
                return [
                    'key' => "{$p->billing_month}_{$p->billing_year}",
                    'month' => (int) $p->billing_month,
                    'year' => (int) $p->billing_year,
                    'label' => date('M, Y', mktime(0, 0, 0, $p->billing_month, 1, $p->billing_year)),
                ];
            })
            ->values();

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        return response()->json([
            'success' => true,
            'data' => $items,
            'counts' => $counts,
            'filtered_units' => $filteredUnits,
            'filtered_amount' => $filteredAmount,
            'available_periods' => $availablePeriods,
            'user_shortcuts' => $currentUser ? $currentUser->getShortcutMap() : config('shortcuts.default'),
            'shortcut_labels' => $currentUser ? $currentUser->getShortcutLabels() : config('shortcuts.labels'),
            'available_tags' => $this->billTagService->getActiveTags(),
            'default_tag' => $this->billTagService->getDefaultTag(),
            'pagination' => [
                'total' => $totalMatching,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $totalPages,
                'from' => $totalMatching > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $totalMatching),
            ]
        ]);
    }

    /**
     * Update working reading for a single bill, sync ConsumerAccount ledger, and cascade to future cycles.
     */
    public function updateWorkingReading(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|exists:bill_records,id',
            'working_reading' => 'required|string|max:30',
        ]);

        $userId = Auth::id();
        $readingVal = trim($request->working_reading);

        $bill = \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $request, $readingVal) {
            $bill = BillRecord::where('user_id', $userId)
                ->where('id', $request->id)
                ->firstOrFail();

            $bill->working_reading = $readingVal;
            $bill->save();

            // 1. Update Master Reading Ledger on ConsumerAccount
            $consumer = ConsumerAccount::where('user_id', $userId)
                ->where('ca_number', $bill->ca_number)
                ->first();

            if ($consumer) {
                $isNewer = false;
                if (!$consumer->last_working_year || $bill->billing_year > $consumer->last_working_year) {
                    $isNewer = true;
                } elseif ($bill->billing_year == $consumer->last_working_year && $bill->billing_month >= ($consumer->last_working_month ?? 0)) {
                    $isNewer = true;
                }

                if ($isNewer) {
                    $consumer->last_working_reading = $readingVal;
                    $consumer->last_working_month = $bill->billing_month;
                    $consumer->last_working_year = $bill->billing_year;
                    $consumer->save();
                }
            }

            // 2. Cascade Auto-Sync to subsequent future cycles in DB for this CA
            $subsequentBills = BillRecord::where('user_id', $userId)
                ->where('ca_number', $bill->ca_number)
                ->where(function ($q) use ($bill) {
                    $q->where('billing_year', '>', $bill->billing_year)
                      ->orWhere(function ($q2) use ($bill) {
                          $q2->where('billing_year', $bill->billing_year)
                             ->where('billing_month', '>', $bill->billing_month);
                      });
                })
                ->orderBy('billing_year', 'asc')
                ->orderBy('billing_month', 'asc')
                ->get();

            $currentChainReading = is_numeric($readingVal) ? (int)$readingVal : 0;
            foreach ($subsequentBills as $futureBill) {
                $futureBill->previous_reading = (string) $currentChainReading;
                $avgUnits = $futureBill->units_consumed ?: 50;
                $newProjected = $currentChainReading + $avgUnits;
                if (!empty($futureBill->current_reading) && is_numeric($futureBill->current_reading)) {
                    $pdfReading = (int) $futureBill->current_reading;
                    if ($newProjected < $pdfReading) {
                        $newProjected = $pdfReading;
                    }
                }
                $futureBill->working_reading = (string) $newProjected;
                $futureBill->save();
                $currentChainReading = $newProjected;
            }

            return $bill;
        });

        return response()->json([
            'success' => true,
            'message' => "Working reading updated to {$bill->working_reading}",
            'working_reading' => $bill->working_reading,
        ]);
    }

    /**
     * Bulk project & auto-fill working readings for all bills in active cycle.
     * Guaranteed: Relies on (Previous Month Working Reading + Smart Average) & Working >= PDF Reading
     */
    public function bulkProjectReadings(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $mruId = $request->input('mru_id');

        $query = BillRecord::where('user_id', $userId)
            ->where('billing_month', $month)
            ->where('billing_year', $year);

        if (!empty($mruId)) {
            $query->where('mru_id', $mruId);
        }

        $bills = $query->get();

        $count = \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $bills, $month, $year) {
            $count = 0;
            $caNumbers = $bills->pluck('ca_number')->unique()->values();
            $historicalBills = BillRecord::where('user_id', $userId)
                ->whereIn('ca_number', $caNumbers)
                ->orderBy('billing_year', 'desc')
                ->orderBy('billing_month', 'desc')
                ->get()
                ->groupBy('ca_number');

            $consumers = ConsumerAccount::where('user_id', $userId)
                ->whereIn('ca_number', $caNumbers)
                ->get()
                ->keyBy('ca_number');

            foreach ($bills as $bill) {
                $history = $historicalBills->get($bill->ca_number, collect());
                $consumer = $consumers->get($bill->ca_number);
                
                // Prioritize strictly preceding record's working_reading
                $prevRecord = $history->first(function ($h) use ($month, $year) {
                    return ($h->billing_year < $year) || ($h->billing_year == $year && $h->billing_month < $month);
                });

                $dbPrevReading = 0;
                if ($prevRecord) {
                    $dbPrevReading = (int) ($prevRecord->working_reading ?: ($prevRecord->current_reading ?: $prevRecord->previous_reading));
                } elseif ($consumer && !empty($consumer->last_working_reading)) {
                    $dbPrevReading = (int) $consumer->last_working_reading;
                } elseif ($consumer && !empty($consumer->baseline_previous_reading)) {
                    $dbPrevReading = (int) $consumer->baseline_previous_reading;
                } else {
                    $dbPrevReading = is_numeric($bill->previous_reading) ? (int)$bill->previous_reading : 0;
                }

                // Calculate clean average for this CA
                $okUnits = $history->filter(fn($h) => ($h->billing_basis ?? 'OK') === 'OK' && $h->units_consumed > 0)->pluck('units_consumed');
                $avgUnits = $okUnits->isNotEmpty() ? (int) round($okUnits->median()) : ($bill->units_consumed ?: 50);

                $projected = $dbPrevReading + $avgUnits;

                // Invariant: Working Reading MUST NEVER be < PDF Reading if PDF exists!
                if (!empty($bill->current_reading) && is_numeric($bill->current_reading)) {
                    $pdfReading = (int) $bill->current_reading;
                    if ($projected < $pdfReading) {
                        $projected = $pdfReading;
                    }
                }

                $bill->working_reading = (string) $projected;
                $bill->save();

                // Sync master ledger
                if ($consumer) {
                    $isNewer = false;
                    if (!$consumer->last_working_year || $year > $consumer->last_working_year) {
                        $isNewer = true;
                    } elseif ($year == $consumer->last_working_year && $month >= ($consumer->last_working_month ?? 0)) {
                        $isNewer = true;
                    }

                    if ($isNewer) {
                        $consumer->last_working_reading = (string) $projected;
                        $consumer->last_working_month = $month;
                        $consumer->last_working_year = $year;
                        $consumer->save();
                    }
                }

                $count++;
            }

            return $count;
        });

        return response()->json([
            'success' => true,
            'message' => "Successfully projected working readings for {$count} accounts.",
            'count' => $count,
        ]);
    }

    /**
     * Update review status for a single bill record.
     */
    public function updateReviewStatus(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|exists:bill_records,id',
            'review_status' => 'required|in:pending,submitted,doubt,critical',
        ]);

        $userId = Auth::id();

        $bill = \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $request) {
            $bill = BillRecord::where('user_id', $userId)->findOrFail($request->id);
            $bill->review_status = $request->review_status;
            $bill->save();

            // Synchronize with bill_statuses table
            if ($request->review_status === 'pending') {
                $statusRecord = BillStatus::where('user_id', $userId)
                    ->where('ca_number', $bill->ca_number)
                    ->where('billing_month', $bill->billing_month)
                    ->where('billing_year', $bill->billing_year)
                    ->first();
                if ($statusRecord) {
                    if (empty($statusRecord->remark)) {
                        $statusRecord->delete();
                    } else {
                        $statusRecord->status = 'pending';
                        $statusRecord->save();
                    }
                }
            } else {
                BillStatus::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'ca_number' => $bill->ca_number,
                        'billing_month' => $bill->billing_month,
                        'billing_year' => $bill->billing_year,
                    ],
                    [
                        'status' => $request->review_status,
                    ]
                );
            }

            return $bill;
        });

        return response()->json([
            'success' => true,
            'message' => "Review status updated to " . ucfirst($bill->review_status),
            'review_status' => $bill->review_status,
        ]);
    }

    /**
     * Update remark for a single bill record.
     */
    public function updateRemark(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|exists:bill_records,id',
            'remark' => 'nullable|string|max:255',
        ]);

        $userId = Auth::id();
        $remark = $request->remark ? trim($request->remark) : null;

        $bill = \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $request, $remark) {
            $bill = BillRecord::where('user_id', $userId)->findOrFail($request->id);
            $bill->remark = $remark;
            $bill->save();

            // Synchronize with bill_statuses table
            BillStatus::updateOrCreate(
                [
                    'user_id' => $userId,
                    'ca_number' => $bill->ca_number,
                    'billing_month' => $bill->billing_month,
                    'billing_year' => $bill->billing_year,
                ],
                [
                    'remark' => $remark,
                ]
            );

            return $bill;
        });

        return response()->json([
            'success' => true,
            'message' => "Remark updated",
            'remark' => $bill->remark,
        ]);
    }

    /**
     * Update tag for a single bill record.
     */
    public function updateTag(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'nullable|exists:bill_records,id',
            'ca_number' => 'nullable|string',
            'billing_month' => 'nullable|integer',
            'billing_year' => 'nullable|integer',
            'tag' => 'required|string|max:64',
        ]);

        $userId = Auth::id();
        $tag = trim((string) $request->tag) ?: 'OK';

        $bill = \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $request, $tag) {
            $bill = null;
            if ($request->id) {
                $bill = BillRecord::where('user_id', $userId)->find($request->id);
            }
            if (!$bill && $request->ca_number && $request->billing_month && $request->billing_year) {
                $bill = BillRecord::where('user_id', $userId)
                    ->where('ca_number', $request->ca_number)
                    ->where('billing_month', (int) $request->billing_month)
                    ->where('billing_year', (int) $request->billing_year)
                    ->first();
            }

            if ($bill) {
                $bill->tag = $tag;
                $bill->save();
            }

            $ca = $bill ? $bill->ca_number : $request->ca_number;
            $month = $bill ? $bill->billing_month : (int)$request->billing_month;
            $year = $bill ? $bill->billing_year : (int)$request->billing_year;

            if ($ca && $month && $year) {
                BillStatus::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'ca_number' => $ca,
                        'billing_month' => $month,
                        'billing_year' => $year,
                    ],
                    [
                        'tag' => $tag,
                    ]
                );
            }

            return $bill;
        });

        return response()->json([
            'success' => true,
            'message' => "Tag updated to " . $this->billTagService->getDisplayLabel($tag),
            'tag' => $tag,
            'display_tag' => $this->billTagService->getDisplayLabel($tag),
            'full_tag' => $this->billTagService->getFullLabel($tag),
        ]);
    }

    /**
     * Get active keyboard shortcuts for current user.
     */
    public function getShortcuts(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'shortcuts' => $user->getShortcutMap(),
            'labels' => $user->getShortcutLabels(),
            'defaults' => config('shortcuts.default'),
            'is_customized' => !empty($user->shortcuts),
        ]);
    }

    /**
     * Save user's custom shortcut key bindings.
     */
    public function saveShortcuts(Request $request): JsonResponse
    {
        $request->validate([
            'shortcuts' => 'required|array',
            'shortcuts.copy_ca' => 'nullable|string|max:30',
            'shortcuts.focus_reading' => 'nullable|string|max:30',
            'shortcuts.auto_fill_reading' => 'nullable|string|max:30',
            'shortcuts.submit_ok' => 'nullable|string|max:30',
            'shortcuts.mark_doubt' => 'nullable|string|max:30',
            'shortcuts.mark_critical' => 'nullable|string|max:30',
            'shortcuts.next_card' => 'nullable|string|max:30',
            'shortcuts.prev_card' => 'nullable|string|max:30',
            'shortcuts.open_remark' => 'nullable|string|max:30',
            'shortcuts.exit_box' => 'nullable|string|max:30',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->shortcuts = $request->shortcuts;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Custom shortcuts saved successfully!',
            'shortcuts' => $user->getShortcutMap(),
        ]);
    }

    /**
     * Reset user's shortcuts to Admin / System defaults.
     */
    public function resetShortcuts(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->shortcuts = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Shortcuts reset to system defaults.',
            'shortcuts' => $user->getShortcutMap(),
        ]);
    }
}
