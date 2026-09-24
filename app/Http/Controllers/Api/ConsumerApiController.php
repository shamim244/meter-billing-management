<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\User;
use App\Services\MeterReadingHistoryService;
use App\Services\SmartAverageCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsumerApiController extends Controller
{
    public function __construct(
        protected MeterReadingHistoryService $meterHistoryService,
        protected SmartAverageCalculationService $smartAverageService,
    ) {}

    /**
     * Search consumers by CA number, name, meter number, or village.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $query = trim($request->input('search', ''));
        $mruId = $request->input('mru_id');

        $consumers = ConsumerAccount::where('user_id', $user->id)
            ->when($mruId, fn ($q) => $q->where('mru_id', $mruId))
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('ca_number', 'like', "%{$query}%")
                        ->orWhere('consumer_name', 'like', "%{$query}%")
                        ->orWhere('meter_no', 'like', "%{$query}%")
                        ->orWhere('address', 'like', "%{$query}%");
                });
            })
            ->paginate(30);

        return response()->json([
            'success' => true,
            'data' => $consumers->items(),
            'current_page' => $consumers->currentPage(),
            'last_page' => $consumers->lastPage(),
            'total' => $consumers->total(),
        ]);
    }

    /**
     * Get detailed information for a single consumer.
     */
    public function show(Request $request, string $caNumber): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $consumer = ConsumerAccount::where('user_id', $user->id)
            ->where('ca_number', $caNumber)
            ->first();

        if (! $consumer) {
            return response()->json([
                'success' => false,
                'message' => "Consumer with CA '{$caNumber}' not found.",
            ], 404);
        }

        $recentBills = BillRecord::where('user_id', $user->id)
            ->where('ca_number', $caNumber)
            ->orderByDesc('billing_year')
            ->orderByDesc('billing_month')
            ->limit(12)
            ->get();

        return response()->json([
            'success' => true,
            'consumer' => $consumer,
            'recent_bills' => $recentBills,
        ]);
    }

    /**
     * Get the 12-month consumption & reading ledger matrix for this consumer.
     */
    public function history(Request $request, string $caNumber): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $matrix = $this->meterHistoryService->getConsumerMonthlyMatrix($caNumber, $user->id);

        return response()->json([
            'success' => true,
            'ca_number' => $caNumber,
            'months' => $matrix,
        ]);
    }

    /**
     * Update working reading for a single consumer.
     */
    public function updateReading(Request $request): JsonResponse
    {
        $request->validate([
            'ca_number' => 'required|string',
            'working_reading' => 'required|numeric',
            'status' => 'nullable|string',
            'remark' => 'nullable|string',
            'source' => 'nullable|string',
        ]);

        /** @var User $user */
        $user = $request->user();
        $ca = trim($request->input('ca_number'));
        $workingReading = $request->input('working_reading');
        $status = $request->input('status', 'Submitted');
        $remark = $request->input('remark');
        $source = $request->input('source', 'api');

        $bill = BillRecord::where('user_id', $user->id)
            ->where('ca_number', $ca)
            ->orderByDesc('billing_year')
            ->orderByDesc('billing_month')
            ->first();

        if (! $bill) {
            return response()->json([
                'success' => false,
                'message' => "No active bill found for consumer {$ca}.",
            ], 404);
        }

        DB::transaction(function () use ($user, $bill, $workingReading, $status, $remark, $source, $ca) {
            $bill->working_reading = (string) $workingReading;
            $bill->reading_source = $source;
            if ($status) {
                $bill->review_status = $status;
            }
            if ($remark !== null) {
                $bill->remark = $remark;
            }

            $prev = is_numeric($bill->previous_reading) ? (int) $bill->previous_reading : 0;
            if ($prev <= 0) {
                $consumer = ConsumerAccount::where('user_id', $user->id)->where('ca_number', $ca)->first();
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

            $bill->save();

            // Record to MeterReadingHistory
            try {
                $isSubmitted = (strtolower($status) === 'submitted');
                $this->meterHistoryService->recordFromWorkingReading(
                    $bill,
                    (string) $workingReading,
                    $bill->units_consumed ? (int) $bill->units_consumed : null,
                    $isSubmitted
                );
            } catch (\Throwable $e) {
                Log::warning("ConsumerApiController history error: {$e->getMessage()}");
            }

            // Update master consumer ledger
            $consumer = ConsumerAccount::where('user_id', $user->id)->where('ca_number', $ca)->first();
            if ($consumer) {
                $consumer->last_working_reading = (string) $workingReading;
                $consumer->last_working_month = $bill->billing_month;
                $consumer->last_working_year = $bill->billing_year;
                $consumer->save();
            }

            // Sync status table
            BillStatus::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'ca_number' => $ca,
                    'billing_month' => $bill->billing_month,
                    'billing_year' => $bill->billing_year,
                ],
                [
                    'status' => $status,
                    'remark' => $bill->remark,
                    'mru_id' => $bill->mru_id,
                ]
            );
        });

        return response()->json([
            'success' => true,
            'message' => "Consumer {$ca} reading updated successfully.",
            'data' => [
                'ca_number' => $ca,
                'working_reading' => $bill->fresh()->working_reading,
                'units_consumed' => $bill->fresh()->units_consumed,
                'status' => $bill->fresh()->review_status,
            ],
        ]);
    }
}
