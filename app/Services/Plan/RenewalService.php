<?php

namespace App\Services\Plan;

use App\Enums\DebitResult;
use App\Events\SubscriptionReactivatedEvent;
use App\Models\AgentSubscription;
use App\Models\Mru;
use App\Models\PlanOverageCharge;
use App\Models\RenewalAttempt;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;

class RenewalService
{
    public function __construct(
        protected WalletService $walletService,
        protected MruQuotaService $mruQuotaService
    ) {}

    /**
     * Get renewable subscription for an agent (active, renewal_due, grace_period, or suspended).
     */
    public function getRenewableSubscription(User|int $user): ?AgentSubscription
    {
        $userId = $user instanceof User ? $user->id : $user;
        return AgentSubscription::where('user_id', $userId)
            ->whereIn('lifecycle_status', ['active', 'renewal_due', 'grace_period', 'suspended'])
            ->latest('id')
            ->first() ?? AgentSubscription::where('user_id', $userId)->latest('id')->first();
    }

    /**
     * Calculate renewal summary data for prompt screen.
     * Enforces PRD invariant: Consumer overage is NEVER included in renewal calculation.
     */
    public function calculateRenewalSummary(User|int $user): array
    {
        $userModel = $user instanceof User ? $user : User::findOrFail($user);
        $subscription = $this->getRenewableSubscription($userModel);

        if (!$subscription) {
            return [
                'has_subscription' => false,
                'message' => 'No active subscription found.',
            ];
        }

        $basePrice = (float) $subscription->base_price_paid;
        $includedMrus = (int) $subscription->included_mrus_locked;
        $extraMruRate = (float) $subscription->extra_mru_rate_locked;

        $activeMrus = Mru::where('user_id', $userModel->id)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->get();

        $totalActiveMrus = $activeMrus->count();
        $extraMrusCount = max(0, $totalActiveMrus - $includedMrus);
        $extraMrusTotal = round($extraMrusCount * $extraMruRate, 2);

        $walletBalance = (float) $this->walletService->getBalance($userModel);
        $autolockTimeoutHours = $this->getAutoLockTimeoutHours();

        return [
            'has_subscription' => true,
            'plan_name' => $subscription->plan?->name ?? 'Custom Plan',
            'duration_months' => $subscription->duration_months,
            'base_price' => $basePrice,
            'included_mrus' => $includedMrus,
            'total_active_mrus' => $totalActiveMrus,
            'extra_mrus_count' => $extraMrusCount,
            'extra_mru_rate' => $extraMruRate,
            'extra_mrus_total' => $extraMrusTotal,
            'autolock_timeout_hours' => $autolockTimeoutHours,
            'consumer_overage_amount' => 0.00, // Strictly 0.00 per PRD invariant
            'total_with_extra_mrus' => round($basePrice + $extraMrusTotal, 2),
            'total_without_extra_mrus' => $basePrice,
            'wallet_balance' => $walletBalance,
            'has_sufficient_balance' => $walletBalance >= ($basePrice + $extraMrusTotal),
            'auto_renewal_enabled' => (bool) $subscription->auto_renewal_enabled,
        ];
    }

