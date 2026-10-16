<?php

namespace App\Http\Controllers;

use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\FieldDeskAction;
use App\Models\MeterInspection;
use App\Models\User;
use App\Services\MeterReadingHistoryService;
use App\Services\Reading\ReadingGapReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ConsumerController extends Controller
{
    public function __construct(
        protected ReadingGapReconciliationService $gapService,
        protected MeterReadingHistoryService $meterHistoryService
    ) {}

    /**
     * Display the 360-degree dedicated consumer workspace.
     */
    public function show(string $ca, Request $request): View
    {
        $userId = Auth::id();
        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        $isAdmin = $currentUser && method_exists($currentUser, 'hasRole') && $currentUser->hasRole('admin');

        $account = ConsumerAccount::withoutGlobalScopes()
            ->with('mru')
            ->where('ca_number', $ca)
            ->when(! $isAdmin && $userId, fn ($q) => $q->where('user_id', $userId))
            ->firstOrFail();

        // 1. Bill Records & Review Statuses
        $bills = BillRecord::withoutGlobalScopes()
            ->with('mru')
            ->where('ca_number', $ca)
            ->when(! $isAdmin && $userId, fn ($q) => $q->where('user_id', $userId))
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get();

        $statuses = BillStatus::withoutGlobalScopes()
            ->where('ca_number', $ca)
            ->when(! $isAdmin && $userId, fn ($q) => $q->where('user_id', $userId))
            ->get()
            ->keyBy(fn ($s) => "{$s->billing_year}_{$s->billing_month}");

        // 2. Reading Gap & Reconciliation Summary
        $latestBill = $bills->first();
        $gapSummary = $this->gapService->getGapSummary($account, $latestBill);

        // 3. Historical Physical Inspections
        $inspections = MeterInspection::withoutGlobalScopes()
            ->where('consumer_account_id', $account->id)
            ->with('user:id,name,email')
            ->orderByDesc('inspected_at')
            ->orderByDesc('id')
            ->get();

        // 4. FieldDesk Follow-ups & Grievance Actions
        $fieldDeskActions = FieldDeskAction::withoutGlobalScopes()
            ->where('consumer_account_id', $account->id)
            ->with(['category', 'activities'])
            ->orderByDesc('target_date')
            ->get();

        // 5. Monthly Consumption Matrix
        $meterMatrix = $this->meterHistoryService->getConsumerMonthlyMatrix(
            $isAdmin ? $account->user_id : $userId,
            $ca
        );

        // 6. 360-Degree Lifetime Analytics
        $totalBilledAmount = (float) $bills->sum('total_amount');
        $totalUnits = (int) $bills->sum('units_consumed');
        $avgMonthlyUnits = $bills->count() > 0 ? (int) round($totalUnits / $bills->count()) : 0;
        $activeCommitmentsCount = $fieldDeskActions->whereIn('status', ['open', 'rescheduled'])->count();

        $stats = [
            'total_bills' => $bills->count(),
            'total_billed_amount' => $totalBilledAmount,
            'total_units' => $totalUnits,
            'avg_monthly_units' => $avgMonthlyUnits,
            'active_commitments_count' => $activeCommitmentsCount,
            'inspections_count' => $inspections->count(),
            'latest_bill_amount' => (float) ($latestBill?->total_amount ?? 0),
            'latest_bill_month' => $latestBill ? $latestBill->billing_month.'/'.$latestBill->billing_year : 'N/A',
        ];

        $isReadingGapRoute = $request->routeIs('reading-gap.consumer');
        $defaultTab = $request->query('tab', $isReadingGapRoute ? 'gap' : 'overview');
        $fromHub = $request->query('from', $isReadingGapRoute ? 'reading-gap' : null);

        return view('consumers.show', compact(
            'account',
            'bills',
            'statuses',
            'gapSummary',
            'inspections',
            'fieldDeskActions',
            'meterMatrix',
            'stats',
            'defaultTab',
            'fromHub'
        ));
    }

    /**
     * Update consumer technical and profile information.
     */
    public function update(string $ca, Request $request): JsonResponse|RedirectResponse
    {
        $userId = Auth::id();
        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        $isAdmin = $currentUser && method_exists($currentUser, 'hasRole') && $currentUser->hasRole('admin');

        $account = ConsumerAccount::withoutGlobalScopes()
            ->where('ca_number', $ca)
            ->when(! $isAdmin && $userId, fn ($q) => $q->where('user_id', $userId))
            ->firstOrFail();

        $validated = $request->validate([
            'consumer_name' => 'nullable|string|max:150',
            'father_name' => 'nullable|string|max:150',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'meter_no' => 'nullable|string|max:50',
            'tariff_category' => 'nullable|string|max:50',
            'billing_basis' => 'nullable|string|max:10',
            'custom_safe_ceiling' => 'nullable|integer|min:0|max:5000',
            'gap_recovery_mode' => 'nullable|string|in:hold_exact,hold_advance,amortize_safe,auto',
        ]);

        $account->update(array_filter($validated, fn ($val) => $val !== null));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Consumer details updated successfully.',
                'account' => $account,
            ]);
        }

        return redirect()->route('consumers.show', ['ca' => $ca])
            ->with('status', 'Consumer details updated successfully.');
    }
}
