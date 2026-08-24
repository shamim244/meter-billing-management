<?php

namespace App\Services\Billing;

use App\Enums\DebitResult;
use App\Events\PlanDowngradedEvent;
use App\Events\PlanUpgradedEvent;
use App\Models\AgentSubscription;
use App\Models\Mru;
use App\Models\Plan;
use App\Models\PlanUpgradeLog;
use App\Services\Plan\MruQuotaService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PlanChangeService
{
    public function __construct(
        protected WalletService $walletService,
        protected MruQuotaService $mruQuotaService
    ) {}

    /**
     * Calculate day-based proration for upgrading or downgrading a plan.
     * Single shared implementation per PRD sections 5.2 and 5.4.
     */
    public function calculateProration(
        AgentSubscription|int $subscription,
        Plan|int $newPlan,
        mixed $durationMonths = null
    ): array {
        $sub = $subscription instanceof AgentSubscription ? $subscription : AgentSubscription::findOrFail($subscription);
        $targetPlan = $newPlan instanceof Plan ? $newPlan : Plan::findOrFail($newPlan);

        if ($durationMonths instanceof \App\Models\PlanDuration) {
            $duration = (int) $durationMonths->duration_months;
            $targetDuration = $durationMonths;
        } elseif (is_numeric($durationMonths)) {
            $duration = (int) $durationMonths;
            $targetDuration = $targetPlan->durations()->where('duration_months', $duration)->first();
        } else {
            $duration = (int) $sub->duration_months;
            $targetDuration = $targetPlan->durations()->where('duration_months', $duration)->first();
        }

        $oldPlanPrice = (float) $sub->base_price_paid;

        // Target duration price
        $newPlanPrice = $targetDuration
            ? (float) $targetDuration->final_price
            : round((float) $targetPlan->base_price * $duration, 2);

        $start = $sub->billing_start ? \Carbon\Carbon::parse($sub->billing_start) : now();
        $end = $sub->billing_end ? \Carbon\Carbon::parse($sub->billing_end) : now()->addMonths($duration);

        $totalDaysInCycle = max(1, (int) round($start->floatDiffInDays($end)));
        $daysRemaining = $end > now() ? max(1, (int) round(now()->floatDiffInDays($end))) : 0;

        $oldPlanCredit = round($oldPlanPrice * ($daysRemaining / $totalDaysInCycle), 2);
        $newPlanCost = round($newPlanPrice * ($daysRemaining / $totalDaysInCycle), 2);
        $amountDue = round($newPlanCost - $oldPlanCredit, 2);

        return [
            'old_plan_price' => $oldPlanPrice,
            'new_plan_price' => $newPlanPrice,
            'total_days_in_cycle' => $totalDaysInCycle,
            'days_remaining' => $daysRemaining,
            'old_plan_credit' => $oldPlanCredit,
            'new_plan_cost' => $newPlanCost,
            'amount_due' => $amountDue, // Positive = upgrade pay-gate; Negative = downgrade credit
            'is_upgrade' => $amountDue > 0,
            'is_downgrade' => $amountDue < 0,
            'target_duration' => $targetDuration,
        ];
    }

    /**
     * Mid-cycle Plan Upgrade flow.
     * PRD Section 5.1 - 5.3: Debits wallet, updates snapshot, and auto-unlocks eligible MRUs.
     */
    public function upgradePlan(AgentSubscription|int $subscription, Plan|int $newPlan, mixed $duration = null): array
    {
        $sub = $subscription instanceof AgentSubscription ? $subscription : AgentSubscription::findOrFail($subscription);
        $targetPlan = $newPlan instanceof Plan ? $newPlan : Plan::findOrFail($newPlan);
        $oldPlan = $sub->plan ?? Plan::withTrashed()->find($sub->plan_id);

        $proration = $this->calculateProration($sub, $targetPlan, $duration);
        $amountDue = max(0.00, $proration['amount_due']);
        $user = $sub->user;

        $txId = null;
        if ($amountDue > 0) {
            $debitResult = $this->walletService->debit(
                user: $user,
                amount: $amountDue,
                source: 'plan_upgrade_proration',
                referenceType: 'plan',
                referenceId: (string) $targetPlan->id,
                description: "Mid-cycle Upgrade to {$targetPlan->name} (Prorated difference)"
            );

            if ($debitResult === DebitResult::INSUFFICIENT_BALANCE) {
                return [
                    'success' => false,
                    'requires_topup' => true,
                    'amount_due' => $amountDue,
                    'message' => "Insufficient wallet balance. You need ₹{$amountDue} to upgrade.",
                ];
            }

            if ($debitResult === DebitResult::WALLET_FROZEN) {
                return [
                    'success' => false,
                    'wallet_frozen' => true,
                    'message' => 'Wallet is currently frozen. Please contact support.',
                ];
            }

            $txId = $user->wallet?->transactions()->latest('id')->value('id');
        }

        return DB::transaction(function () use ($sub, $targetPlan, $oldPlan, $proration, $amountDue, $txId, $user) {
            $targetDuration = $proration['target_duration'];

            // 1. Update subscription snapshot
            $sub->update([
                'plan_id' => $targetPlan->id,
                'included_mrus_locked' => $targetPlan->included_mrus,
                'included_consumers_locked' => $targetPlan->included_consumers,
                'extra_mru_rate_locked' => $targetDuration?->extra_mru_rate ?? $targetPlan->extra_mru_rate,
                'extra_consumer_rate_locked' => $targetDuration?->extra_consumer_rate ?? $targetPlan->extra_consumer_rate,
                'base_price_paid' => $proration['new_plan_price'],
                'last_state_change_at' => now(),
            ]);

            // 2. Auto-unlock evaluation (PRD Section 5.3)
            $activeMrusCount = Mru::where('user_id', $user->id)->where('status', 'active')->count();
            $availableNewSlots = max(0, $targetPlan->included_mrus - $activeMrusCount);
            $autoUnlockedMrus = [];

            if ($availableNewSlots > 0) {
                $lockedMrus = Mru::where('user_id', $user->id)
                    ->where('status', 'locked')
                    ->orderBy('id')
                    ->take($availableNewSlots)
                    ->get();

                foreach ($lockedMrus as $lockedMru) {
                    $unlockRes = $this->mruQuotaService->unlockMru($lockedMru, payOverage: false);
                    if ($unlockRes['success']) {
                        $autoUnlockedMrus[] = $lockedMru->fresh();
                    }
                }
            }

            // 3. Log upgrade
            $log = PlanUpgradeLog::create([
                'agent_subscription_id' => $sub->id,
                'user_id' => $sub->user_id,
                'from_plan_id' => $oldPlan?->id,
                'to_plan_id' => $targetPlan->id,
                'action_type' => 'upgrade',
                'old_plan_credit' => $proration['old_plan_credit'],
                'new_plan_cost' => $proration['new_plan_cost'],
                'amount_charged' => $amountDue,
                'wallet_transaction_id' => $txId ? (string) $txId : null,
                'days_remaining_at_upgrade' => $proration['days_remaining'],
                'total_days_in_cycle' => $proration['total_days_in_cycle'],
                'notes' => 'Mid-cycle upgrade with day-based proration.',
            ]);

            event(new PlanUpgradedEvent($sub->fresh(), $oldPlan, $targetPlan, $log));

            return [
                'success' => true,
                'amount_charged' => $amountDue,
                'subscription' => $sub->fresh(),
                'auto_unlocked_mrus' => $autoUnlockedMrus,
                'log' => $log,
            ];
        });
    }

    /**
     * Check if Agent is eligible for mid-cycle downgrade.
     * PRD Section 5.4: Active MRU count must be <= new plan's included MRUs.
     */
    public function checkDowngradeEligibility(AgentSubscription|int $subscription, Plan|int $newPlan): array
    {
        $sub = $subscription instanceof AgentSubscription ? $subscription : AgentSubscription::findOrFail($subscription);
        $targetPlan = $newPlan instanceof Plan ? $newPlan : Plan::findOrFail($newPlan);

        $activeMrus = Mru::where('user_id', $sub->user_id)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        $activeCount = $activeMrus->count();
        $newQuota = (int) $targetPlan->included_mrus;

        if ($activeCount <= $newQuota) {
            return [
                'eligible' => true,
                'active_mrus_count' => $activeCount,
                'new_plan_quota' => $newQuota,
            ];
        }

        return [
            'eligible' => false,
            'active_mrus_count' => $activeCount,
            'new_plan_quota' => $newQuota,
            'excess_mrus' => $activeCount - $newQuota,
            'active_mrus' => $activeMrus,
            'message' => "You have {$activeCount} active MRUs, but the target plan only includes {$newQuota}. Lock or delete at least " . ($activeCount - $newQuota) . " MRU(s) to proceed.",
        ];
    }

    /**
     * Mid-cycle Plan Downgrade flow.
     * PRD Section 5.4: Server-side eligibility check, credits wallet, updates snapshot.
     */
    public function downgradePlan(AgentSubscription|int $subscription, Plan|int $newPlan, mixed $duration = null): array
    {
        $sub = $subscription instanceof AgentSubscription ? $subscription : AgentSubscription::findOrFail($subscription);
        $targetPlan = $newPlan instanceof Plan ? $newPlan : Plan::findOrFail($newPlan);
        $oldPlan = $sub->plan ?? Plan::withTrashed()->find($sub->plan_id);

        // Server-side eligibility validation
        $eligibility = $this->checkDowngradeEligibility($sub, $targetPlan);
        if (!$eligibility['eligible']) {
            throw new InvalidArgumentException($eligibility['message']);
        }

        $proration = $this->calculateProration($sub, $targetPlan, $duration);
        $creditAmount = max(0.00, round($proration['old_plan_credit'] - $proration['new_plan_cost'], 2));
        $user = $sub->user;

        $txId = null;
        if ($creditAmount > 0) {
            // Credit wallet (PRD Invariant: Downgrade CREDITS wallet)
            $this->walletService->credit(
                user: $user,
                amount: $creditAmount,
                source: 'plan_downgrade_credit',
                referenceType: 'plan',
                referenceId: (string) $targetPlan->id,
                description: "Mid-cycle Downgrade to {$targetPlan->name} (Prorated credit)"
            );

            $txId = $user->wallet?->transactions()->latest('id')->value('id');
        }

        return DB::transaction(function () use ($sub, $targetPlan, $oldPlan, $proration, $creditAmount, $txId) {
            $targetDuration = $proration['target_duration'];

            // 1. Update subscription snapshot
            $sub->update([
                'plan_id' => $targetPlan->id,
                'included_mrus_locked' => $targetPlan->included_mrus,
                'included_consumers_locked' => $targetPlan->included_consumers,
                'extra_mru_rate_locked' => $targetDuration?->extra_mru_rate ?? $targetPlan->extra_mru_rate,
                'extra_consumer_rate_locked' => $targetDuration?->extra_consumer_rate ?? $targetPlan->extra_consumer_rate,
                'base_price_paid' => $proration['new_plan_price'],
                'last_state_change_at' => now(),
            ]);

            // 2. Log downgrade
            $log = PlanUpgradeLog::create([
                'agent_subscription_id' => $sub->id,
                'user_id' => $sub->user_id,
                'from_plan_id' => $oldPlan?->id,
                'to_plan_id' => $targetPlan->id,
                'action_type' => 'downgrade',
                'old_plan_credit' => $proration['old_plan_credit'],
                'new_plan_cost' => $proration['new_plan_cost'],
                'amount_charged' => -$creditAmount,
                'wallet_transaction_id' => $txId ? (string) $txId : null,
                'days_remaining_at_upgrade' => $proration['days_remaining'],
                'total_days_in_cycle' => $proration['total_days_in_cycle'],
                'notes' => 'Mid-cycle downgrade with day-based proration credit.',
            ]);

            event(new PlanDowngradedEvent($sub->fresh(), $oldPlan, $targetPlan, $log));

            return [
                'success' => true,
                'amount_credited' => $creditAmount,
                'subscription' => $sub->fresh(),
                'log' => $log,
            ];
        });
    }
}
