<?php

namespace App\Services\Plan;

use App\Enums\DebitResult;
use App\Events\ConsumerOverageChargedEvent;
use App\Models\AgentSubscription;
use App\Models\BillingCycle;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\PlanOverageCharge;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;

class ConsumerQuotaService
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Get active subscription for a user.
     */
    public function getActiveSubscription(User|int $user): ?AgentSubscription
    {
        $userId = $user instanceof User ? $user->id : $user;
        return AgentSubscription::where('user_id', $userId)
            ->where('status', 'active')
            ->where('billing_end', '>', now())
            ->latest('id')
            ->first();
    }

    /**
     * Get remaining included consumer quota for an agent for a given billing period (month/year).
     */
    public function getRemainingConsumerQuotaForPeriod(User|int $user, int $month, int $year): int
    {
        $userId = $user instanceof User ? $user->id : $user;
        $subscription = $this->getActiveSubscription($userId);

        if (!$subscription) {
            return 0;
        }

        $totalIncluded = (int) $subscription->included_consumers_locked;

        $usedIncluded = (int) BillingCycle::where('user_id', $userId)
            ->where('cycle_month', $month)
            ->where('cycle_year', $year)
            ->sum('included_quota_used');

        return max(0, $totalIncluded - $usedIncluded);
    }

    /**
     * Consume consumer quota when creating a billing cycle for an MRU.
     * Enforces consumer pay-gate if linked count exceeds remaining quota for this period.
     */
    public function consumeConsumerQuota(
        User|int $user,
        Mru $mru,
        int $month,
        int $year,
        int $consumerCount,
        bool $payOverage = false
    ): array {
        $userModel = $user instanceof User ? $user : User::findOrFail($user);
        $subscription = $this->getActiveSubscription($userModel);

        if (!$subscription) {
            return [
                'allowed' => false,
                'requires_subscription' => true,
                'message' => 'Active plan subscription required to create a billing cycle.',
            ];
        }

        // Check if MRU itself is locked
        if ($mru->isLocked()) {
            return [
                'allowed' => false,
                'mru_locked' => true,
                'message' => 'MRU is locked. Please unlock the MRU before creating a billing cycle.',
            ];
        }

        // Check if cycle already exists
        $existingCycle = BillingCycle::where('mru_id', $mru->id)
            ->where('cycle_month', $month)
            ->where('cycle_year', $year)
            ->first();

        if ($existingCycle) {
            return [
                'allowed' => true,
                'cycle' => $existingCycle,
                'already_exists' => true,
            ];
        }

        $availableQuota = $this->getRemainingConsumerQuotaForPeriod($userModel, $month, $year);

        // Case 1: All consumers fit within remaining included quota
        if ($consumerCount <= $availableQuota) {
            $cycle = BillingCycle::create([
                'mru_id' => $mru->id,
                'user_id' => $userModel->id,
                'cycle_month' => $month,
                'cycle_year' => $year,
                'consumer_count_at_creation' => $consumerCount,
                'included_quota_used' => $consumerCount,
                'extra_consumer_count' => 0,
                'extra_consumer_charge' => 0.00,
                'status' => 'active',
            ]);

            return [
                'allowed' => true,
                'cycle' => $cycle,
                'overage_charge' => null,
            ];
        }

        // Case 2: Exceeds remaining included quota - Consumer Pay-Gate
        $includedUsed = max(0, $availableQuota);
        $extraCount = $consumerCount - $includedUsed;
        $extraRate = (float) $subscription->extra_consumer_rate_locked;
        $extraCharge = round($extraCount * $extraRate, 2);

        if (!$payOverage) {
            return [
                'allowed' => false,
                'requires_payment' => true,
                'consumer_count' => $consumerCount,
                'remaining_quota' => $availableQuota,
                'extra_count' => $extraCount,
                'rate_per_consumer' => $extraRate,
                'amount_due' => $extraCharge,
                'reason' => "This cycle has {$consumerCount} consumers, but you have {$availableQuota} remaining in your quota. Pay ₹" . number_format($extraCharge, 2) . " to continue.",
            ];
        }

        // Process wallet debit for extra consumers
        $debitResult = $this->walletService->debit(
            user: $userModel,
            amount: $extraCharge,
            source: 'consumer_cycle',
            referenceType: 'mru',
            referenceId: (string) $mru->id,
            description: "Extra consumer quota for MRU {$mru->name} ({$month}/{$year}) [{$extraCount} consumers]"
        );

        if ($debitResult !== DebitResult::SUCCESS) {
            return [
                'allowed' => false,
                'requires_payment' => true,
                'debit_result' => $debitResult->value,
                'amount_due' => $extraCharge,
                'message' => $debitResult === DebitResult::WALLET_FROZEN
                    ? 'Wallet is frozen. Please contact administrator.'
                    : 'Insufficient wallet balance to cover extra consumer overage.',
            ];
        }

        $latestTx = $userModel->transactions()->latest('id')->first();

        $cycle = DB::transaction(function () use ($userModel, $mru, $month, $year, $consumerCount, $includedUsed, $extraCount, $extraCharge, $latestTx) {
            $cycle = BillingCycle::create([
                'mru_id' => $mru->id,
                'user_id' => $userModel->id,
                'cycle_month' => $month,
                'cycle_year' => $year,
                'consumer_count_at_creation' => $consumerCount,
                'included_quota_used' => $includedUsed,
                'extra_consumer_count' => $extraCount,
                'extra_consumer_charge' => $extraCharge,
                'status' => 'active',
            ]);

            $charge = PlanOverageCharge::create([
                'user_id' => $userModel->id,
                'charge_type' => 'consumer_cycle',
                'reference_type' => 'billing_cycle',
                'reference_id' => (string) $cycle->id,
                'amount' => $extraCharge,
                'wallet_transaction_id' => $latestTx?->id ? (string)$latestTx->id : null,
                'created_at' => now(),
            ]);

            event(new ConsumerOverageChargedEvent($userModel, $cycle, $extraCount, $extraCharge, $charge));

            return $cycle;
        });

        return [
            'allowed' => true,
            'cycle' => $cycle,
            'extra_consumer_count' => $extraCount,
            'extra_consumer_charge' => $extraCharge,
        ];
    }

    /**
     * Explicit sync action: recalculates cycle consumer count and charges overage if new consumers were added.
     * Quota does NOT auto-update on passive add/remove; only through this method!
     */
    public function syncCycleConsumerCount(BillingCycle $cycle, bool $payOverage = false): array
    {
        $userModel = $cycle->user;
        $subscription = $this->getActiveSubscription($userModel);

        $currentCount = ConsumerAccount::where('mru_id', $cycle->mru_id)
            ->where('status', 'active')
            ->count();

        $oldCount = (int) $cycle->consumer_count_at_creation;

        if ($currentCount <= $oldCount) {
            // Count did not increase
            return [
                'synced' => true,
                'old_count' => $oldCount,
                'current_count' => $currentCount,
                'additional_charge' => 0.00,
                'cycle' => $cycle,
            ];
        }

        $diff = $currentCount - $oldCount;
        $extraRate = $subscription ? (float) $subscription->extra_consumer_rate_locked : 0.0;
        $additionalCharge = round($diff * $extraRate, 2);

        if (!$payOverage) {
            return [
                'synced' => false,
                'requires_payment' => true,
                'old_count' => $oldCount,
                'current_count' => $currentCount,
                'diff' => $diff,
                'amount_due' => $additionalCharge,
                'reason' => "Consumer count increased by {$diff}. Pay ₹" . number_format($additionalCharge, 2) . " to sync.",
            ];
        }

        // Process wallet debit
        if ($additionalCharge > 0) {
            $debitResult = $this->walletService->debit(
                user: $userModel,
                amount: $additionalCharge,
                source: 'consumer_cycle_sync',
                referenceType: 'billing_cycle',
                referenceId: (string) $cycle->id,
                description: "Additional consumer sync for cycle #{$cycle->id} (+{$diff} consumers)"
            );

            if ($debitResult !== DebitResult::SUCCESS) {
                return [
                    'synced' => false,
                    'requires_payment' => true,
                    'debit_result' => $debitResult->value,
                    'amount_due' => $additionalCharge,
                    'message' => 'Insufficient wallet balance for cycle sync.',
                ];
            }

            $latestTx = $userModel->transactions()->latest('id')->first();

            PlanOverageCharge::create([
                'user_id' => $userModel->id,
                'charge_type' => 'consumer_cycle',
                'reference_type' => 'billing_cycle',
                'reference_id' => (string) $cycle->id,
                'amount' => $additionalCharge,
                'wallet_transaction_id' => $latestTx?->id ? (string)$latestTx->id : null,
                'created_at' => now(),
            ]);
        }

        $cycle->update([
            'consumer_count_at_creation' => $currentCount,
            'extra_consumer_count' => $cycle->extra_consumer_count + $diff,
            'extra_consumer_charge' => $cycle->extra_consumer_charge + $additionalCharge,
        ]);

        return [
            'synced' => true,
            'old_count' => $oldCount,
            'current_count' => $currentCount,
            'diff' => $diff,
            'additional_charge' => $additionalCharge,
            'cycle' => $cycle->fresh(),
        ];
    }
}
