<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\MeterReadingHistory;
use App\Models\Mru;
use App\Models\User;
use App\Services\EngineService;
use App\Services\MeterReadingHistoryService;
use App\Services\SmartAverageCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillApiController extends Controller
{
    public function __construct(
        protected MeterReadingHistoryService $meterHistoryService,
        protected SmartAverageCalculationService $smartAverageService,
        protected EngineService $engineService,
    ) {}

    /**
     * Fetch filtered & sorted bills matching exact Web Dashboard parity.
     * Supports priority sorting (pdcs, dcps, cdps, spdc), column sorting, and search.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $userId = $user->id;

        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $statusFilter = strtolower(trim((string) $request->input('status', 'all')));
        $mruParam = $request->input('mru_id');
        $search = trim((string) $request->input('search', ''));
        $sortCol = strtolower(trim((string) $request->input('sort_col', 'ca_number')));
        $sortAsc = filter_var($request->input('sort_asc', true), FILTER_VALIDATE_BOOLEAN);
        $statusSort = strtolower(trim((string) $request->input('status_sort', 'default')));
        $perPage = min(max((int) $request->input('per_page', 50), 1), 250);
        $page = max((int) $request->input('page', 1), 1);

        // Resolve MRU if specified
        $mru = null;
        if ($mruParam) {
            $mru = Mru::where('user_id', $userId)
                ->where(fn ($q) => $q->where('id', $mruParam)->orWhere('code', $mruParam)->orWhere('name', $mruParam))
                ->first();
        }

        // Base query for counts
        $baseQuery = BillRecord::where('user_id', $userId)
            ->where('billing_month', $month)
            ->where('billing_year', $year);

        if ($mru) {
            $baseQuery->where('mru_id', $mru->id);
        }

        // Compute total counts by status via single database group-by query
        $statusRows = (clone $baseQuery)
            ->selectRaw("LOWER(COALESCE(review_status, 'pending')) as status_key, COUNT(*) as aggregate")
            ->groupBy('status_key')
            ->pluck('aggregate', 'status_key');

        $submittedCount = (int) ($statusRows->get('submitted') ?? 0);
        $doubtCount = (int) ($statusRows->get('doubt') ?? 0);
        $criticalCount = (int) ($statusRows->get('critical') ?? 0);
        $pendingCount = 0;

        foreach ($statusRows as $key => $cnt) {
            if ($key !== 'submitted' && $key !== 'doubt' && $key !== 'critical') {
                $pendingCount += (int) $cnt;
            }
        }

        $counts = [
            'all' => $submittedCount + $doubtCount + $criticalCount + $pendingCount,
            'pending' => $pendingCount,
            'submitted' => $submittedCount,
            'doubt' => $doubtCount,
            'critical' => $criticalCount,
        ];

        // Filter by status
        $query = clone $baseQuery;
        if ($statusFilter === 'pending' || $statusFilter === 'unsubmitted') {
            $query->where(function ($q) {
                $q->whereNull('review_status')
                    ->orWhereRaw('LOWER(review_status) IN (?, ?)', ['pending', 'unsubmitted']);
            });
        } elseif ($statusFilter !== 'all') {
            $query->whereRaw('LOWER(review_status) = ?', [$statusFilter]);
        }

        // Search filter
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ca_number', 'like', "%{$search}%")
                    ->orWhere('consumer_name', 'like', "%{$search}%")
                    ->orWhere('meter_no', 'like', "%{$search}%");
            });
        }

        $totalMatching = (clone $query)->count();
        $totalPages = max(1, (int) ceil($totalMatching / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        // Priority sequence sorting weights in SQL
        if ($statusSort && in_array($statusSort, ['pdcs', 'dcps', 'cdps', 'spdc'], true)) {
            $weights = match ($statusSort) {
                'pdcs' => ['pending' => 1, 'unsubmitted' => 1, 'doubt' => 2, 'critical' => 3, 'submitted' => 4],
                'dcps' => ['doubt' => 1, 'critical' => 2, 'pending' => 3, 'unsubmitted' => 3, 'submitted' => 4],
                'cdps' => ['critical' => 1, 'doubt' => 2, 'pending' => 3, 'unsubmitted' => 3, 'submitted' => 4],
                'spdc' => ['submitted' => 1, 'pending' => 2, 'unsubmitted' => 2, 'doubt' => 3, 'critical' => 4],
            };

            $caseSql = 'CASE ';
            foreach ($weights as $st => $w) {
                $caseSql .= "WHEN LOWER(COALESCE(review_status, 'pending')) = '{$st}' THEN {$w} ";
            }
            $caseSql .= 'ELSE 99 END ASC';

            $query->orderByRaw($caseSql);
        }

        $direction = $sortAsc ? 'asc' : 'desc';

        // Column sort
        if (in_array($sortCol, ['amount', 'total_amount'], true)) {
            $query->orderBy('total_amount', $direction);
        } elseif (in_array($sortCol, ['units', 'units_consumed'], true)) {
            $query->orderBy('units_consumed', $direction);
        } elseif ($sortCol === 'current_reading') {
            $query->orderBy('current_reading', $direction);
        } elseif ($sortCol === 'previous_reading') {
            $query->orderBy('previous_reading', $direction);
        } elseif ($sortCol === 'working_reading') {
            $query->orderBy('working_reading', $direction);
        } elseif (in_array($sortCol, ['consumer_name', 'name'], true)) {
            $query->orderBy('consumer_name', $direction);
        } elseif ($sortCol === 'meter_no') {
            $query->orderBy('meter_no', $direction);
        } else {
            $query->orderBy('ca_number', $direction);
        }

        // Secondary stable tie-breaker
        $query->orderBy('ca_number', 'asc');

        $items = $query->skip($offset)->take($perPage)->get();

        $data = $items->map(function (BillRecord $b) {
            return [
                'id' => $b->id,
                'ca_number' => $b->ca_number,
                'consumer_name' => $b->consumer_name,
                'tariff_category' => $b->category,
                'billing_basis' => $b->billing_basis ?: 'OK',
                'meter_no' => $b->meter_no,
                'total_amount' => $b->total_amount !== null ? (float) $b->total_amount : null,
                'units_consumed' => $b->units_consumed !== null ? (int) $b->units_consumed : null,
                'db_prev_reading' => $b->previous_reading,
                'working_reading' => $b->working_reading,
                'official_pdf_reading' => $b->current_reading,
                'pdf_sync_status' => ($b->current_reading && $b->working_reading && (string) $b->current_reading === (string) $b->working_reading) ? 'matched' : 'custom',
                'review_status' => $b->review_status ?? 'pending',
                'reason_code' => $b->tag,
                'remark' => $b->remark,
            ];
        });

        return response()->json([
            'success' => true,
            'mru' => $mru ? [
                'id' => $mru->id,
                'code' => $mru->code,
                'name' => $mru->name,
            ] : null,
            'period' => sprintf('%02d/%d', $month, $year),
            'counts' => $counts,
            'pagination' => [
                'total' => $totalMatching,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $totalPages,
            ],
            'data' => $data,
        ]);
    }

    /**
     * Submit human review decision (submitted, doubt, critical, pending) with readings & remarks.
     */
    public function review(Request $request): JsonResponse
    {
        $request->validate([
            'ca_number' => 'required|string',
            'billing_month' => 'required|integer|between:1,12',
            'billing_year' => 'required|integer',
            'status' => 'required|string|in:submitted,doubt,critical,pending',
            'reason_code' => 'nullable|string|max:100',
            'remark' => 'nullable|string|max:500',
            'working_reading' => 'nullable|numeric',
        ]);

        /** @var User $user */
        $user = $request->user();
        $userId = $user->id;

        $ca = trim((string) $request->input('ca_number'));
        $month = (int) $request->input('billing_month');
        $year = (int) $request->input('billing_year');
        $status = strtolower(trim((string) $request->input('status')));
        $reasonCode = $request->input('reason_code');
        $remark = $request->input('remark');
        $workingReading = $request->input('working_reading');

        $bill = BillRecord::where('user_id', $userId)
            ->where('ca_number', $ca)
            ->where('billing_month', $month)
            ->where('billing_year', $year)
            ->first();

        if (! $bill) {
            return response()->json([
                'success' => false,
                'error' => 'NotFound',
                'message' => "Bill record not found for CA {$ca} in period {$month}/{$year}.",
            ], 404);
        }

        DB::transaction(function () use ($userId, $bill, $status, $reasonCode, $remark, $workingReading, $ca) {
            $bill->review_status = $status;
            if ($reasonCode !== null) {
                $bill->tag = $reasonCode;
            }
            if ($remark !== null) {
                $bill->remark = $remark;
            }

            if ($workingReading !== null && is_numeric($workingReading)) {
                $bill->working_reading = (string) $workingReading;
                $bill->reading_source = 'api';

                $prev = is_numeric($bill->previous_reading) ? (int) $bill->previous_reading : 0;
                if ($prev <= 0) {
                    $consumer = ConsumerAccount::where('user_id', $userId)->where('ca_number', $ca)->first();
                    $resolved = $this->smartAverageService->resolvePreviousReading($ca, $bill->billing_month, $bill->billing_year, $bill, $consumer, null);
                    if (! empty($resolved['reading'])) {
                        $prev = (int) $resolved['reading'];
                        $bill->previous_reading = $prev;
                    }
                }

                if ($prev > 0 && (int) $workingReading >= $prev) {
                    $bill->units_consumed = (int) $workingReading - $prev;
                    $bill->calculated_avg_units = $bill->units_consumed;
                }

                // MeterReadingHistory recording
                try {
                    $isSubmitted = ($status === 'submitted');
                    $this->meterHistoryService->recordFromWorkingReading(
                        $bill,
                        (string) $workingReading,
                        $bill->units_consumed ? (int) $bill->units_consumed : null,
                        $isSubmitted
                    );
                } catch (\Throwable $e) {
                    Log::warning("BillApiController review history error: {$e->getMessage()}");
                }

                // Update Consumer master ledger
                $consumer = ConsumerAccount::where('user_id', $userId)->where('ca_number', $ca)->first();
                if ($consumer) {
                    $consumer->last_working_reading = (string) $workingReading;
                    $consumer->last_working_month = $bill->billing_month;
                    $consumer->last_working_year = $bill->billing_year;
                    $consumer->save();
                }
            }

            $bill->save();

            // Sync bill_statuses
            BillStatus::updateOrCreate(
                [
                    'user_id' => $userId,
                    'ca_number' => $ca,
                    'billing_month' => $bill->billing_month,
                    'billing_year' => $bill->billing_year,
                ],
                [
                    'status' => $status,
                    'remark' => $bill->remark,
                    'tag' => $bill->tag,
                    'mru_id' => $bill->mru_id,
                ]
            );
        });

        return response()->json([
            'success' => true,
            'message' => "Bill for CA {$ca} updated to '{$status}'.",
            'data' => [
                'ca_number' => $bill->ca_number,
                'review_status' => $bill->fresh()->review_status,
                'reason_code' => $bill->fresh()->tag,
                'remark' => $bill->fresh()->remark,
                'working_reading' => $bill->fresh()->working_reading,
                'updated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Batch sync reviews and readings for offline-to-online transitions.
     */
    public function batchSync(Request $request): JsonResponse
    {
        $request->validate([
            'billing_month' => 'required|integer|between:1,12',
            'billing_year' => 'required|integer',
            'reviews' => 'required|array|min:1',
            'reviews.*.ca_number' => 'required|string',
            'reviews.*.status' => 'required|string|in:submitted,doubt,critical,pending',
            'reviews.*.reason_code' => 'nullable|string',
            'reviews.*.remark' => 'nullable|string',
            'reviews.*.working_reading' => 'nullable|numeric',
        ]);

        /** @var User $user */
        $user = $request->user();
        $userId = $user->id;

        $month = (int) $request->input('billing_month');
        $year = (int) $request->input('billing_year');
        $reviews = $request->input('reviews');

        $caList = collect($reviews)
            ->pluck('ca_number')
            ->filter()
            ->map(fn ($ca) => trim((string) $ca))
            ->unique()
            ->values()
            ->all();

        // Bulk pre-fetch active bills and consumer accounts in single round-trips
        $existingBills = BillRecord::where('user_id', $userId)
            ->where('billing_month', $month)
            ->where('billing_year', $year)
            ->whereIn('ca_number', $caList)
            ->get()
            ->keyBy('ca_number');

        $existingConsumers = ConsumerAccount::where('user_id', $userId)
            ->whereIn('ca_number', $caList)
            ->get()
            ->keyBy('ca_number');

        $meterHistories = MeterReadingHistory::where('user_id', $userId)
            ->whereIn('ca_number', $caList)
            ->where(function ($q) use ($month, $year) {
                $q->where('billing_year', '<', $year)
                    ->orWhere(function ($q2) use ($month, $year) {
                        $q2->where('billing_year', $year)
                            ->where('billing_month', '<', $month);
                    });
            })
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get()
            ->groupBy('ca_number');

        $syncedCount = 0;
        $failedCount = 0;
        $failures = [];

        $billsToUpsert = [];
        $billStatusesToUpsert = [];
        $consumersToUpsert = [];
        $meterHistoriesToUpsert = [];
        $now = now();

        foreach ($reviews as $index => $item) {
            $ca = trim((string) $item['ca_number']);
            $status = strtolower(trim((string) $item['status']));
            $reasonCode = $item['reason_code'] ?? null;
            $remark = $item['remark'] ?? null;
            $workingReading = isset($item['working_reading']) ? $item['working_reading'] : null;

            $bill = $existingBills->get($ca);

            if (! $bill) {
                $failures[] = [
                    'index' => $index,
                    'ca_number' => $ca,
                    'error' => "Record not found for period {$month}/{$year}",
                ];
                $failedCount++;

                continue;
            }

            $bill->review_status = $status;
            if ($reasonCode !== null) {
                $bill->tag = $reasonCode;
            }
            if ($remark !== null) {
                $bill->remark = $remark;
            }

            $hasNewReading = ($workingReading !== null && is_numeric($workingReading));
            $effectiveReading = $hasNewReading ? (string) $workingReading : $bill->working_reading;

            if ($hasNewReading) {
                $bill->working_reading = (string) $workingReading;
                $bill->reading_source = 'api_batch';

                $prev = is_numeric($bill->previous_reading) ? (int) $bill->previous_reading : 0;
                if ($prev <= 0) {
                    $consumer = $existingConsumers->get($ca);
                    $mrhForCa = $meterHistories->get($ca, collect());
                    $resolved = $this->smartAverageService->resolvePreviousReading($ca, $month, $year, $bill, $consumer, null, $mrhForCa);
                    if (! empty($resolved['reading'])) {
                        $prev = (int) $resolved['reading'];
                        $bill->previous_reading = $prev;
                    }
                }

                if ($prev > 0 && (int) $workingReading >= $prev) {
                    $bill->units_consumed = (int) $workingReading - $prev;
                    $bill->calculated_avg_units = $bill->units_consumed;
                }

                $consumer = $existingConsumers->get($ca);
                if ($consumer) {
                    $consumer->last_working_reading = (string) $workingReading;
                    $consumer->last_working_month = $month;
                    $consumer->last_working_year = $year;
                    $consumer->updated_at = $now;

                    $consumersToUpsert[$consumer->id] = [
                        'id' => $consumer->id,
                        'user_id' => $consumer->user_id,
                        'ca_number' => $consumer->ca_number,
                        'last_working_reading' => (string) $workingReading,
                        'last_working_month' => $month,
                        'last_working_year' => $year,
                        'updated_at' => $now,
                    ];
                }
            }

            if ($effectiveReading !== null && is_numeric($effectiveReading)) {
                $consumer = $existingConsumers->get($ca);
                $isSubmitted = ($status === 'submitted');

                $meterHistoriesToUpsert[$ca] = [
                    'user_id' => $bill->user_id,
                    'ca_number' => $bill->ca_number,
                    'billing_month' => (int) $bill->billing_month,
                    'billing_year' => (int) $bill->billing_year,
                    'reading_source' => 'working',
                    'mru_id' => $bill->mru_id,
                    'consumer_id' => $bill->consumer_account_id ?: ($consumer?->id ?: null),
                    'bill_record_id' => $bill->id,
                    'previous_reading' => $bill->previous_reading !== null ? (string) $bill->previous_reading : null,
                    'current_reading' => $bill->current_reading !== null ? (string) $bill->current_reading : null,
                    'working_reading' => (string) $effectiveReading,
                    'units_consumed' => $bill->units_consumed ? (int) $bill->units_consumed : null,
                    'billing_basis' => strtoupper(trim((string) ($bill->billing_basis ?: 'OK'))),
                    'is_closed' => $isSubmitted,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $bill->updated_at = $now;
            $billsToUpsert[$bill->id] = [
                'id' => $bill->id,
                'user_id' => $bill->user_id,
                'ca_number' => $bill->ca_number,
                'billing_month' => (int) $bill->billing_month,
                'billing_year' => (int) $bill->billing_year,
                'mru_id' => $bill->mru_id,
                'review_status' => $bill->review_status,
                'tag' => $bill->tag,
                'remark' => $bill->remark,
                'working_reading' => $bill->working_reading !== null ? (string) $bill->working_reading : null,
                'reading_source' => $bill->reading_source,
                'previous_reading' => $bill->previous_reading !== null ? (string) $bill->previous_reading : null,
                'units_consumed' => $bill->units_consumed !== null ? (int) $bill->units_consumed : null,
                'calculated_avg_units' => $bill->calculated_avg_units !== null ? (int) $bill->calculated_avg_units : null,
                'updated_at' => $now,
            ];

            $billStatusesToUpsert[$ca] = [
                'user_id' => $userId,
                'ca_number' => $ca,
                'billing_month' => $month,
                'billing_year' => $year,
                'status' => $status,
                'remark' => $bill->remark,
                'tag' => $bill->tag,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $syncedCount++;
        }

        DB::beginTransaction();
        try {
            if (! empty($billsToUpsert)) {
                foreach (array_chunk(array_values($billsToUpsert), 200) as $chunk) {
                    BillRecord::upsert(
                        $chunk,
                        ['id'],
                        ['review_status', 'tag', 'remark', 'working_reading', 'reading_source', 'previous_reading', 'units_consumed', 'calculated_avg_units', 'updated_at']
                    );
                }
            }

            if (! empty($consumersToUpsert)) {
                foreach (array_chunk(array_values($consumersToUpsert), 200) as $chunk) {
                    ConsumerAccount::upsert(
                        $chunk,
                        ['id'],
                        ['last_working_reading', 'last_working_month', 'last_working_year', 'updated_at']
                    );
                }
            }

            if (! empty($meterHistoriesToUpsert)) {
                foreach (array_chunk(array_values($meterHistoriesToUpsert), 200) as $chunk) {
                    MeterReadingHistory::upsert(
                        $chunk,
                        ['user_id', 'ca_number', 'billing_month', 'billing_year', 'reading_source'],
                        ['mru_id', 'consumer_id', 'bill_record_id', 'previous_reading', 'current_reading', 'working_reading', 'units_consumed', 'billing_basis', 'is_closed', 'updated_at']
                    );
                }
            }

            if (! empty($billStatusesToUpsert)) {
                foreach (array_chunk(array_values($billStatusesToUpsert), 200) as $chunk) {
                    BillStatus::upsert(
                        $chunk,
                        ['user_id', 'ca_number', 'billing_month', 'billing_year'],
                        ['status', 'remark', 'tag', 'updated_at']
                    );
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("batchSync transaction failed: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Batch synchronization encountered an internal error: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'total_received' => count($reviews),
            'synced_count' => $syncedCount,
            'failed_count' => $failedCount,
            'failures' => $failures,
        ]);
    }

    /**
     * Quick-pull a single consumer's bill from official BSPHCL API.
     */
    public function quickPull(Request $request): JsonResponse
    {
        $request->validate([
            'ca_number' => 'required|string',
            'billing_month' => 'required|integer|between:1,12',
            'billing_year' => 'required|integer',
            'mru_id' => 'nullable|integer',
        ]);

        /** @var User $user */
        $user = $request->user();
        $ca = trim((string) $request->input('ca_number'));
        $month = (int) $request->input('billing_month');
        $year = (int) $request->input('billing_year');
        $mruId = $request->input('mru_id');

        try {
            $result = $this->engineService->processSingleCa($user->id, $ca, $month, $year, $mruId);

            return response()->json([
                'success' => true,
                'message' => "Successfully pulled bill for CA {$ca}.",
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to pull bill for CA {$ca}: {$e->getMessage()}",
            ], 500);
        }
    }
}
