<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentSubscription;
use App\Models\Mru;
use App\Models\Plan;
use App\Models\PlanOverageCharge;
use App\Models\User;
use App\Services\Plan\MruQuotaService;
use App\Services\Plan\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPlanController extends Controller
{
    public function __construct(
        protected PlanService $planService,
        protected MruQuotaService $mruQuotaService
    ) {}

    /**
     * Display all subscription plans.
     */
    public function index(): View
    {
        $plans = Plan::withTrashed()
            ->withCount(['subscriptions' => function ($q) {
                $q->where('status', 'active')->where('billing_end', '>', now());
            }])
            ->with('durations')
            ->orderBy('id')
            ->get();

        $totalSubscribers = AgentSubscription::where('status', 'active')
            ->where('billing_end', '>', now())
            ->count();

        $totalOverageRevenue = PlanOverageCharge::sum('amount');
        $lockedMrusCount = Mru::where('status', 'locked')->count();

        return view('admin.plans.index', compact(
            'plans',
            'totalSubscribers',
            'totalOverageRevenue',
            'lockedMrusCount'
        ));
    }

    /**
     * Show form to create a new plan.
     */
    public function create(): View
    {
        return view('admin.plans.create');
    }

    /**
     * Store a new plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'included_mrus' => 'required|integer|min:0',
            'included_consumers' => 'required|integer|min:0',
            'extra_mru_rate' => 'required|numeric|min:0',
            'extra_consumer_rate' => 'required|numeric|min:0',
            'grace_period_days' => 'nullable|integer|min:0|max:90',
            'is_active' => 'nullable|boolean',
            'durations' => 'nullable|array',
            'durations.*.id' => 'nullable|integer',
            'durations.*.duration_unit' => 'nullable|string|in:day,month',
            'durations.*.duration_value' => 'nullable|integer|min:1|max:3650',
            'durations.*.duration_months' => 'nullable|integer|min:1|max:3650',
            'durations.*.name' => 'nullable|string|max:100',
            'durations.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'durations.*.final_price' => 'nullable|numeric|min:0',
            'durations.*.extra_mru_rate' => 'nullable|numeric|min:0',
            'durations.*.extra_consumer_rate' => 'nullable|numeric|min:0',
            'durations.*.is_active' => 'nullable|boolean',
        ]);

        $plan = $this->planService->createPlan($validated, $request->input('durations', []));

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan '{$plan->name}' created successfully.");
    }

    /**
     * Show form to edit an existing plan.
     */
    public function edit(Plan $plan): View
    {
        $plan->load('durations');
        return view('admin.plans.edit', compact('plan'));
    }

    /**
     * Update an existing plan.
     */
    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'base_price' => 'nullable|numeric|min:0',
            'included_mrus' => 'required|integer|min:0',
            'included_consumers' => 'required|integer|min:0',
            'extra_mru_rate' => 'required|numeric|min:0',
            'extra_consumer_rate' => 'required|numeric|min:0',
            'grace_period_days' => 'nullable|integer|min:0|max:90',
            'is_active' => 'nullable|boolean',
            'durations' => 'nullable|array',
            'durations.*.id' => 'nullable|integer',
            'durations.*.duration_unit' => 'nullable|string|in:day,month',
            'durations.*.duration_value' => 'nullable|integer|min:1|max:3650',
            'durations.*.duration_months' => 'nullable|integer|min:1|max:3650',
            'durations.*.name' => 'nullable|string|max:100',
            'durations.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'durations.*.final_price' => 'nullable|numeric|min:0',
            'durations.*.extra_mru_rate' => 'nullable|numeric|min:0',
            'durations.*.extra_consumer_rate' => 'nullable|numeric|min:0',
            'durations.*.is_active' => 'nullable|boolean',
        ]);

        $this->planService->updatePlan($plan, $validated, $request->input('durations', []));

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan '{$plan->name}' updated successfully. Note: Existing subscriber snapshots remain locked.");
    }

    /**
     * Soft delete a plan.
     */
    public function destroy(Plan $plan): RedirectResponse
    {
        $this->planService->softDeletePlan($plan);

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan '{$plan->name}' deactivated / soft-deleted. Existing subscribers remain active.");
    }

    /**
     * Force delete a plan.
     */
    public function forceDelete(Request $request, Plan $plan): RedirectResponse
    {
        $request->validate([
            'migration_plan_id' => 'nullable|integer|exists:plans,id',
            'force' => 'nullable|boolean',
        ]);

        try {
            $this->planService->forceDeletePlan(
                $plan,
                $request->input('migration_plan_id'),
                $request->boolean('force', false)
            );

            return redirect()->route('admin.plans.index')
                ->with('success', "Plan permanently deleted.");
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('admin.plans.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * View all Agents subscribed to a plan.
     */
    public function agents(Plan $plan): View
    {
        $subscriptions = AgentSubscription::where('plan_id', $plan->id)
            ->with(['user.mrus'])
            ->orderByDesc('id')
            ->paginate(20);

        $allPlans = Plan::where('id', '!=', $plan->id)->active()->get();

        return view('admin.plans.agents', compact('plan', 'subscriptions', 'allPlans'));
    }

    /**
     * Manually migrate an agent to another plan.
     */
    public function migrateAgent(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'target_plan_id' => 'required|integer|exists:plans,id',
            'duration_months' => 'required|integer|in:1,2,3,6,12',
        ]);

        $user = User::findOrFail($request->input('user_id'));
        $targetPlan = Plan::findOrFail($request->input('target_plan_id'));
        $durationMonths = (int) $request->input('duration_months');

        $this->planService->migrateAgent($user, $targetPlan, $durationMonths);

        return back()->with('success', "Agent '{$user->name}' successfully migrated to {$targetPlan->name} ({$durationMonths} Months).");
    }

    /**
     * View overage charge audit log across all agents.
     */
    public function overageCharges(Request $request): View
    {
        $query = PlanOverageCharge::with('user')->orderByDesc('id');

        if ($request->filled('charge_type')) {
            $query->where('charge_type', $request->input('charge_type'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $charges = $query->paginate(25);
        $totalAmount = PlanOverageCharge::sum('amount');
        $mruCreationTotal = PlanOverageCharge::where('charge_type', 'mru_creation')->sum('amount');
        $mruRenewalTotal = PlanOverageCharge::where('charge_type', 'mru_renewal')->sum('amount');
        $consumerTotal = PlanOverageCharge::where('charge_type', 'consumer_cycle')->sum('amount');

        return view('admin.plans.overage_charges', compact(
            'charges',
            'totalAmount',
            'mruCreationTotal',
            'mruRenewalTotal',
            'consumerTotal'
        ));
    }

    /**
     * Manually unlock an agent's locked MRU (support tool).
     */
    public function unlockMru(Mru $mru): RedirectResponse
    {
        $result = $this->mruQuotaService->unlockMru($mru, payOverage: false);

        if ($result['success']) {
            return back()->with('success', "MRU '{$mru->name}' manually unlocked by admin.");
        }

        return back()->with('error', 'Failed to unlock MRU.');
    }
}
