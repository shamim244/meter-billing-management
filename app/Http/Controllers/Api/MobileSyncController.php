<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\FieldDeskAction;
use App\Models\FieldDeskCategory;
use App\Models\MeterReadingHistory;
use App\Models\Mru;
use App\Models\User;
use App\Services\FieldDeskService;
use App\Services\MeterReadingHistoryService;
use App\Services\SmartAverageCalculationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class MobileSyncController extends Controller
{
    public function __construct(
        protected MeterReadingHistoryService $meterHistoryService,
        protected SmartAverageCalculationService $smartAverageService,
        protected FieldDeskService $fieldDeskService,
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

        $caNumbers = $consumers->pluck('ca_number')->unique()->values();

        $historicalBills = BillRecord::where('user_id', $userId)
            ->whereIn('ca_number', $caNumbers)
            ->where(function ($q) use ($activeMonth, $activeYear) {
                $q->where('billing_year', '<', $activeYear)
                    ->orWhere(function ($q2) use ($activeMonth, $activeYear) {
                        $q2->where('billing_year', $activeYear)
                            ->where('billing_month', '<', $activeMonth);
                    });
            })
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get()
            ->groupBy('ca_number');

        $meterHistories = MeterReadingHistory::where('user_id', $userId)
            ->whereIn('ca_number', $caNumbers)
            ->where(function ($q) use ($activeMonth, $activeYear) {
                $q->where('billing_year', '<', $activeYear)
                    ->orWhere(function ($q2) use ($activeMonth, $activeYear) {
                        $q2->where('billing_year', $activeYear)
                            ->where('billing_month', '<', $activeMonth);
                    });
            })
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get()
            ->groupBy('ca_number');

        $billStatuses = BillStatus::where('user_id', $userId)
            ->whereIn('ca_number', $caNumbers)
            ->where('billing_month', $activeMonth)
            ->where('billing_year', $activeYear)
            ->get()
            ->keyBy('ca_number');

        $activeFieldDeskActions = FieldDeskAction::with('category')
            ->where('user_id', $userId)
            ->whereIn('ca_number', $caNumbers)
            ->whereIn('status', ['open', 'rescheduled'])
            ->get()
            ->keyBy('ca_number');

        $applyTuning = filter_var($request->input('apply_tuning', false), FILTER_VALIDATE_BOOLEAN)
            || filter_var($request->input('include_tuning', false), FILTER_VALIDATE_BOOLEAN);

        $consumerPayload = [];
        foreach ($consumers as $consumer) {
            $bill = $activeBills->get($consumer->ca_number);
            $history = $historicalBills->get($consumer->ca_number, collect());
            $mrhForCa = $meterHistories->get($consumer->ca_number, collect());
            $st = $billStatuses->get($consumer->ca_number);
            $fda = $activeFieldDeskActions->get($consumer->ca_number);

            $effectiveStatus = ! empty($bill?->review_status) && ! in_array(strtolower(trim($bill->review_status)), ['pending', 'unsubmitted'])
                ? $bill->review_status
                : ($st ? $st->status : ($bill?->review_status ?: 'unsubmitted'));
            $effectiveTag = $bill?->tag ?: ($st?->tag);
            $effectiveRemark = $bill?->remark ?: ($st?->remark);

            if ($bill) {
                $guardrails = $applyTuning ? [] : ['tuning_enabled' => false];
                $resolved = $this->smartAverageService->resolveWorkingAndSuggestedReading(
                    $bill,
                    $consumer,
                    $history,
                    $mrhForCa,
                    0,
                    [],
                    $guardrails
                );
                $prevReading = $resolved['previous_reading'];
                $smartAvg = $resolved['smart_average_kwh'];
                $suggestedReading = $resolved['suggested_reading'];
                $workingReading = $resolved['working_reading'] !== null ? (string) $resolved['working_reading'] : null;
            } else {
                $prevReading = 0;
                if (is_numeric($consumer->last_working_reading)) {
                    $prevReading = (int) $consumer->last_working_reading;
                } elseif (is_numeric($consumer->baseline_previous_reading)) {
                    $prevReading = (int) $consumer->baseline_previous_reading;
                }
                $smartAvg = 50;
                $suggestedReading = $prevReading > 0 ? $prevReading + $smartAvg : 0;
                $workingReading = $suggestedReading > 0 ? (string) $suggestedReading : null;
                $resolved = [
                    'smart_average_kwh' => 50,
                    'base_avg_units' => 50,
                    'tuned_avg_units' => null,
                ];
            }

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
                'base_avg_units' => (int) ($resolved['base_avg_units'] ?? $smartAvg),
                'tuned_avg_units' => $resolved['tuned_avg_units'] ?? null,
                'suggested_reading' => $suggestedReading,
                'min_avg_units' => $consumer->min_avg_units ?? $bill?->min_avg_units,
                'max_avg_units' => $consumer->max_avg_units ?? $bill?->max_avg_units,
                'custom_avg_units' => $consumer->custom_avg_units ?? $bill?->custom_avg_units,
                'is_avg_excluded' => (bool) ($consumer->is_avg_excluded ?? $bill?->is_avg_excluded ?? false),
                'avg_exclude_reason' => $consumer->avg_exclude_reason ?? $bill?->avg_exclude_reason,
                'mobile' => $consumer->mobile,
                'latitude' => $consumer->latitude ? (float) $consumer->latitude : null,
                'longitude' => $consumer->longitude ? (float) $consumer->longitude : null,
                'current_bill' => [
                    'bill_id' => $bill?->id,
                    'working_reading' => $workingReading,
                    'review_status' => $effectiveStatus,
                    'tag' => $effectiveTag,
                    'remark' => $effectiveRemark,
                    'units_consumed' => ($prevReading > 0 && $workingReading !== null && (int) $workingReading >= $prevReading)
                        ? ((int) $workingReading - $prevReading)
                        : ($bill?->units_consumed ?? $smartAvg),
                    'min_avg_units' => $bill?->min_avg_units,
                    'max_avg_units' => $bill?->max_avg_units,
                    'custom_avg_units' => $bill?->custom_avg_units,
                    'is_avg_excluded' => (bool) ($bill?->is_avg_excluded ?? false),
                ],
                'field_desk' => $fda ? [
                    'id' => $fda->id,
                    'category_id' => $fda->category_id,
                    'category_code' => $fda->category?->code ?? 'general_note',
                    'category_name' => $fda->category?->name ?? 'General',
                    'category_icon' => $fda->category?->icon ?? '📋',
                    'category_color' => $fda->category?->color ?? '#10b981',
                    'priority' => $fda->priority,
                    'target_date' => $fda->target_date ? $fda->target_date->toDateString() : null,
                    'target_amount' => $fda->target_amount ? (float) $fda->target_amount : null,
                    'collected_amount' => $fda->collected_amount ? (float) $fda->collected_amount : null,
                    'private_note' => $fda->private_note,
                    'status' => $fda->status,
                    'is_due_today' => $fda->isDueToday(),
                    'is_overdue' => $fda->isOverdue(),
                    'is_upcoming' => $fda->isUpcoming(),
                ] : null,
            ];
        }

        FieldDeskCategory::ensureDefaultsExist();
        $categories = FieldDeskCategory::active()->ordered()->get()->map(fn ($c) => [
            'id' => $c->id,
            'code' => $c->code,
            'name' => $c->name,
            'icon' => $c->icon,
            'color' => $c->color,
        ]);

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
            'field_desk_categories' => $categories,
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

                $metaArr = [];
                if ($recordedAt !== null) {
                    $metaArr['recorded_at'] = $recordedAt;
                }
                if (! empty($item['latitude'])) {
                    $metaArr['latitude'] = (float) $item['latitude'];
                }
                if (! empty($item['longitude'])) {
                    $metaArr['longitude'] = (float) $item['longitude'];
                }
                if (! empty($item['photo_path'])) {
                    $metaArr['photo_path'] = $item['photo_path'];
                }
                $meta = ! empty($metaArr) ? json_encode($metaArr) : null;

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

    /**
     * SYNC-IN: Download all bill PDFs for a specific MRU packaged as a ZIP archive.
     */
    public function billsArchive(Request $request, int|string $mruId): StreamedResponse|JsonResponse
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

        // Determine active cycle or fetch all latest bills for this MRU
        $latestBill = BillRecord::where('user_id', $userId)
            ->where('mru_id', $mru->id)
            ->orderByDesc('billing_year')
            ->orderByDesc('billing_month')
            ->first();

        $activeMonth = $latestBill ? (int) $latestBill->billing_month : now()->month;
        $activeYear = $latestBill ? (int) $latestBill->billing_year : now()->year;

        $bills = BillRecord::where('user_id', $userId)
            ->where('mru_id', $mru->id)
            ->where('billing_month', $activeMonth)
            ->where('billing_year', $activeYear)
            ->get();

        if ($bills->isEmpty()) {
            $bills = BillRecord::where('user_id', $userId)
                ->where('mru_id', $mru->id)
                ->orderByDesc('billing_year')
                ->orderByDesc('billing_month')
                ->get()
                ->unique('ca_number');
        }

        $zipFileName = 'NBPDCL_MRU_'.$mru->code.'_Bills.zip';
        $zipTempPath = tempnam(sys_get_temp_dir(), 'mru_pdf_zip_');

        try {
            $zip = new ZipArchive;
            if ($zip->open($zipTempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                return response()->json(['error' => 'Failed to initialize ZIP archive.'], 500);
            }

            $addedFiles = 0;
            foreach ($bills as $bill) {
                $content = null;
                if (! empty($bill->pdf_path) && Storage::disk('local')->exists($bill->pdf_path)) {
                    $content = Storage::disk('local')->get($bill->pdf_path);
                } elseif (! empty($bill->pdf_path) && File::exists(storage_path('app/'.$bill->pdf_path))) {
                    $content = File::get(storage_path('app/'.$bill->pdf_path));
                } elseif (! empty($bill->pdf_path) && File::exists($bill->pdf_path)) {
                    $content = File::get($bill->pdf_path);
                } elseif (! empty($bill->pdf_filename) && File::exists(storage_path("app/bills/{$userId}/{$mru->code}/{$bill->pdf_filename}"))) {
                    $content = File::get(storage_path("app/bills/{$userId}/{$mru->code}/{$bill->pdf_filename}"));
                } elseif (File::exists(storage_path("app/users/{$userId}/pdfs/{$activeYear}/{$activeMonth}/{$mru->code}/{$bill->ca_number}.pdf"))) {
                    $content = File::get(storage_path("app/users/{$userId}/pdfs/{$activeYear}/{$activeMonth}/{$mru->code}/{$bill->ca_number}.pdf"));
                }

                if ($content !== null) {
                    $zip->addFromString("{$bill->ca_number}.pdf", $content);
                    $addedFiles++;
                }
            }

            // Always write an archive manifest so zip is never empty and includes metadata
            $manifest = [
                'mru_id' => $mru->id,
                'mru_code' => $mru->code,
                'mru_name' => $mru->name,
                'total_bills' => $bills->count(),
                'archived_pdfs' => $addedFiles,
                'cycle_month' => $activeMonth,
                'cycle_year' => $activeYear,
                'generated_at' => now()->toIso8601String(),
            ];
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

            $zip->close();

            return response()->streamDownload(function () use ($zipTempPath) {
                try {
                    $stream = fopen($zipTempPath, 'r');
                    fpassthru($stream);
                    fclose($stream);
                } finally {
                    if (file_exists($zipTempPath)) {
                        @unlink($zipTempPath);
                    }
                }
            }, $zipFileName, [
                'Content-Type' => 'application/zip',
            ]);
        } catch (\Throwable $e) {
            if (file_exists($zipTempPath)) {
                @unlink($zipTempPath);
            }
            throw $e;
        }
    }

    /**
     * SYNC-OUT: Batch upload offline field desk actions, phone numbers, notes from Flutter app.
     */
    public function uploadBatchFieldDesk(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'nullable|array',
            'actions' => 'nullable|array',
        ]);

        /** @var User $user */
        $user = $request->user();
        $userId = $user->id;

        $items = $request->input('items', $request->input('actions', []));
        if (empty($items) && is_array($request->all()) && ! empty($request->all()) && isset($request->all()[0])) {
            $items = $request->all();
        }

        if (empty($items)) {
            return response()->json([
                'success' => true,
                'message' => 'No field desk items to sync.',
                'total_received' => 0,
                'synced_count' => 0,
                'failed_count' => 0,
                'errors' => [],
            ]);
        }

        FieldDeskCategory::ensureDefaultsExist();
        $defaultCategory = FieldDeskCategory::active()->ordered()->first();
        $allCategories = FieldDeskCategory::all();

        $syncedCount = 0;
        $failedCount = 0;
        $errors = [];
        $savedActionIds = [];

        DB::beginTransaction();
        try {
            foreach ($items as $index => $item) {
                $caNumber = isset($item['ca_number']) ? trim((string) $item['ca_number']) : null;
                if (! $caNumber) {
                    $errors[] = ['index' => $index, 'error' => 'ca_number is required'];
                    $failedCount++;

                    continue;
                }

                $rawMobile = $item['mobile'] ?? null;
                $cleanMobile = ! empty($rawMobile)
                    ? ($this->fieldDeskService->sanitizeMobile($rawMobile) ?: trim((string) $rawMobile))
                    : null;

                $latitude = isset($item['latitude']) && is_numeric($item['latitude']) ? (float) $item['latitude'] : null;
                $longitude = isset($item['longitude']) && is_numeric($item['longitude']) ? (float) $item['longitude'] : null;
                $accuracy = isset($item['location_accuracy']) && is_numeric($item['location_accuracy']) ? (float) $item['location_accuracy'] : null;
                $note = $item['private_note'] ?? $item['note'] ?? $item['remark'] ?? null;

                // 1. Consumer Account update or auto-link
                $consumer = ConsumerAccount::withoutGlobalScopes()
                    ->where('user_id', $userId)
                    ->where('ca_number', $caNumber)
                    ->first();

                if (! $consumer) {
                    $bill = BillRecord::withoutGlobalScopes()
                        ->where('user_id', $userId)
                        ->where('ca_number', $caNumber)
                        ->latest()
                        ->first();

                    $consumerName = $item['consumer_name']
                        ?? ($bill?->consumer_name ?: ('Consumer '.$caNumber));

                    $consumer = ConsumerAccount::create([
                        'user_id' => $userId,
                        'ca_number' => $caNumber,
                        'mru_id' => $item['mru_id'] ?? ($bill?->mru_id ?? null),
                        'consumer_name' => $consumerName,
                        'meter_no' => $bill?->meter_no ?? null,
                        'tariff_category' => $bill?->tariff_category ?? 'DS-II',
                        'billing_basis' => $bill?->billing_basis ?? 'OK',
                        'baseline_amount' => $bill?->total_amount ?? 0.00,
                        'mobile' => $cleanMobile,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'location_accuracy' => $accuracy,
                        'location_updated_at' => ($latitude !== null && $longitude !== null) ? now() : null,
                        'status' => 'active',
                    ]);
                } else {
                    $changed = false;
                    if ($cleanMobile && (empty($consumer->mobile) || $cleanMobile !== $consumer->mobile)) {
                        $consumer->mobile = $cleanMobile;
                        $changed = true;
                    }
                    if ($latitude !== null && $longitude !== null) {
                        $consumer->latitude = $latitude;
                        $consumer->longitude = $longitude;
                        $consumer->location_accuracy = $accuracy;
                        $consumer->location_updated_at = now();
                        $changed = true;
                    }
                    if (! empty($item['mru_id']) && empty($consumer->mru_id)) {
                        $consumer->mru_id = (int) $item['mru_id'];
                        $changed = true;
                    }
                    if ($changed) {
                        $consumer->save();
                    }
                }

                // 2. Resolve category
                $categoryId = null;
                if (! empty($item['category_id']) && $allCategories->firstWhere('id', (int) $item['category_id'])) {
                    $categoryId = (int) $item['category_id'];
                } elseif (! empty($item['category_code'])) {
                    $cat = $allCategories->firstWhere('code', trim($item['category_code']));
                    $categoryId = $cat ? $cat->id : null;
                }
                if (! $categoryId) {
                    $categoryId = $defaultCategory ? $defaultCategory->id : 1;
                }

                // 3. Create or update FieldDeskAction
                $actionId = $item['id'] ?? null;
                $action = null;
                if ($actionId) {
                    $action = FieldDeskAction::where('user_id', $userId)->where('id', $actionId)->first();
                }

                if (! $action) {
                    $action = new FieldDeskAction;
                    $action->user_id = $userId;
                    $action->ca_number = $caNumber;
                    $action->reschedule_count = 0;
                }

                $targetDate = ! empty($item['target_date']) ? Carbon::parse($item['target_date'])->toDateString() : now()->toDateString();
                $status = $item['status'] ?? 'open';

                $action->consumer_account_id = $consumer->id;
                $action->category_id = $categoryId;
                $action->priority = in_array($item['priority'] ?? '', ['urgent', 'high', 'normal', 'low']) ? $item['priority'] : 'normal';
                $action->target_date = $targetDate;
                if (! $action->original_target_date) {
                    $action->original_target_date = $targetDate;
                }
                $action->target_amount = isset($item['target_amount']) && is_numeric($item['target_amount']) ? (float) $item['target_amount'] : null;
                $action->collected_amount = isset($item['collected_amount']) && is_numeric($item['collected_amount']) ? (float) $item['collected_amount'] : 0.00;
                $action->payment_mode = $item['payment_mode'] ?? null;
                $action->private_note = $note;
                $action->mobile = $cleanMobile ?: $consumer->mobile;
                $action->latitude = $latitude;
                $action->longitude = $longitude;
                $action->location_accuracy = $accuracy;
                $action->status = in_array($status, ['open', 'completed', 'rescheduled']) ? $status : 'open';
                $action->mru_id = $item['mru_id'] ?? $consumer->mru_id;
                $action->billing_month = $item['billing_month'] ?? null;
                $action->billing_year = $item['billing_year'] ?? null;

                if ($action->status === 'completed' && empty($action->resolved_at)) {
                    $action->resolved_at = now();
                    $action->resolution_note = $note;
                }

                $action->save();

                // Log activity
                $this->fieldDeskService->logActivity(
                    $action,
                    $action->wasRecentlyCreated ? 'created' : 'updated',
                    $note ?: 'Synced from Flutter Field App',
                    [
                        'recorded_at' => $item['recorded_at'] ?? now()->toIso8601String(),
                        'sync_source' => 'flutter_mobile',
                    ],
                    $userId
                );

                $savedActionIds[] = $action->id;
                $syncedCount++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Batch field desk sync failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Batch field desk sync failed: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully synced {$syncedCount} field desk actions.",
            'total_received' => count($items),
            'synced_count' => $syncedCount,
            'failed_count' => $failedCount,
            'action_ids' => $savedActionIds,
            'errors' => $errors,
        ]);
    }

    /**
     * SYNC-IN: Download all FieldDesk agenda items for the user across all MRUs and Global consumers.
     */
    public function downloadFieldDeskAgenda(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $userId = $user->id;

        $timeline = $request->query('timeline', 'all');
        $mruId = $request->query('mru_id');

        $query = FieldDeskAction::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->with(['category', 'consumerAccount', 'mru']);

        if (! empty($mruId)) {
            if ($mruId === 'global' || $mruId === '0') {
                $query->whereNull('mru_id');
            } else {
                $query->where('mru_id', (int) $mruId);
            }
        }

        $today = Carbon::today();
        match ($timeline) {
            'today' => $query->whereIn('status', ['open', 'rescheduled'])->whereDate('target_date', $today),
            'overdue' => $query->whereIn('status', ['open', 'rescheduled'])->whereDate('target_date', '<', $today),
            'upcoming' => $query->whereIn('status', ['open', 'rescheduled'])->whereDate('target_date', '>', $today),
            'resolved' => $query->where('status', 'completed'),
            default => null,
        };

        $actions = $query->orderBy('target_date', 'asc')->get();
        $counts = $this->fieldDeskService->getSummaryCounts($userId);

        FieldDeskCategory::ensureDefaultsExist();
        $categories = FieldDeskCategory::active()->ordered()->get()->map(fn ($c) => [
            'id' => $c->id,
            'code' => $c->code,
            'name' => $c->name,
            'icon' => $c->icon,
            'color' => $c->color,
        ]);

        $items = $actions->map(function (FieldDeskAction $action) {
            $consumer = $action->consumerAccount;

            return [
                'id' => $action->id,
                'ca_number' => $action->ca_number,
                'consumer_name' => $consumer?->consumer_name ?? 'Consumer '.$action->ca_number,
                'mobile' => $action->mobile ?: ($consumer?->mobile ?? null),
                'mru_id' => $action->mru_id,
                'mru_code' => $action->mru?->code ?? 'Global',
                'mru_name' => $action->mru?->name ?? 'Global / Unassigned',
                'category_id' => $action->category_id,
                'category_code' => $action->category?->code ?? 'general_note',
                'category_name' => $action->category?->name ?? 'General',
                'category_icon' => $action->category?->icon ?? '📋',
                'category_color' => $action->category?->color ?? '#10b981',
                'priority' => $action->priority ?? 'normal',
                'target_date' => $action->target_date ? $action->target_date->toDateString() : null,
                'target_amount' => $action->target_amount ? (float) $action->target_amount : null,
                'collected_amount' => $action->collected_amount ? (float) $action->collected_amount : null,
                'private_note' => $action->private_note,
                'status' => $action->status ?? 'open',
                'latitude' => $action->latitude ? (float) $action->latitude : ($consumer?->latitude ? (float) $consumer->latitude : null),
                'longitude' => $action->longitude ? (float) $action->longitude : ($consumer?->longitude ? (float) $consumer->longitude : null),
                'location_accuracy' => $action->location_accuracy ? (float) $action->location_accuracy : null,
                'is_due_today' => $action->isDueToday(),
                'is_overdue' => $action->isOverdue(),
                'is_upcoming' => $action->isUpcoming(),
                'created_at' => $action->created_at?->toIso8601String(),
                'updated_at' => $action->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'counts' => $counts,
            'categories' => $categories,
            'actions' => $items,
        ]);
    }
}
