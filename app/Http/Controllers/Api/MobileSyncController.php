<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\MeterReadingHistory;
use App\Models\Mru;
use App\Models\User;
use App\Services\MeterReadingHistoryService;
use App\Services\SmartAverageCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MobileSyncController extends Controller
{
    public function __construct(
        protected MeterReadingHistoryService $meterHistoryService,
        protected SmartAverageCalculationService $smartAverageService,
    ) {}

    /**
     * SYNC-IN: Download all data for a specific MRU to store locally in the Flutter SQLite database.
     */
    public function downloadMruPayload(Request $request, int|string $mruId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $userId = $user->id;

        $mru = Mru::where('user_id', $userId)
            ->where(fn ($q) => $q->where('id', $mruId)->orWhere('code', $mruId)->orWhere('name', $mruId))
            ->first();

        if (! $mru) {
            return response()->json([
                'success' => false,
                'message' => 'MRU not found or not assigned to your account.',
            ], 404);
        }

        // Determine the latest/active billing cycle for this MRU
        $latestBill = BillRecord::where('user_id', $userId)
            ->where('mru_id', $mru->id)
            ->orderByDesc('billing_year')
            ->orderByDesc('billing_month')
            ->first();

        $activeMonth = $latestBill ? (int) $latestBill->billing_month : now()->month;
        $activeYear = $latestBill ? (int) $latestBill->billing_year : now()->year;

        // Fetch master consumers belonging to this MRU
        $consumers = ConsumerAccount::where('user_id', $userId)
            ->where('mru_id', $mru->id)
            ->get();

        // Fetch current cycle bills for this MRU
        $activeBills = BillRecord::where('user_id', $userId)
            ->where('mru_id', $mru->id)
            ->where('billing_month', $activeMonth)
            ->where('billing_year', $activeYear)
            ->get()
            ->keyBy('ca_number');

        $consumerPayload = [];
        foreach ($consumers as $consumer) {
            $bill = $activeBills->get($consumer->ca_number);

            // Previous reading resolution
            $prevReading = 0;
            if ($bill && is_numeric($bill->previous_reading)) {
                $prevReading = (int) $bill->previous_reading;
            } elseif (is_numeric($consumer->last_working_reading)) {
                $prevReading = (int) $consumer->last_working_reading;
            }

            // Smart average calculation
            $smartAvg = $bill?->calculated_avg_units ?? $bill?->units_consumed ?? 50;

            $consumerPayload[] = [
                'ca_number' => $consumer->ca_number,
                'consumer_name' => $consumer->consumer_name,
                'meter_number' => $consumer->meter_no,
                'address' => $consumer->address,
                'category' => $consumer->category,
                'sanctioned_load' => $consumer->sanctioned_load,
                'billing_basis' => $consumer->billing_basis ?: ($bill?->billing_basis ?: 'Normal(OK)'),
                'previous_reading' => $prevReading,
                'smart_average_kwh' => (int) $smartAvg,
                'suggested_reading' => $prevReading > 0 ? $prevReading + (int) $smartAvg : 0,
                'current_bill' => $bill ? [
                    'bill_id' => $bill->id,
                    'working_reading' => $bill->working_reading,
                    'review_status' => $bill->review_status ?? 'unsubmitted',
                    'remark' => $bill->remark,
                    'units_consumed' => $bill->units_consumed,
                ] : null,
            ];
        }

        return response()->json([
            'success' => true,
            'sync_timestamp' => now()->toISOString(),
            'mru' => [
                'id' => $mru->id,
                'code' => $mru->code,
                'name' => $mru->name,
                'full_identifier' => $mru->full_identifier,
                'consumer_count' => count($consumerPayload),
            ],
            'cycle' => [
                'label' => sprintf('%s-%d', strtoupper(date('M', mktime(0, 0, 0, $activeMonth, 1))), $activeYear),
                'month' => $activeMonth,
                'year' => $activeYear,
            ],
            'consumers' => $consumerPayload,
        ]);
    }

    /**
     * SYNC-OUT: Batch upload offline meter readings collected in Flutter back to Laravel MySQL.
     */
    public function uploadBatchReadings(Request $request): JsonResponse
    {
        $request->validate([
            'mru_id' => 'required',
            'readings' => 'required|array|min:1',
            'readings.*.ca_number' => 'required|string',
            'readings.*.working_reading' => 'nullable|numeric',
            'readings.*.status' => 'nullable|string',
            'readings.*.remark' => 'nullable|string',
            'readings.*.reason_code' => 'nullable|string',
            'readings.*.tag' => 'nullable|string',
            'readings.*.recorded_at' => 'nullable|string',
        ]);

        /** @var User $user */
        $user = $request->user();
        $userId = $user->id;

        $mruId = $request->input('mru_id');
        $mru = Mru::where('user_id', $userId)
            ->where(fn ($q) => $q->where('id', $mruId)->orWhere('code', $mruId)->orWhere('name', $mruId))
            ->first();

        if (! $mru) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid MRU specified.',
            ], 404);
        }

        $readings = $request->input('readings');
        $caList = collect($readings)
            ->pluck('ca_number')
            ->filter()
            ->map(fn ($ca) => trim((string) $ca))
            ->unique()
            ->values()
            ->all();

        // Bulk pre-fetch active bills (matching MRU first, latest cycle) and consumers in single round-trips
        $allBills = BillRecord::where('user_id', $userId)
            ->where(function ($q) use ($mru) {
                $q->where('mru_id', $mru->id)->orWhereNull('mru_id');
            })
            ->whereIn('ca_number', $caList)
            ->orderByDesc('billing_year')
            ->orderByDesc('billing_month')
            ->get();

        $activeBillsByCa = [];
        foreach ($allBills as $b) {
            if (! isset($activeBillsByCa[$b->ca_number])) {
                $activeBillsByCa[$b->ca_number] = $b;
            }
        }

        $existingConsumers = ConsumerAccount::where('user_id', $userId)
            ->whereIn('ca_number', $caList)
            ->get()
            ->keyBy('ca_number');

        $meterHistories = MeterReadingHistory::where('user_id', $userId)
            ->whereIn('ca_number', $caList)
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get()
            ->groupBy('ca_number');

        $syncedCount = 0;
        $failedCount = 0;
        $errors = [];

        $billsToUpsert = [];
        $billStatusesToUpsert = [];
        $consumersToUpsert = [];
        $meterHistoriesToUpsert = [];
        $now = now();

        foreach ($readings as $index => $item) {
            $ca = trim((string) $item['ca_number']);
            $workingReading = isset($item['working_reading']) ? $item['working_reading'] : null;
            $status = isset($item['status']) && trim((string) $item['status']) !== ''
                ? trim((string) $item['status'])
                : 'Submitted';
            $remark = $item['remark'] ?? null;
            $reasonCode = $item['reason_code'] ?? $item['tag'] ?? null;
            $recordedAt = $item['recorded_at'] ?? null;

            $bill = $activeBillsByCa[$ca] ?? null;

            if (! $bill) {
                $errors[] = [
                    'index' => $index,
                    'ca_number' => $ca,
                    'error' => 'Consumer bill record not found in active cycle.',
                ];
                $failedCount++;

                continue;
            }

            $bill->review_status = $status;
            if ($remark !== null) {
                $bill->remark = $remark;
            }
            if ($reasonCode !== null) {
                $bill->tag = $reasonCode;
            }

            $hasNewReading = ($workingReading !== null && is_numeric($workingReading));
            $effectiveReading = $hasNewReading ? (string) $workingReading : $bill->working_reading;

            if ($hasNewReading) {
                $bill->working_reading = (string) $workingReading;
                $bill->reading_source = 'flutter_mobile';

                $prev = is_numeric($bill->previous_reading) ? (int) $bill->previous_reading : 0;
                if ($prev <= 0) {
                    $consumer = $existingConsumers->get($ca);
                    $mrhForCa = $meterHistories->get($ca, collect());
                    $resolved = $this->smartAverageService->resolvePreviousReading($ca, $bill->billing_month, $bill->billing_year, $bill, $consumer, null, $mrhForCa);
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
                    $consumer->last_working_month = $bill->billing_month;
                    $consumer->last_working_year = $bill->billing_year;
                    $consumer->updated_at = $now;

                    $consumersToUpsert[$consumer->id] = [
                        'id' => $consumer->id,
                        'user_id' => $consumer->user_id,
                        'ca_number' => $consumer->ca_number,
                        'last_working_reading' => (string) $workingReading,
                        'last_working_month' => (int) $bill->billing_month,
                        'last_working_year' => (int) $bill->billing_year,
                        'updated_at' => $now,
                    ];
                }
            }

            if ($effectiveReading !== null && is_numeric($effectiveReading)) {
                $consumer = $existingConsumers->get($ca);
                $isSubmitted = (strtolower($status) === 'submitted');

                $meta = null;
                if ($recordedAt !== null) {
                    $meta = json_encode(['recorded_at' => $recordedAt]);
                }

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
                    'meta' => $meta,
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
                'billing_month' => $bill->billing_month,
                'billing_year' => $bill->billing_year,
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
            Log::error('Batch sync failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Batch sync failed due to an unexpected server error: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully synced {$syncedCount} readings.",
            'total_received' => count($readings),
            'synced_count' => $syncedCount,
            'failed_count' => $failedCount,
            'errors' => $errors,
        ]);
    }
}
