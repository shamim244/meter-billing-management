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

        $statusCounts['basis_ok'] = (clone $periodBillsQuery)->where(function($q) {
            $q->where('billing_basis', 'OK')
              ->orWhere(function($sub) {
                  $sub->whereNull('billing_basis')
                      ->where(function($sub2) {
                          $sub2->whereDoesntHave('consumerAccount')
                               ->orWhereHas('consumerAccount', fn($ca) => $ca->where('billing_basis', 'OK')->orWhereNull('billing_basis'));
                      });
              });
        })->count();
        $statusCounts['basis_lk'] = (clone $periodBillsQuery)->where(function($q) {
            $q->where('billing_basis', 'LK')
              ->orWhere(function($sub) {
                  $sub->whereNull('billing_basis')
                      ->whereHas('consumerAccount', fn($ca) => $ca->where('billing_basis', 'LK'));
              });
        })->count();
        $statusCounts['basis_md'] = (clone $periodBillsQuery)->where(function($q) {
            $q->where('billing_basis', 'MD')
              ->orWhere(function($sub) {
                  $sub->whereNull('billing_basis')
                      ->whereHas('consumerAccount', fn($ca) => $ca->where('billing_basis', 'MD'));
              });
        })->count();
        $statusCounts['basis_pl'] = (clone $periodBillsQuery)->where(function($q) {
            $q->where('billing_basis', 'PL')
              ->orWhere(function($sub) {
                  $sub->whereNull('billing_basis')
                      ->whereHas('consumerAccount', fn($ca) => $ca->where('billing_basis', 'PL'));
              });
        })->count();
        $statusCounts['basis_rn'] = (clone $periodBillsQuery)->where(function($q) {
            $q->where('billing_basis', 'RN')
              ->orWhere(function($sub) {
                  $sub->whereNull('billing_basis')
                      ->whereHas('consumerAccount', fn($ca) => $ca->where('billing_basis', 'RN'));
              });
        })->count();

        $activeTags = $this->billTagService->getActiveTags();
        $defaultTag = $this->billTagService->getDefaultTag();
        $activeSubscription = Auth::user()?->activeSubscription;

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
            'defaultTag',
            'activeSubscription'
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
        $perPageParam = $request->get('per_page');
        if ($perPageParam === 'all' || $perPageParam === '-1') {
            $perPage = 1000;
        } else {
            $perPage = max(1, min(1000, (int) ($perPageParam ?: 50)));
        }
        
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
                  ->orWhere('meter_no', 'like', "%{$escapedSearch}%")
                  ->orWhere('tariff_category', 'like', "%{$escapedSearch}%")
                  ->orWhereHas('consumerAccount', function ($caQ) use ($escapedSearch) {
                      $caQ->where('consumer_name', 'like', "%{$escapedSearch}%")
                          ->orWhere('meter_no', 'like', "%{$escapedSearch}%")
                          ->orWhere('tariff_category', 'like', "%{$escapedSearch}%")
                          ->orWhere('billing_basis', 'like', "%{$escapedSearch}%");
                  });
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

        $consumers = ConsumerAccount::where('user_id', $userId)
            ->whereIn('ca_number', $caNumbers)
            ->get()
            ->keyBy('ca_number');

        // Attach review_status, remark, and 4-Box Reading Metrics
        $mapped = $allRecords->map(function ($bill) use ($userStatusModels, $historicalBills, $basisHistories, $consumers, $month, $year) {
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

            // Master-First Identity & Profile Resolution
            $consumerAcc = $bill->consumerAccount ?? ($consumers[$bill->ca_number] ?? null);
            $masterName = $consumerAcc?->consumer_name;
            if (!empty($masterName) && !str_starts_with($masterName, 'Consumer ')) {
                $bill->consumer_name = $masterName;
            } elseif (empty($bill->consumer_name)) {
                $bill->consumer_name = "Consumer {$bill->ca_number}";
            }

            $masterMeter = $consumerAcc?->meter_no;
            if (!empty($masterMeter)) {
                $bill->meter_no = $masterMeter;
            }

            $masterTariff = $consumerAcc?->tariff_category;
            $bill->tariff_category = !empty($masterTariff) ? $masterTariff : ($bill->tariff_category ?: 'DS-II');

            $masterBasis = $consumerAcc?->billing_basis;
            if (empty($bill->billing_basis) && !empty($masterBasis)) {
                $bill->billing_basis = $masterBasis;
            }
            $bill->billing_basis = $bill->billing_basis ?: 'OK';

            if ((empty($bill->total_amount) || (float)$bill->total_amount == 0.0) && $consumerAcc && (float)$consumerAcc->baseline_amount > 0) {
                $bill->total_amount = (float) $consumerAcc->baseline_amount;
                $bill->is_baseline_amount = true;
            }

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

            // 2. Box 3: Smart Average Usage Calculation (Outlier-Proof, DB-driven delta & OK vs LK vs MD)
            $avgUnits = 50;
            $avgLabel = '50 kWh (Initial)';
            $avgRange = '42–58 kWh';

            // Collect historical consumption units from clean DB history deltas
            $historyList = $history->values();
            $okUnits = collect();
            $lkUnits = collect();

            for ($i = 0; $i < $historyList->count(); $i++) {
                $h = $historyList[$i];
                $hBasis = strtoupper(trim((string)($h->billing_basis ?: 'OK')));

                $units = 0;
                if ($h->units_consumed && $h->units_consumed > 0) {
                    $units = (int) $h->units_consumed;
                } else {
                    $rCurr = is_numeric($h->working_reading) ? (int)$h->working_reading : (is_numeric($h->current_reading) ? (int)$h->current_reading : null);
                    $rPrev = is_numeric($h->previous_reading) ? (int)$h->previous_reading : null;
                    if ($rCurr !== null && $rPrev !== null && $rCurr >= $rPrev) {
                        $units = $rCurr - $rPrev;
                    } elseif ($rCurr !== null && isset($historyList[$i + 1])) {
                        $nextH = $historyList[$i + 1];
                        $rNext = is_numeric($nextH->working_reading) ? (int)$nextH->working_reading : (is_numeric($nextH->current_reading) ? (int)$nextH->current_reading : null);
                        if ($rNext !== null && $rCurr >= $rNext) {
                            $units = $rCurr - $rNext;
                        }
                    }
                }

                if ($units > 0) {
                    if ($hBasis === 'OK') {
                        $okUnits->push($units);
                    } elseif ($hBasis === 'LK') {
                        $lkUnits->push($units);
                    }
                }
            }

            $currentBillUnits = 0;
            if ($bill->units_consumed > 0) {
                $currentBillUnits = (int) $bill->units_consumed;
            } else {
                $w = is_numeric($bill->working_reading) ? (int)$bill->working_reading : (is_numeric($bill->current_reading) ? (int)$bill->current_reading : 0);
                $p = is_numeric($dbPrevReading) ? (int)$dbPrevReading : (is_numeric($bill->previous_reading) ? (int)$bill->previous_reading : 0);
                if ($w > 0 && $p > 0 && $w >= $p) {
                    $currentBillUnits = $w - $p;
                }
            }

            if ($bill->billing_basis === 'MD') {
                $avgUnits = $currentBillUnits ?: ($bill->units_consumed ?: 76);
                $avgLabel = "{$avgUnits} kWh (MD Assessed)";
                $avgRange = 'Flat Assessed';
            } else {
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
                } elseif ($currentBillUnits > 0) {
                    $avgUnits = (int) $currentBillUnits;
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

            $readingSource = $bill->reading_source ?: 'auto';

            if ($readingSource === 'manual') {
                $bill->is_manual = true;
                $bill->is_projected = false;
                $bill->reading_source = 'manual';
            } else {
                $bill->is_manual = false;
                $bill->is_projected = true;
                $bill->reading_source = 'auto';

                if (empty($bill->working_reading) || $bill->working_reading == '0') {
                    if ($projectedReading > 0) {
                        $bill->working_reading = (string) $projectedReading;
                    }
                }
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
            'basis_ok' => $mapped->filter(fn($item) => strtoupper(trim((string)($item->billing_basis ?: 'OK'))) === 'OK')->count(),
            'basis_lk' => $mapped->filter(fn($item) => strtoupper(trim((string)($item->billing_basis ?: ''))) === 'LK')->count(),
            'basis_md' => $mapped->filter(fn($item) => strtoupper(trim((string)($item->billing_basis ?: ''))) === 'MD')->count(),
            'basis_pl' => $mapped->filter(fn($item) => strtoupper(trim((string)($item->billing_basis ?: ''))) === 'PL')->count(),
            'basis_rn' => $mapped->filter(fn($item) => strtoupper(trim((string)($item->billing_basis ?: ''))) === 'RN')->count(),
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

        // Apply Basis filter (all, OK, LK, MD, PL, RN)
        $basisFilter = strtoupper(trim((string) $request->get('basis_filter', $request->get('basis', 'all'))));
        if (!empty($basisFilter) && $basisFilter !== 'ALL') {
            $mapped = $mapped->filter(function ($item) use ($basisFilter) {
                $b = strtoupper(trim((string) ($item->billing_basis ?: 'OK')));
                return $b === $basisFilter;
            });
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

            $numericCols = ['working_reading', 'current_reading', 'previous_reading', 'units_consumed', 'units', 'total_amount', 'amount', 'basis_priority', 'review_status', 'status'];

            if (in_array($sortCol, $numericCols, true)) {
                $numA = match ($sortCol) {
                    'working_reading' => (float) ($a->working_reading ?? 0),
                    'current_reading' => (float) ($a->current_reading ?? 0),
                    'previous_reading' => (float) ($a->previous_reading ?? 0),
                    'units_consumed', 'units' => (float) ($a->units_consumed ?? 0),
                    'total_amount', 'amount' => (float) ($a->total_amount ?? 0),
                    'basis_priority' => match (strtoupper(trim((string) ($a->billing_basis ?: 'OK')))) {
                        'OK' => 1,
                        'LK' => 2,
                        'MD' => 3,
                        'PL' => 4,
                        'RN' => 5,
                        default => 6,
                    },
                    'review_status', 'status' => match ($a->review_status ?? 'pending') {
                        'pending' => 1,
                        'doubt' => 2,
                        'critical' => 3,
                        'submitted' => 4,
                        default => 5,
                    },
                    default => 0.0,
                };

                $numB = match ($sortCol) {
                    'working_reading' => (float) ($b->working_reading ?? 0),
                    'current_reading' => (float) ($b->current_reading ?? 0),
                    'previous_reading' => (float) ($b->previous_reading ?? 0),
                    'units_consumed', 'units' => (float) ($b->units_consumed ?? 0),
                    'total_amount', 'amount' => (float) ($b->total_amount ?? 0),
                    'basis_priority' => match (strtoupper(trim((string) ($b->billing_basis ?: 'OK')))) {
                        'OK' => 1,
                        'LK' => 2,
                        'MD' => 3,
                        'PL' => 4,
                        'RN' => 5,
                        default => 6,
                    },
                    'review_status', 'status' => match ($b->review_status ?? 'pending') {
                        'pending' => 1,
                        'doubt' => 2,
                        'critical' => 3,
                        'submitted' => 4,
                        default => 5,
                    },
                    default => 0.0,
                };

                if ($numA == $numB) {
                    return strcmp((string)$a->ca_number, (string)$b->ca_number);
                }

                return $sortAsc ? ($numA <=> $numB) : ($numB <=> $numA);
            }

            // String and natural alphanumeric sorting
            $valA = match ($sortCol) {
                'consumer_name', 'name' => trim((string) ($a->consumer_name ?? '')),
                'meter_no' => trim((string) ($a->meter_no ?? '')),
                'bill_month' => trim((string) ($a->bill_month_label ?? '')),
                'billing_basis', 'basis' => strtoupper(trim((string) ($a->billing_basis ?: 'OK'))),
                default => trim((string) ($a->ca_number ?? '')),
            };

            $valB = match ($sortCol) {
                'consumer_name', 'name' => trim((string) ($b->consumer_name ?? '')),
                'meter_no' => trim((string) ($b->meter_no ?? '')),
                'bill_month' => trim((string) ($b->bill_month_label ?? '')),
                'billing_basis', 'basis' => strtoupper(trim((string) ($b->billing_basis ?: 'OK'))),
                default => trim((string) ($b->ca_number ?? '')),
            };

            $cmp = strnatcasecmp((string)$valA, (string)$valB);
            if ($cmp === 0) {
                return strcmp((string)$a->ca_number, (string)$b->ca_number);
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

        $bill = BillRecord::where('user_id', $userId)
            ->where('id', $request->id)
            ->firstOrFail();

        // Submitted Bill Lockout: Require explicit confirmation/override flag if bill is submitted
        $isSubmitted = (strtolower(trim($bill->review_status ?? '')) === 'submitted');
        if (!$isSubmitted) {
            $isSubmitted = BillStatus::where('user_id', $userId)
                ->where('ca_number', $bill->ca_number)
                ->where('billing_month', $bill->billing_month)
                ->where('billing_year', $bill->billing_year)
                ->whereRaw('LOWER(status) = ?', ['submitted'])
                ->exists();
        }

        $force = $request->boolean('force') || $request->boolean('override_submitted') || $request->boolean('confirm_override');

        if ($isSubmitted && !$force) {
            return response()->json([
                'success' => false,
                'requires_override' => true,
                'is_submitted' => true,
                'message' => 'This bill has been submitted and is locked. Confirm unlock or set status to pending to update reading.',
            ], 422);
        }

        $readingSource = $request->input('source', 'manual');

        $bill = \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $bill, $readingVal, $readingSource) {
            $bill->working_reading = $readingVal;
            $bill->reading_source = $readingSource;
            if (is_numeric($readingVal)) {
                $prev = is_numeric($bill->previous_reading) ? (int)$bill->previous_reading : 0;
                if ($prev > 0 && (int)$readingVal >= $prev) {
                    $bill->units_consumed = (int)$readingVal - $prev;
                }
            }
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

            $submittedFutureStatuses = BillStatus::where('user_id', $userId)
                ->where('ca_number', $bill->ca_number)
                ->whereRaw('LOWER(status) = ?', ['submitted'])
                ->get()
                ->keyBy(fn($s) => "{$s->billing_month}_{$s->billing_year}");

            $currentChainReading = is_numeric($readingVal) ? (int)$readingVal : 0;
            foreach ($subsequentBills as $futureBill) {
                $statusKey = "{$futureBill->billing_month}_{$futureBill->billing_year}";
                $isFutureSubmitted = (strtolower(trim($futureBill->review_status ?? '')) === 'submitted') || isset($submittedFutureStatuses[$statusKey]);
                $isFutureManual = ($futureBill->reading_source === 'manual');

                // Cascade Protection: Do NOT overwrite future bills whose review_status is 'submitted' or reading_source is 'manual'
                if ($isFutureSubmitted || $isFutureManual) {
                    if (!empty($futureBill->working_reading) && is_numeric($futureBill->working_reading)) {
                        $currentChainReading = (int) $futureBill->working_reading;
                    }
                    continue;
                }

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
                $futureBill->reading_source = 'auto';
                $futureBill->save();
                $currentChainReading = $newProjected;
            }

            return $bill;
        });

        return response()->json([
            'success' => true,
            'message' => "Working reading updated to {$bill->working_reading}",
            'working_reading' => $bill->working_reading,
            'reading_source' => $bill->reading_source,
            'is_manual' => ($bill->reading_source === 'manual'),
            'is_projected' => ($bill->reading_source !== 'manual'),
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
            ->where('billing_year', $year)
            ->where(function ($q) {
                $q->whereNull('review_status')
                  ->orWhereRaw('LOWER(review_status) != ?', ['submitted']);
            })
            ->where(function ($q) {
                $q->whereNull('reading_source')
                  ->orWhere('reading_source', '!=', 'manual');
            });

        // Exclude accounts already marked submitted in bill_statuses
        $submittedCas = BillStatus::where('user_id', $userId)
            ->where('billing_month', $month)
            ->where('billing_year', $year)
            ->whereRaw('LOWER(status) = ?', ['submitted'])
            ->pluck('ca_number');

        if ($submittedCas->isNotEmpty()) {
            $query->whereNotIn('ca_number', $submittedCas);
        }

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
                // Safeguard against overwriting manual custom entries or submitted bills
                if ($bill->reading_source === 'manual') {
                    continue;
                }
                if (strtolower(trim($bill->review_status ?? '')) === 'submitted') {
                    continue;
                }

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

                // Calculate clean average for this CA from DB history deltas
                $priorHistory = $history->filter(function ($h) use ($month, $year) {
                    return ($h->billing_year < $year) || ($h->billing_year == $year && $h->billing_month < $month);
                })->values();

                $okUnits = collect();
                for ($i = 0; $i < $priorHistory->count(); $i++) {
                    $h = $priorHistory[$i];
                    if (($h->billing_basis ?? 'OK') !== 'OK') continue;

                    $units = 0;
                    if ($h->units_consumed && $h->units_consumed > 0) {
                        $units = (int) $h->units_consumed;
                    } else {
                        $rCurr = is_numeric($h->working_reading) ? (int)$h->working_reading : (is_numeric($h->current_reading) ? (int)$h->current_reading : null);
                        $rPrev = is_numeric($h->previous_reading) ? (int)$h->previous_reading : null;
                        if ($rCurr !== null && $rPrev !== null && $rCurr >= $rPrev) {
                            $units = $rCurr - $rPrev;
                        } elseif ($rCurr !== null && isset($priorHistory[$i + 1])) {
                            $nextH = $priorHistory[$i + 1];
                            $rNext = is_numeric($nextH->working_reading) ? (int)$nextH->working_reading : (is_numeric($nextH->current_reading) ? (int)$nextH->current_reading : null);
                            if ($rNext !== null && $rCurr >= $rNext) {
                                $units = $rCurr - $rNext;
                            }
                        }
                    }
                    if ($units > 0) {
                        $okUnits->push($units);
                    }
                }

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
                $bill->reading_source = 'auto';
                $bill->units_consumed = $avgUnits;
                $bill->calculated_avg_units = $avgUnits;
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

    /**
     * Ultra-lightweight health-check ping for offline detection and reconnection synchronization.
     * Returns server timestamp and fresh CSRF token.
     */
    public function ping(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'server_time' => now()->toISOString(),
            'timestamp' => now()->timestamp,
            'authenticated' => Auth::check(),
            'csrf_token' => csrf_token(),
        ]);
    }
}