    /**
     * Process subscription renewal based on Agent's choice.
     */
    public function processRenewal(
        User|int $user,
        bool $includeExtraMrus = true,
        array $selectedMrusToLock = []
    ): array {
        $userModel = $user instanceof User ? $user : User::findOrFail($user);
        $subscription = $this->getRenewableSubscription($userModel);

        if (!$subscription) {
            return [
                'success' => false,
                'message' => 'No active subscription found to renew.',
            ];
        }

        $summary = $this->calculateRenewalSummary($userModel);
        $basePrice = (float) $summary['base_price'];
        $extraMrusTotal = (float) $summary['extra_mrus_total'];
        $extraMrusCount = (int) $summary['extra_mrus_count'];

        $lockedMrusList = [];

        if ($includeExtraMrus) {
            $totalAmount = $basePrice + $extraMrusTotal;

            $debitResult = $this->walletService->debit(
                user: $userModel,
                amount: $totalAmount,
                source: 'subscription_renewal',
                referenceType: 'subscription',
                referenceId: (string) $subscription->id,
                description: "Subscription Renewal ({$summary['plan_name']}) - Including {$extraMrusCount} Extra MRUs"
            );

            if ($debitResult !== DebitResult::SUCCESS) {
                RenewalAttempt::create([
                    'agent_subscription_id' => $subscription->id,
                    'user_id' => $userModel->id,
                    'attempt_type' => 'manual',
                    'amount_charged' => $totalAmount,
                    'wallet_transaction_id' => null,
                    'status' => $debitResult === DebitResult::WALLET_FROZEN ? 'wallet_frozen' : 'insufficient_balance',
                    'failure_reason' => $debitResult === DebitResult::WALLET_FROZEN ? 'Agent wallet is frozen.' : 'Insufficient wallet balance for renewal.',
                    'attempted_at' => now(),
                ]);

                return [
                    'success' => false,
                    'debit_result' => $debitResult->value,
                    'amount_due' => $totalAmount,
                    'message' => 'Insufficient wallet balance for renewal.',
                ];
            }

            $txId = $userModel->wallet?->transactions()->latest('id')->value('id');

            // Record MRU renewal overage charge if applicable
            if ($extraMrusTotal > 0) {
                PlanOverageCharge::create([
                    'user_id' => $userModel->id,
                    'charge_type' => 'mru_renewal',
                    'reference_type' => 'subscription',
                    'reference_id' => (string) $subscription->id,
                    'amount' => $extraMrusTotal,
                    'wallet_transaction_id' => $txId ? (string) $txId : null,
                ]);
            }
        } else {
            // Exclude extra MRUs -> Lock excess MRUs to bring within included quota
            $activeMrus = Mru::where('user_id', $userModel->id)
                ->where('status', 'active')
                ->orderByDesc('id')
                ->get();

            $neededLocks = $extraMrusCount;
            $lockedCount = 0;

            // Lock Agent's explicitly selected MRUs first
            foreach ($selectedMrusToLock as $mruId) {
                if ($lockedCount >= $neededLocks) break;
                $mru = $activeMrus->firstWhere('id', $mruId);
                if ($mru && $mru->status === 'active') {
                    $this->mruQuotaService->lockMru($mru, 'renewal_excluded');
                    $lockedMrusList[] = $mru->name;
                    $lockedCount++;
                }
            }

            // If not enough locked, auto-lock the most recently created active MRUs
            if ($lockedCount < $neededLocks) {
                foreach ($activeMrus as $mru) {
                    if ($lockedCount >= $neededLocks) break;
                    if ($mru->status === 'active') {
                        $this->mruQuotaService->lockMru($mru, 'auto_locked_renewal');
                        $lockedMrusList[] = $mru->name;
                        $lockedCount++;
                    }
                }
            }

            // Debit base renewal amount only
            $debitResult = $this->walletService->debit(
                user: $userModel,
                amount: $basePrice,
                source: 'subscription_renewal',
                referenceType: 'subscription',
                referenceId: (string) $subscription->id,
                description: "Subscription Renewal ({$summary['plan_name']}) - Base quota only"
            );

            if ($debitResult !== DebitResult::SUCCESS) {
                RenewalAttempt::create([
                    'agent_subscription_id' => $subscription->id,
                    'user_id' => $userModel->id,
                    'attempt_type' => 'manual',
                    'amount_charged' => $basePrice,
                    'wallet_transaction_id' => null,
                    'status' => $debitResult === DebitResult::WALLET_FROZEN ? 'wallet_frozen' : 'insufficient_balance',
                    'failure_reason' => $debitResult === DebitResult::WALLET_FROZEN ? 'Agent wallet is frozen.' : 'Insufficient wallet balance for base renewal.',
                    'attempted_at' => now(),
                ]);

                return [
                    'success' => false,
                    'debit_result' => $debitResult->value,
                    'amount_due' => $basePrice,
                    'message' => 'Insufficient wallet balance for base renewal.',
                ];
            }

            $txId = $userModel->wallet?->transactions()->latest('id')->value('id');
        }

        // Extend subscription billing period & reactivate
        $newStart = $subscription->billing_end > now() ? $subscription->billing_end : now();
        $newEnd = (clone $newStart)->addMonths($subscription->duration_months);

        $subscription->update([
            'billing_start' => $newStart,
            'billing_end' => $newEnd,
            'status' => 'active',
            'lifecycle_status' => 'active',
            'suspended_at' => null,
            'grace_period_ends_at' => null,
            'last_state_change_at' => now(),
        ]);

        $chargedAmount = $includeExtraMrus ? ($basePrice + $extraMrusTotal) : $basePrice;

        RenewalAttempt::create([
            'agent_subscription_id' => $subscription->id,
            'user_id' => $userModel->id,
            'attempt_type' => 'manual',
            'amount_charged' => $chargedAmount,
            'wallet_transaction_id' => $txId ? (string) $txId : null,
            'status' => 'success',
            'attempted_at' => now(),
        ]);

        event(new SubscriptionReactivatedEvent($subscription->fresh(), 'Manual Renewal Success'));

        return [
            'success' => true,
            'amount_charged' => $chargedAmount,
            'locked_mrus' => $lockedMrusList,
            'subscription' => $subscription->fresh(),
        ];
    }

    /**
     * Toggle auto-renewal setting for an agent subscription.
     */
    public function toggleAutoRenewal(AgentSubscription|int $subscription, bool $enabled): bool
    {
        $sub = $subscription instanceof AgentSubscription ? $subscription : AgentSubscription::findOrFail($subscription);
        $sub->update(['auto_renewal_enabled' => $enabled]);
        return (bool) $sub->auto_renewal_enabled;
    }

    /**
     * Get configured timeout hours before auto-locking extra MRU at renewal.
     */
    public function getAutoLockTimeoutHours(): int
    {
        return (int) \App\Models\SystemSetting::get(
            'plan_mru_autolock_timeout_hours',
            config('plans.mru_autolock_timeout_hours', 72)
        );
    }
}
