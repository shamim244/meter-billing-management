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
            $targetDuration = $durationMonths;
            $duration = (int) ($targetDuration->duration_value ?: $targetDuration->duration_months ?: 1);
        } elseif (is_numeric($durationMonths)) {
            $duration = (int) $durationMonths;
            $targetDuration = $targetPlan->durations()->where('duration_months', $duration)->first()
                ?? $targetPlan->durations()->where('duration_value', $duration)->first();
        } else {
            $targetDuration = $targetPlan->durations()->first();
            $duration = (int) ($targetDuration?->duration_value ?: 1);
        }

        $oldPlanPrice = (float) $sub->base_price_paid;
        $newPlanPrice = $targetDuration
            ? (float) $targetDuration->final_price
            : round((float) $targetPlan->base_price * $duration, 2);

        $start = $sub->billing_start ? \Carbon\Carbon::parse($sub->billing_start) : now();
        $end = $sub->billing_end 
            ? \Carbon\Carbon::parse($sub->billing_end) 
            : ($targetDuration ? $targetDuration->calculateBillingEnd($start) : ($sub->calculateNewEnd($start) ?? now()->addMonth()));

        $totalDaysInCycle = max(1, (int) round($start->floatDiffInDays($end)));
        $daysRemaining = $end > now() ? max(0, (int) round(now()->floatDiffInDays($end))) : 0;

        $oldPlanCredit = round($oldPlanPrice * ($daysRemaining / $totalDaysInCycle), 2);
        $newPlanCost = $newPlanPrice;
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
     * PRD Section 5.1 - 5.3: Debits wallet, creates new subscription snapshot, and auto-unlocks eligible MRUs.
     */
    public function upgradePlan(AgentSubscription|int $subscription, Plan|int $newPlan, mixed $duration = null): array
    {
        $sub = $subscription instanceof AgentSubscription ? $subscription : AgentSubscription::findOrFail($subscription);
        $targetPlan = $newPlan instanceof Plan ? $newPlan : Plan::findOrFail($newPlan);
        $oldPlan = $sub->plan ?? Plan::withTrashed()->find($sub->plan_id);

        $proration = $this->calculateProration($sub, $targetPlan, $duration);
        $amountDue = max(0.00, $proration['amount_due']);
        $user = $sub->user;

        return DB::transaction(function () use ($sub, $targetPlan, $oldPlan, $proration, $amountDue, $user) {
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

            $targetDuration = $proration['target_duration'];

            // 1. Mark previous subscription as upgraded
            $sub->update([
                'status' => 'upgraded',
                'last_state_change_at' => now(),
            ]);

            // 2. Create fresh active subscription starting NOW with the full target duration
            $start = now();
            $end = $targetDuration ? $targetDuration->calculateBillingEnd($start) : $start->copy()->addMonth();

            $durationValue = $targetDuration ? ($targetDuration->duration_value ?: $targetDuration->duration_months ?: 1) : 1;
            $durationUnit = $targetDuration ? ($targetDuration->duration_unit ?: 'month') : 'month';
            $durationMonths = $durationUnit === 'month' ? $durationValue : max(1, (int)ceil($durationValue / 30));

            $newSubscription = AgentSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $targetPlan->id,
                'duration_unit' => $durationUnit,
                'duration_value' => $durationValue,
                'duration_months' => $durationMonths,
                'base_price_paid' => $proration['new_plan_price'],
                'included_mrus_locked' => $targetPlan->included_mrus,
                'included_consumers_locked' => $targetPlan->included_consumers,
                'extra_mru_rate_locked' => $targetDuration?->extra_mru_rate ?? $targetPlan->extra_mru_rate,
                'extra_consumer_rate_locked' => $targetDuration?->extra_consumer_rate ?? $targetPlan->extra_consumer_rate,
                'billing_start' => $start,
                'billing_end' => $end,
                'status' => 'active',
                'lifecycle_status' => 'active',
            ]);

            // 3. Auto-unlock evaluation (PRD Section 5.3)
            $activeMrusCount = Mru::where('user_id', $user->id)->where('status', 'active')->count();
            $availableNewSlots = max(0, $targetPlan->included_mrus - $activeMrusCount);
            $unlockedMrus = collect();

            if ($availableNewSlots > 0) {
                $lockedMrus = Mru::where('user_id', $user->id)
                    ->where('status', 'locked')
                    ->orderBy('locked_at', 'asc')
                    ->take($availableNewSlots)
                    ->get();

                foreach ($lockedMrus as $mru) {
                    $mru->update([
                        'status' => 'active',
                        'locked_at' => null,
                        'lock_reason' => null,
                    ]);
                    $unlockedMrus->push($mru);
                    event(new MruUnlockedEvent($mru, $user, 'Auto-unlocked upon Plan Upgrade'));
                }
            }

            // 4. Log upgrade
            $log = PlanUpgradeLog::create([
                'agent_subscription_id' => $newSubscription->id,
                'user_id' => $newSubscription->user_id,
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

            event(new PlanUpgradedEvent($newSubscription, $oldPlan, $targetPlan, $log));

            return [
                'success' => true,
                'amount_charged' => $amountDue,
                'subscription' => $newSubscription,
                'auto_unlocked_mrus' => $unlockedMrus->pluck('id')->toArray(),
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
     * PRD Section 5.4: Server-side eligibility check, credits wallet, creates new subscription snapshot.
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

        return DB::transaction(function () use ($sub, $targetPlan, $oldPlan, $proration, $creditAmount, $user) {
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

            $targetDuration = $proration['target_duration'];

            // 1. Mark previous subscription as downgraded
            $sub->update([
                'status' => 'downgraded',
                'last_state_change_at' => now(),
            ]);

            // 2. Create fresh active subscription starting NOW with the full target duration
            $start = now();
            $end = $targetDuration ? $targetDuration->calculateBillingEnd($start) : $start->copy()->addMonth();

            $durationValue = $targetDuration ? ($targetDuration->duration_value ?: $targetDuration->duration_months ?: 1) : 1;
            $durationUnit = $targetDuration ? ($targetDuration->duration_unit ?: 'month') : 'month';
            $durationMonths = $durationUnit === 'month' ? $durationValue : max(1, (int)ceil($durationValue / 30));

            $newSubscription = AgentSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $targetPlan->id,
                'duration_unit' => $durationUnit,
                'duration_value' => $durationValue,
                'duration_months' => $durationMonths,
                'base_price_paid' => $proration['new_plan_price'],
                'included_mrus_locked' => $targetPlan->included_mrus,
                'included_consumers_locked' => $targetPlan->included_consumers,
                'extra_mru_rate_locked' => $targetDuration?->extra_mru_rate ?? $targetPlan->extra_mru_rate,
                'extra_consumer_rate_locked' => $targetDuration?->extra_consumer_rate ?? $targetPlan->extra_consumer_rate,
                'billing_start' => $start,
                'billing_end' => $end,
                'status' => 'active',
                'lifecycle_status' => 'active',
            ]);

            // 3. Log downgrade
            $log = PlanUpgradeLog::create([
                'agent_subscription_id' => $newSubscription->id,
                'user_id' => $newSubscription->user_id,
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

            event(new PlanDowngradedEvent($newSubscription, $oldPlan, $targetPlan, $log));

            // Refer & Earn: Clawback any pending or paid referral reward tied to the downgraded subscription
            try {
                app(\App\Services\Referral\ReferralService::class)->handleClawback(
                    paymentReferenceType: 'subscription_payment',
                    paymentReferenceId: 'sub_' . $sub->id,
                    reason: "Mid-cycle plan downgrade from {$oldPlan?->name} to {$targetPlan->name}"
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("[PlanDowngrade] Referral clawback error for sub #{$sub->id}: " . $e->getMessage());
            }

            return [
                'success' => true,
                'amount_credited' => $creditAmount,
                'subscription' => $newSubscription,
                'log' => $log,
            ];
        });
    }
}
