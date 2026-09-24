<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use App\Services\MeterReadingHistoryService;
use App\Services\SmartAverageCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutomationController extends Controller
{
    public function __construct(
        protected MeterReadingHistoryService $meterHistoryService,
        protected SmartAverageCalculationService $smartAverageService,
    ) {}

    /**
     * Get the queue of consumers to be automated in the Android app.
     * Filterable by MRU, billing cycle, review status, and limit.
     */
    public function getQueue(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $userId = $user->id;

        $mruCode = $request->input('mru_code');
        $mruId = $request->input('mru_id');
        $cycle = $request->input('cycle'); // e.g. "SEP-2026" or "09-2026"
        $statusFilter = strtolower($request->input('status', 'unsubmitted'));
        $limit = min((int) $request->input('limit', 50), 250);

        // Resolve MRU if provided
        $mru = null;
        if ($mruId) {
            $mru = Mru::where('user_id', $userId)->where('id', $mruId)->first();
        } elseif ($mruCode) {
            $mru = Mru::where('user_id', $userId)->where(fn ($q) => $q->where('code', $mruCode)->orWhere('name', $mruCode))->first();
        }

        // Determine cycle month & year
        $month = null;
        $year = null;
        if ($cycle) {
            if (preg_match('/^([A-Za-z]{3})[-_\s]?(\d{4})$/', trim($cycle), $matches)) {
                $monthNum = date('n', strtotime("1-{$matches[1]}-2000"));
                if ($monthNum) {
                    $month = (int) $monthNum;
                    $year = (int) $matches[2];
                }
            } elseif (preg_match('/^(\d{1,2})[-_\s]?(\d{4})$/', trim($cycle), $matches)) {
                $month = (int) $matches[1];
                $year = (int) $matches[2];
            }
        }

        // Default to the latest cycle if not specified
        if (! $month || ! $year) {
            $latest = BillRecord::where('user_id', $userId)
                ->when($mru, fn ($q) => $q->where('mru_id', $mru->id))
                ->orderByDesc('billing_year')
                ->orderByDesc('billing_month')
                ->first();

            if ($latest) {
                $month = (int) $latest->billing_month;
                $year = (int) $latest->billing_year;
            } else {
                $month = now()->month;
                $year = now()->year;
            }
        }

        // Query bills
        $query = BillRecord::where('user_id', $userId)
            ->where('billing_month', $month)
            ->where('billing_year', $year);

        if ($mru) {
            $query->where('mru_id', $mru->id);
        }

        // Filter status
        if ($statusFilter === 'unsubmitted' || $statusFilter === 'pending') {
            $query->where(function ($q) {
                $q->whereNull('review_status')
                    ->orWhereRaw('LOWER(review_status) IN (?, ?)', ['pending', 'unsubmitted']);
            });
        } elseif ($statusFilter !== 'all') {
            $query->whereRaw('LOWER(review_status) = ?', [$statusFilter]);
        }

        $totalInQueue = (clone $query)->count();
        $bills = $query->orderBy('id', 'asc')->limit($limit)->get();

        $formatted = $bills->map(function (BillRecord $bill) {
            $prev = is_numeric($bill->previous_reading) ? (int) $bill->previous_reading : 0;
            $working = is_numeric($bill->working_reading) ? (int) $bill->working_reading : null;
            $avgUnits = (int) ($bill->calculated_avg_units ?? $bill->units_consumed ?? 50);

            return [
                'bill_id' => $bill->id,
                'ca_number' => $bill->ca_number,
                'consumer_name' => $bill->consumer_name,
                'meter_number' => $bill->meter_no,
                'billing_basis' => $bill->billing_basis ?: 'Normal(OK)',
                'address' => $bill->address,
                'previous_reading' => $prev,
                'working_reading' => $working,
                'smart_average_kwh' => $avgUnits,
                'suggested_reading' => $working ?? ($prev > 0 ? $prev + $avgUnits : 0),
                'review_status' => $bill->review_status ?? 'unsubmitted',
                'remark' => $bill->remark,
            ];
        });

        return response()->json([
            'success' => true,
            'mru_code' => $mru?->code,
            'cycle' => sprintf('%s-%d', strtoupper(date('M', mktime(0, 0, 0, $month, 1))), $year),
            'cycle_month' => $month,
            'cycle_year' => $year,
            'total_in_queue' => $totalInQueue,
            'returned_count' => $formatted->count(),
            'consumers' => $formatted,
        ]);
    }

    /**
     * Update a consumer's status or reading after processing in NBPDCL app.
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'ca_number' => 'nullable|string',
            'bill_id' => 'nullable|integer',
            'status' => 'required|string',
            'working_reading' => 'nullable|numeric',
            'remark' => 'nullable|string',
            'reading_source' => 'nullable|string',
        ]);

        /** @var User $user */
        $user = $request->user();
        $userId = $user->id;

        $billId = $request->input('bill_id');
        $caNumber = $request->input('ca_number');

        if (! $billId && ! $caNumber) {
            return response()->json([
                'success' => false,
                'message' => 'Either ca_number or bill_id must be provided.',
            ], 422);
        }

        $bill = null;
        if ($billId) {
            $bill = BillRecord::where('user_id', $userId)->where('id', $billId)->first();
        } elseif ($caNumber) {
            // Find latest bill record for this CA
            $bill = BillRecord::where('user_id', $userId)
                ->where('ca_number', $caNumber)
                ->orderByDesc('billing_year')
                ->orderByDesc('billing_month')
                ->first();
        }

        if (! $bill) {
            return response()->json([
                'success' => false,
                'message' => 'Bill record not found for the specified consumer.',
            ], 404);
        }

        $newStatus = trim($request->input('status'));
        $workingReading = $request->input('working_reading');
        $remark = $request->input('remark');
        $source = $request->input('reading_source', 'automation_adb');

        $updatedBill = DB::transaction(function () use ($userId, $bill, $newStatus, $workingReading, $remark, $source) {
            $bill->review_status = $newStatus;

            if ($remark !== null) {
                $bill->remark = $remark;
            }

            if ($workingReading !== null && is_numeric($workingReading)) {
                $bill->working_reading = (string) $workingReading;
                $bill->reading_source = $source;

                $prev = is_numeric($bill->previous_reading) ? (int) $bill->previous_reading : 0;
                if ($prev <= 0) {
                    $consumer = ConsumerAccount::where('user_id', $userId)->where('ca_number', $bill->ca_number)->first();
                    $resolved = $this->smartAverageService->resolvePreviousReading($bill->ca_number, $bill->billing_month, $bill->billing_year, $bill, $consumer, null);
                    if (! empty($resolved['reading'])) {
                        $prev = (int) $resolved['reading'];
                        $bill->previous_reading = $prev;
                    }
                }

                if ($prev > 0 && (int) $workingReading >= $prev) {
                    $bill->units_consumed = (int) $workingReading - $prev;
                    $bill->calculated_avg_units = $bill->units_consumed;
                }

                // Record into MeterReadingHistory table
                try {
                    $isSubmitted = (strtolower($newStatus) === 'submitted');
                    $this->meterHistoryService->recordFromWorkingReading(
                        $bill,
                        (string) $workingReading,
                        $bill->units_consumed ? (int) $bill->units_consumed : null,
                        $isSubmitted
                    );
                } catch (\Throwable $e) {
                    Log::warning("API Automation MeterReadingHistory error: {$e->getMessage()}");
                }

                // Update ConsumerAccount master ledger
                $consumer = ConsumerAccount::where('user_id', $userId)
                    ->where('ca_number', $bill->ca_number)
                    ->first();

                if ($consumer) {
                    $consumer->last_working_reading = (string) $workingReading;
                    $consumer->last_working_month = $bill->billing_month;
                    $consumer->last_working_year = $bill->billing_year;
                    $consumer->save();
                }
            }

            $bill->save();

            // Sync to bill_statuses
            BillStatus::updateOrCreate(
                [
                    'user_id' => $userId,
                    'ca_number' => $bill->ca_number,
                    'billing_month' => $bill->billing_month,
                    'billing_year' => $bill->billing_year,
                ],
                [
                    'status' => $newStatus,
                    'remark' => $bill->remark,
                    'mru_id' => $bill->mru_id,
                ]
            );

            return $bill;
        });

        return response()->json([
            'success' => true,
            'message' => "Consumer {$bill->ca_number} status updated to '{$newStatus}'",
            'data' => [
                'bill_id' => $updatedBill->id,
                'ca_number' => $updatedBill->ca_number,
                'status' => $updatedBill->review_status,
                'working_reading' => $updatedBill->working_reading,
                'units_consumed' => $updatedBill->units_consumed,
                'remark' => $updatedBill->remark,
            ],
        ]);
    }
}
