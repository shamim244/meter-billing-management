<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanDuration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPlanDurationController extends Controller
{
    /**
     * Display the dedicated duration management console for a plan.
     */
    public function index(Plan $plan): View
    {
        $plan->load(['durations' => function ($q) {
            $q->orderBy('duration_unit', 'desc')->orderBy('duration_value');
        }]);

        $activeSubscribersCount = $plan->subscriptions()
            ->where('status', 'active')
            ->where('billing_end', '>', now())
            ->count();

        return view('admin.plans.durations', compact('plan', 'activeSubscribersCount'));
    }

    /**
     * Store a newly created duration for a plan.
     */
    public function store(Request $request, Plan $plan): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'duration_unit' => 'required|string|in:day,month',
            'duration_value' => 'required|integer|min:1|max:3650',
            'name' => 'nullable|string|max:100',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'final_price' => 'nullable|numeric|min:0',
            'extra_mru_rate' => 'nullable|numeric|min:0',
            'extra_consumer_rate' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $unit = $validated['duration_unit'];
        $val = (int) $validated['duration_value'];
        $discount = (float) ($validated['discount_percent'] ?? 0.0);

        // Check if duration already exists for this unit + value
        $exists = $plan->durations()
            ->where('duration_unit', $unit)
            ->where('duration_value', $val)
            ->exists();

        if ($exists) {
            $msg = "A {$val}-" . ($unit === 'day' ? 'day' : 'month') . " duration already exists for this plan.";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg)->withInput();
        }

        // Calculate final price if not explicitly provided
        $baseMonthlyPrice = (float) $plan->base_price;
        if (!isset($validated['final_price']) || $validated['final_price'] === '') {
            if ($unit === 'day') {
                $finalPrice = ($baseMonthlyPrice / 30) * $val * (1 - ($discount / 100));
            } else {
                $finalPrice = $baseMonthlyPrice * $val * (1 - ($discount / 100));
            }
        } else {
            $finalPrice = (float) $validated['final_price'];
        }

        $months = $unit === 'month' ? $val : max(1, (int)ceil($val / 30));

        $duration = $plan->durations()->create([
            'duration_unit' => $unit,
            'duration_value' => $val,
            'duration_months' => $months,
            'name' => !empty($validated['name']) ? trim($validated['name']) : null,
            'discount_percent' => $discount,
            'final_price' => max(0.0, $finalPrice),
            'extra_mru_rate' => !empty($validated['extra_mru_rate']) ? (float)$validated['extra_mru_rate'] : null,
            'extra_consumer_rate' => !empty($validated['extra_consumer_rate']) ? (float)$validated['extra_consumer_rate'] : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $successMsg = "Duration '{$duration->formatted_duration}' (₹" . number_format($duration->final_price, 2) . ") created successfully.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg, 'duration' => $duration]);
        }

        return redirect()->route('admin.plans.durations.index', $plan)->with('success', $successMsg);
    }

    /**
     * Update an existing duration.
     */
    public function update(Request $request, Plan $plan, PlanDuration $duration): RedirectResponse|JsonResponse
    {
        if ($duration->plan_id !== $plan->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'final_price' => 'required|numeric|min:0',
            'extra_mru_rate' => 'nullable|numeric|min:0',
            'extra_consumer_rate' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $duration->update([
            'name' => !empty($validated['name']) ? trim($validated['name']) : null,
            'discount_percent' => (float) ($validated['discount_percent'] ?? 0.0),
            'final_price' => (float) $validated['final_price'],
            'extra_mru_rate' => !empty($validated['extra_mru_rate']) ? (float)$validated['extra_mru_rate'] : null,
            'extra_consumer_rate' => !empty($validated['extra_consumer_rate']) ? (float)$validated['extra_consumer_rate'] : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $successMsg = "Duration '{$duration->formatted_duration}' updated successfully.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg, 'duration' => $duration->fresh()]);
        }

        return redirect()->route('admin.plans.durations.index', $plan)->with('success', $successMsg);
    }

    /**
     * 1-Click Toggle active status.
     */
    public function toggleActive(Plan $plan, PlanDuration $duration): RedirectResponse|JsonResponse
    {
        if ($duration->plan_id !== $plan->id) {
            abort(404);
        }

        // If disabling, ensure at least one other active duration remains
        if ($duration->is_active) {
            $otherActiveCount = $plan->durations()->where('id', '!=', $duration->id)->where('is_active', true)->count();
            if ($otherActiveCount === 0) {
                $errorMsg = "Cannot disable the only active duration. At least 1 active duration is required.";
                if (request()->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }
                return redirect()->back()->with('error', $errorMsg);
            }
        }

        $duration->update(['is_active' => !$duration->is_active]);

        $stateText = $duration->is_active ? 'enabled' : 'disabled';
        $successMsg = "Duration '{$duration->formatted_duration}' is now {$stateText}.";

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg, 'is_active' => $duration->is_active]);
        }

        return redirect()->back()->with('success', $successMsg);
    }

    /**
     * Delete a duration.
     */
    public function destroy(Plan $plan, PlanDuration $duration): RedirectResponse|JsonResponse
    {
        if ($duration->plan_id !== $plan->id) {
            abort(404);
        }

        // Ensure at least 1 duration remains on the plan
        $remainingCount = $plan->durations()->where('id', '!=', $duration->id)->count();
        if ($remainingCount === 0) {
            $errorMsg = "Cannot delete the last remaining duration. A plan must have at least 1 duration.";
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }
            return redirect()->back()->with('error', $errorMsg);
        }

        $name = $duration->formatted_duration;
        $duration->delete();

        $successMsg = "Duration '{$name}' deleted successfully.";

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg]);
        }

        return redirect()->route('admin.plans.durations.index', $plan)->with('success', $successMsg);
    }
}
