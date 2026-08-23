<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentSubscription;
use App\Models\Plan;
use App\Models\PlanUpgradeLog;
use App\Models\RenewalAttempt;
use App\Models\SystemSetting;
use App\Services\Billing\SubscriptionLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionLifecycleService $lifecycleService
    ) {}

    /**
     * Display all agent subscriptions with lifecycle states.
     */
    public function index(Request $request): View
    {
        $query = AgentSubscription::with(['user', 'plan'])->orderByDesc('id');

        if ($request->filled('lifecycle_status')) {
            $query->where('lifecycle_status', $request->input('lifecycle_status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->paginate(20);

        $counts = [
            'total' => AgentSubscription::count(),
            'active' => AgentSubscription::where('lifecycle_status', 'active')->count(),
            'renewal_due' => AgentSubscription::where('lifecycle_status', 'renewal_due')->count(),
            'grace_period' => AgentSubscription::where('lifecycle_status', 'grace_period')->count(),
            'suspended' => AgentSubscription::where('lifecycle_status', 'suspended')->count(),
        ];

        $defaultGraceDays = (int) SystemSetting::get(
            'billing_default_grace_period_days',
            config('billing.default_grace_period_days', 3)
        );

        return view('admin.subscriptions.index', compact('subscriptions', 'counts', 'defaultGraceDays'));
    }

    /**
     * Manually override the lifecycle status of an agent subscription.
     * PRD: Mandatory reason required for admin manual override.
     */
    public function stateOverride(Request $request, AgentSubscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'target_status' => 'required|string|in:active,renewal_due,grace_period,suspended',
            'reason' => 'required|string|min:5|max:500',
        ]);

        $targetStatus = $validated['target_status'];
        $reason = $validated['reason'];
        $admin = $request->user();

        switch ($targetStatus) {
            case 'active':
                $this->lifecycleService->reactivate($subscription, $reason, $admin);
                break;
            case 'renewal_due':
                $this->lifecycleService->transitionToRenewalDue($subscription);
                break;
            case 'grace_period':
                $this->lifecycleService->transitionToGracePeriod($subscription);
                break;
            case 'suspended':
                $this->lifecycleService->transitionToSuspended($subscription);
                break;
        }

        return back()->with('success', "Subscription state for agent '{$subscription->user?->name}' updated to " . strtoupper($targetStatus) . ".");
    }

    /**
     * View renewal attempts history across all agents.
     */
    public function renewalAttempts(Request $request): View
    {
        $query = RenewalAttempt::with(['subscription.plan', 'user'])->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('attempt_type')) {
            $query->where('attempt_type', $request->input('attempt_type'));
        }

        $attempts = $query->paginate(25);

        $counts = [
            'total' => RenewalAttempt::count(),
            'success' => RenewalAttempt::where('status', 'success')->count(),
            'insufficient_balance' => RenewalAttempt::where('status', 'insufficient_balance')->count(),
            'wallet_frozen' => RenewalAttempt::where('status', 'wallet_frozen')->count(),
        ];

        return view('admin.subscriptions.renewal_attempts', compact('attempts', 'counts'));
    }

    /**
     * View plan upgrade and downgrade proration audit logs.
     */
    public function upgradeLogs(Request $request): View
    {
        $query = PlanUpgradeLog::with(['subscription', 'user', 'fromPlan', 'toPlan'])->orderByDesc('id');

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        $logs = $query->paginate(25);

        $totalUpgradeRevenue = PlanUpgradeLog::where('action_type', 'upgrade')->sum('amount_charged');
        $totalDowngradeCredits = abs(PlanUpgradeLog::where('action_type', 'downgrade')->sum('amount_charged'));

        return view('admin.subscriptions.upgrade_logs', compact('logs', 'totalUpgradeRevenue', 'totalDowngradeCredits'));
    }

    /**
     * Update platform-wide default grace period setting.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_grace_period_days' => 'required|integer|min:0|max:90',
        ]);

        SystemSetting::set('billing_default_grace_period_days', $validated['default_grace_period_days']);

        return back()->with('success', 'Platform default grace period updated successfully.');
    }
}
