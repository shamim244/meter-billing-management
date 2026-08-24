<?php

namespace App\Services\Billing;

use App\Enums\DebitResult;
use App\Events\RenewalFailedInsufficientBalanceEvent;
use App\Events\SubscriptionEnteredGracePeriodEvent;
use App\Events\SubscriptionReactivatedEvent;
use App\Events\SubscriptionRenewalDueEvent;
use App\Events\SubscriptionSuspendedEvent;
use App\Models\AgentSubscription;
use App\Models\Plan;
use App\Models\RenewalAttempt;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Plan\RenewalService;
use App\Services\Wallet\WalletService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubscriptionLifecycleService
{
    public function __construct(
        protected WalletService $walletService,
        protected RenewalService $renewalService
    ) {}

    /**
     * Resolve effective grace period days for a subscription or plan.
     * Priority: Plan override -> Platform SystemSetting -> config default (3).
     */
    public function resolveGracePeriodDays(?Plan $plan = null): int
    {
        if ($plan && $plan->grace_period_days !== null) {
            return (int) $plan->grace_period_days;
        }

        return (int) SystemSetting::get(
            'billing_default_grace_period_days',
            config('billing.default_grace_period_days', 3)
        );
    }

    /**
     * Transition a subscription to RENEWAL_DUE state.
     * If resolved grace_period_days is 0, skips straight to SUSPENDED per PRD Section 3.
     */
    public function transitionToRenewalDue(AgentSubscription|int $subscription): AgentSubscription
    {
        $sub = $subscription instanceof AgentSubscription ? $subscription : AgentSubscription::findOrFail($subscription);
        $graceDays = $this->resolveGracePeriodDays($sub->plan);

        // If grace period is 0 days, skip straight to suspended
        if ($graceDays === 0) {
            $sub->update([
                'lifecycle_status' => 'renewal_due',
                'grace_period_days' => 0,
                'grace_period_ends_at' => now(),
                'last_state_change_at' => now(),
            ]);

            return $this->transitionToSuspended($sub);
        }

        $baseDate = $sub->billing_end && $sub->billing_end > now()->subDays(30)
            ? $sub->billing_end
            : now();

        $graceEndsAt = (clone $baseDate)->addDays($graceDays);

        $sub->update([
            'lifecycle_status' => 'renewal_due',
            'grace_period_days' => $graceDays,
            'grace_period_ends_at' => $graceEndsAt,
            'last_state_change_at' => now(),
        ]);

        event(new SubscriptionRenewalDueEvent($sub));

        return $sub->fresh();
    }

    /**
     * Transition a subscription to GRACE_PERIOD state (stronger warning + countdown).
     */
    public function transitionToGracePeriod(AgentSubscription|int $subscription): AgentSubscription
    {
        $sub = $subscription instanceof AgentSubscription ? $subscription : AgentSubscription::findOrFail($subscription);

        $sub->update([
            'lifecycle_status' => 'grace_period',
            'last_state_change_at' => now(),
        ]);

        event(new SubscriptionEnteredGracePeriodEvent($sub));

        return $sub->fresh();
    }

    /**
     * Transition a subscription to SUSPENDED (read-only mode).
     */
    public function transitionToSuspended(AgentSubscription|int $subscription): AgentSubscription
    {
        $sub = $subscription instanceof AgentSubscription ? $subscription : AgentSubscription::findOrFail($subscription);

        $sub->update([
            'lifecycle_status' => 'suspended',
            'suspended_at' => now(),
            'last_state_change_at' => now(),
        ]);

        event(new SubscriptionSuspendedEvent($sub));

        return $sub->fresh();
    }

    /**
     * Reactivate a subscription (returns to ACTIVE).
     * Used both for successful renewals and admin manual state overrides.
     */
    public function reactivate(
        AgentSubscription|int $subscription,
        ?string $adminReason = null,
        ?User $admin = null
    ): AgentSubscription {
        $sub = $subscription instanceof AgentSubscription ? $subscription : AgentSubscription::findOrFail($subscription);

        if ($admin && empty(trim((string)$adminReason))) {
            throw new InvalidArgumentException('A mandatory reason must be logged when an Admin manually overrides/reactivates a subscription.');
        }

        $sub->update([
            'lifecycle_status' => 'active',
            'status' => 'active',
            'suspended_at' => null,
            'grace_period_ends_at' => null,
            'last_state_change_at' => now(),
        ]);

        event(new SubscriptionReactivatedEvent($sub, $adminReason, $admin));

        return $sub->fresh();
    }

    /**
     * Daily lifecycle processor scheduled job.
     * Executes lifecycle state transitions and auto-renewals.
     */
    public function runDailyLifecycleProcessor(): array
    {
        $stats = [
            'moved_to_renewal_due' => 0,
            'auto_renewal_success' => 0,
            'auto_renewal_failed' => 0,
            'moved_to_suspended' => 0,
        ];

        // 1. Find subscriptions where billing_end has passed and status is still active
        $expiredActive = AgentSubscription::where('billing_end', '<=', now())
            ->where('lifecycle_status', 'active')
            ->get();

        foreach ($expiredActive as $sub) {
            $this->transitionToRenewalDue($sub);
            $stats['moved_to_renewal_due']++;
        }

        // 2. Find subscriptions in renewal_due or grace_period where auto_renewal_enabled is true
        $autoRenewable = AgentSubscription::whereIn('lifecycle_status', ['renewal_due', 'grace_period'])
            ->where('auto_renewal_enabled', true)
            ->with('user')
            ->get();

        foreach ($autoRenewable as $sub) {
            if (!$sub->user) {
                continue;
            }

            $summary = $this->renewalService->calculateRenewalSummary($sub->user);
            $amountDue = (float) ($summary['total_with_extra_mrus'] ?? $sub->base_price_paid);

            $debitResult = $this->walletService->debit(
                user: $sub->user,
                amount: $amountDue,
                source: 'subscription_auto_renewal',
                referenceType: 'subscription',
                referenceId: (string) $sub->id,
                description: "Automatic Subscription Renewal ({$sub->plan?->name})"
            );

            if ($debitResult === DebitResult::SUCCESS) {
                // Extend billing period
                $newStart = $sub->billing_end && $sub->billing_end > now() ? $sub->billing_end : now();
                $newEnd = $sub->calculateNewEnd($newStart);

                $sub->update([
                    'billing_start' => $newStart,
                    'billing_end' => $newEnd,
                ]);

                $this->reactivate($sub);

                // Fetch latest transaction id
                $txId = $sub->user->wallet?->transactions()->latest('id')->value('id');

                RenewalAttempt::create([
                    'agent_subscription_id' => $sub->id,
                    'user_id' => $sub->user_id,
                    'attempt_type' => 'auto',
                    'amount_charged' => $amountDue,
                    'wallet_transaction_id' => $txId ? (string) $txId : null,
                    'status' => 'success',
                    'attempted_at' => now(),
                ]);

                $stats['auto_renewal_success']++;
            } else {
                // INSUFFICIENT_BALANCE or WALLET_FROZEN:
                // PRD Invariant: DO NOT attempt PG mandate! Leave subscription where it is.
                RenewalAttempt::create([
                    'agent_subscription_id' => $sub->id,
                    'user_id' => $sub->user_id,
                    'attempt_type' => 'auto',
                    'amount_charged' => $amountDue,
                    'wallet_transaction_id' => null,
                    'status' => $debitResult === DebitResult::WALLET_FROZEN ? 'wallet_frozen' : 'insufficient_balance',
                    'failure_reason' => $debitResult === DebitResult::WALLET_FROZEN ? 'Agent wallet is frozen.' : 'Insufficient wallet balance for auto-renewal.',
                    'attempted_at' => now(),
                ]);

                event(new RenewalFailedInsufficientBalanceEvent($sub, $amountDue));
                $stats['auto_renewal_failed']++;
            }
        }

        // 3. Find subscriptions in renewal_due or grace_period where grace_period_ends_at has expired
        $expiredGrace = AgentSubscription::whereIn('lifecycle_status', ['renewal_due', 'grace_period'])
            ->where('grace_period_ends_at', '<=', now())
            ->get();

        foreach ($expiredGrace as $sub) {
            $this->transitionToSuspended($sub);
            $stats['moved_to_suspended']++;
        }

        return $stats;
    }
}
