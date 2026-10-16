<?php

namespace App\Http\Controllers;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\MeterInspection;
use App\Models\Mru;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Reading\ReadingGapReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReadingGapController extends Controller
{
    public function __construct(
        protected ReadingGapReconciliationService $gapService
    ) {}

    /**
     * Display the dedicated Reading Gap & Field Inspection workspace.
     */
    public function index(Request $request): View
    {
        $userId = Auth::id();
        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        $isAdmin = $currentUser && method_exists($currentUser, 'hasRole') && $currentUser->hasRole('admin');

        $mrus = Mru::query()
            ->when(! $isAdmin && $userId, fn ($q) => $q->where('user_id', $userId))
            ->orderBy('code')
            ->get();

        $selectedCa = $request->query('ca');

        return view('reading-gap.index', [
            'mrus' => $mrus,
            'selectedCa' => $selectedCa,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Get paginated gap feed and KPI summaries for user workspace.
     */
    public function getData(Request $request): JsonResponse
    {
        $userId = Auth::id();
        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        $isAdmin = $currentUser && method_exists($currentUser, 'hasRole') && $currentUser->hasRole('admin');

        $mruId = $request->query('mru_id') ? (int) $request->query('mru_id') : null;
        $tab = $request->query('tab', 'all');
        $search = trim((string) $request->query('search', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(10, (int) $request->query('per_page', 25)));

        // 1. KPI Analytics
        $kpis = $this->gapService->getMruGapAnalytics($isAdmin ? null : $userId, $mruId);

        // 2. Query Consumers in Scope
        $query = ConsumerAccount::query()
            ->when(! $isAdmin && $userId, fn ($q) => $q->where('user_id', $userId))
            ->when($mruId, fn ($q) => $q->where('mru_id', $mruId))
            ->with('mru');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ca_number', 'like', "%{$search}%")
                    ->orWhere('consumer_name', 'like', "%{$search}%")
                    ->orWhere('meter_no', 'like', "%{$search}%");
            });
        }

        $allConsumers = $query->orderBy('ca_number')->get();
        $caNumbers = $allConsumers->pluck('ca_number')->filter()->unique();

        // Latest Bills
        $latestBills = BillRecord::withoutGlobalScopes()
            ->whereIn('ca_number', $caNumbers)
            ->when(! $isAdmin && $userId, fn ($q) => $q->where('user_id', $userId))
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get()
            ->groupBy('ca_number')
            ->map(fn ($group) => $group->first());

        $staleWarningDays = (int) SystemSetting::get('inspection_stale_warning_days', 90);

        // Calculate summaries and apply tab filter
        $evaluated = $allConsumers->map(function ($consumer) use ($latestBills) {
            $latestBill = $latestBills->get($consumer->ca_number);
            $summary = $this->gapService->getGapSummary($consumer, $latestBill);

            return [
                'id' => $consumer->id,
                'ca_number' => $consumer->ca_number,
                'consumer_name' => $consumer->consumer_name ?? 'N/A',
                'meter_no' => $consumer->meter_no ?? 'N/A',
                'tariff_category' => $consumer->tariff_category ?? 'DS1D',
                'mru_code' => $consumer->mru?->code ?? 'N/A',
                'last_physical_reading' => $consumer->last_physical_reading,
                'last_inspected_at' => $consumer->last_inspected_at?->format('d M Y'),
                'days_since_inspection' => $summary['days_since_inspection'] ?? null,
                'is_stale' => $summary['is_stale'] ?? false,
                'gap_status' => $summary['gap_status'] ?? 'uninspected',
                'net_gap' => $summary['net_gap'] ?? 0,
                'has_gap' => $summary['has_gap'] ?? false,
                'safe_ceiling' => $summary['safe_ceiling'] ?? 220,
                'gap_recovery_mode' => $consumer->gap_recovery_mode ?: 'auto',
                'custom_safe_ceiling' => $consumer->custom_safe_ceiling,
                'recommended_working_reading' => $summary['recommended_working_reading'] ?? null,
                'gap_slice_applied' => $summary['gap_slice_applied'] ?? 0,
                'estimated_recovery_cycles' => $summary['estimated_recovery_cycles'] ?? 0,
                'estimated_catchup_cycles' => $summary['estimated_catchup_cycles'] ?? 0,
                'badge_color' => $summary['badge_color'] ?? 'slate',
                'badge_label' => $summary['badge_label'] ?? 'No Inspection',
                'posted_reading' => $latestBill ? ($latestBill->previous_reading ?? $latestBill->current_reading ?? 0) : 0,
            ];
        });

        // Tab Filter
        $filtered = match ($tab) {
            'over_posted' => $evaluated->where('gap_status', 'over_posted'),
            'under_posted' => $evaluated->where('gap_status', 'under_posted'),
            'stale' => $evaluated->where('is_stale', true),
            'uninspected' => $evaluated->where('gap_status', 'uninspected'),
            'in_sync' => $evaluated->where('gap_status', 'in_sync'),
            default => $evaluated,
        };

        $totalFiltered = $filtered->count();
        $paginated = $filtered->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'success' => true,
            'kpis' => $kpis,
            'consumers' => $paginated,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalFiltered,
                'last_page' => (int) ceil($totalFiltered / $perPage),
            ],
        ]);
    }

    /**
     * Export route inspection sheet as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $userId = Auth::id();
        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        $isAdmin = $currentUser && method_exists($currentUser, 'hasRole') && $currentUser->hasRole('admin');

        $mruId = $request->query('mru_id') ? (int) $request->query('mru_id') : null;

        $consumers = ConsumerAccount::query()
            ->when(! $isAdmin && $userId, fn ($q) => $q->where('user_id', $userId))
            ->when($mruId, fn ($q) => $q->where('mru_id', $mruId))
            ->with('mru')
            ->orderBy('ca_number')
            ->get();

        $caNumbers = $consumers->pluck('ca_number')->filter()->unique();
        $latestBills = BillRecord::withoutGlobalScopes()
            ->whereIn('ca_number', $caNumbers)
            ->when(! $isAdmin && $userId, fn ($q) => $q->where('user_id', $userId))
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get()
            ->groupBy('ca_number')
            ->map(fn ($group) => $group->first());

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reading-gap-audit-'.now()->format('Y-m-d').'.csv"',
        ];

        return response()->stream(function () use ($consumers, $latestBills) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'CA Number',
                'Consumer Name',
                'MRU',
                'Tariff',
                'Meter No',
                'Last Physical Reading',
                'Last Inspected Date',
                'Days Since Visit',
                'Latest Posted Reading',
                'Net Gap (kWh)',
                'Status',
                'Recovery Mode',
            ]);

            foreach ($consumers as $consumer) {
                $latestBill = $latestBills->get($consumer->ca_number);
                $summary = $this->gapService->getGapSummary($consumer, $latestBill);
                $posted = $latestBill ? ($latestBill->previous_reading ?? $latestBill->current_reading ?? '') : '';

                fputcsv($handle, [
                    $consumer->ca_number,
                    $consumer->consumer_name ?? '',
                    $consumer->mru?->code ?? '',
                    $consumer->tariff_category ?? '',
                    $consumer->meter_no ?? '',
                    $consumer->last_physical_reading ?? '',
                    $consumer->last_inspected_at ? $consumer->last_inspected_at->format('Y-m-d') : '',
                    $summary['days_since_inspection'] ?? '',
                    $posted,
                    $summary['net_gap'] ?? 0,
                    $summary['gap_status'] ?? 'uninspected',
                    $consumer->gap_recovery_mode ?: 'auto',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Record a meter replacement / change-out event.
     */
    public function recordMeterSwap(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ca_number' => 'required|string',
            'new_meter_no' => 'required|string|max:50',
            'initial_reading' => 'required|numeric|min:0|max:9999999',
            'old_meter_final_reading' => 'nullable|numeric|min:0|max:9999999',
            'notes' => 'nullable|string|max:255',
            'event_date' => 'nullable|date',
        ]);

        $userId = Auth::id();
        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        $isAdmin = $currentUser && method_exists($currentUser, 'hasRole') && $currentUser->hasRole('admin');

        $consumer = ConsumerAccount::withoutGlobalScopes()
            ->where('ca_number', $validated['ca_number'])
            ->first();

        if (! $consumer) {
            return response()->json(['success' => false, 'message' => 'Consumer account not found.'], 404);
        }

        if (! $isAdmin && (int) $consumer->user_id !== (int) $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized consumer.'], 403);
        }

        $date = ! empty($validated['event_date']) ? Carbon::parse($validated['event_date']) : now();

        $inspection = $this->gapService->recordMeterReplacement(
            $consumer,
            $validated['initial_reading'],
            $date,
            $validated['old_meter_final_reading'] ?? null,
            $validated['new_meter_no'],
            $validated['notes'] ?? null,
            $currentUser
        );

        $latestBill = BillRecord::withoutGlobalScopes()
            ->where('ca_number', $consumer->ca_number)
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->first();

        $summary = $this->gapService->getGapSummary($consumer, $latestBill);

        return response()->json([
            'success' => true,
            'message' => 'Meter replacement event logged and baseline reset successfully.',
            'data' => [
                'inspection' => $inspection,
                'gap_summary' => $summary,
            ],
        ]);
    }

    /**
     * Get inspection history for a consumer account.
     */
    public function getInspections(string $ca): JsonResponse
    {
        $userId = Auth::id();
        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        $isAdmin = $currentUser && method_exists($currentUser, 'hasRole') && $currentUser->hasRole('admin');

        $consumer = ConsumerAccount::withoutGlobalScopes()->where('ca_number', $ca)->first();
        if (! $consumer) {
            return response()->json(['success' => false, 'message' => 'Consumer account not found.'], 404);
        }

        if (! $isAdmin && (int) $consumer->user_id !== (int) $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $history = $this->gapService->getInspectionHistory($consumer);

        return response()->json([
            'success' => true,
            'consumer' => [
                'ca_number' => $consumer->ca_number,
                'consumer_name' => $consumer->consumer_name,
                'meter_no' => $consumer->meter_no,
                'last_physical_reading' => $consumer->last_physical_reading,
                'last_inspected_at' => $consumer->last_inspected_at?->format('Y-m-d'),
            ],
            'inspections' => $history,
        ]);
    }

    /**
     * Update an existing physical meter inspection.
     */
    public function updateInspection(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'physical_reading' => 'required|numeric|min:0|max:9999999',
            'inspected_at' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $userId = Auth::id();
        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        $isAdmin = $currentUser && method_exists($currentUser, 'hasRole') && $currentUser->hasRole('admin');

        $inspection = MeterInspection::withoutGlobalScopes()->find($id);
        if (! $inspection) {
            return response()->json(['success' => false, 'message' => 'Inspection record not found.'], 404);
        }

        $consumer = ConsumerAccount::withoutGlobalScopes()->find($inspection->consumer_account_id);
        if (! $consumer) {
            return response()->json(['success' => false, 'message' => 'Consumer account not found.'], 404);
        }

        if (! $isAdmin && (int) $consumer->user_id !== (int) $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $updated = $this->gapService->updatePhysicalInspection($inspection, $validated);

        $latestBill = BillRecord::withoutGlobalScopes()
            ->where('ca_number', $consumer->ca_number)
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->first();

        $summary = $this->gapService->getGapSummary($consumer, $latestBill);

        return response()->json([
            'success' => true,
            'message' => 'Physical inspection updated successfully.',
            'data' => [
                'inspection' => $updated,
                'gap_summary' => $summary,
            ],
        ]);
    }

    /**
     * Delete a single physical meter inspection.
     */
    public function destroyInspection(int $id): JsonResponse
    {
        $userId = Auth::id();
        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        $isAdmin = $currentUser && method_exists($currentUser, 'hasRole') && $currentUser->hasRole('admin');

        $inspection = MeterInspection::withoutGlobalScopes()->find($id);
        if (! $inspection) {
            return response()->json(['success' => false, 'message' => 'Inspection record not found.'], 404);
        }

        $consumer = ConsumerAccount::withoutGlobalScopes()->find($inspection->consumer_account_id);
        if ($consumer && ! $isAdmin && (int) $consumer->user_id !== (int) $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $this->gapService->deletePhysicalInspection($inspection);

        $summary = null;
        if ($consumer) {
            $latestBill = BillRecord::withoutGlobalScopes()
                ->where('ca_number', $consumer->ca_number)
                ->orderBy('billing_year', 'desc')
                ->orderBy('billing_month', 'desc')
                ->first();
            $summary = $this->gapService->getGapSummary($consumer, $latestBill);
        }

        return response()->json([
            'success' => true,
            'message' => 'Physical inspection deleted successfully.',
            'data' => [
                'gap_summary' => $summary,
            ],
        ]);
    }

    /**
     * Bulk delete multiple physical meter inspections.
     */
    public function bulkDestroyInspections(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'ca_number' => 'required|string',
        ]);

        $userId = Auth::id();
        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        $isAdmin = $currentUser && method_exists($currentUser, 'hasRole') && $currentUser->hasRole('admin');

        $consumer = ConsumerAccount::withoutGlobalScopes()->where('ca_number', $validated['ca_number'])->first();
        if (! $consumer) {
            return response()->json(['success' => false, 'message' => 'Consumer account not found.'], 404);
        }

        if (! $isAdmin && (int) $consumer->user_id !== (int) $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $deletedCount = $this->gapService->deleteMultipleInspections($validated['ids'], $consumer);

        $latestBill = BillRecord::withoutGlobalScopes()
            ->where('ca_number', $consumer->ca_number)
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->first();

        $summary = $this->gapService->getGapSummary($consumer, $latestBill);

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} physical inspection(s) removed successfully.",
            'data' => [
                'deleted_count' => $deletedCount,
                'gap_summary' => $summary,
            ],
        ]);
    }

    /**
     * Clear all physical inspections for a consumer and reset cache.
     */
    public function clearAllInspections(string $ca): JsonResponse
    {
        $userId = Auth::id();
        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        $isAdmin = $currentUser && method_exists($currentUser, 'hasRole') && $currentUser->hasRole('admin');

        $consumer = ConsumerAccount::withoutGlobalScopes()->where('ca_number', $ca)->first();
        if (! $consumer) {
            return response()->json(['success' => false, 'message' => 'Consumer account not found.'], 404);
        }

        if (! $isAdmin && (int) $consumer->user_id !== (int) $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $deletedCount = $this->gapService->clearAllInspections($consumer);

        $latestBill = BillRecord::withoutGlobalScopes()
            ->where('ca_number', $consumer->ca_number)
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->first();

        $summary = $this->gapService->getGapSummary($consumer, $latestBill);

        return response()->json([
            'success' => true,
            'message' => 'All physical visits cleared. Consumer status reset to uninspected.',
            'data' => [
                'deleted_count' => $deletedCount,
                'gap_summary' => $summary,
            ],
        ]);
    }
}
