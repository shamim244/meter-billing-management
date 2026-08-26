<?php

namespace App\Services\Plan;

use App\Enums\DebitResult;
use App\Events\MruLockedEvent;
use App\Events\MruOverageChargedEvent;
use App\Events\MruUnlockedEvent;
use App\Models\AgentSubscription;
use App\Models\Mru;
use App\Models\PlanOverageCharge;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Support\Facades\DB;

class MruQuotaService
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Get active subscription for a user (permits active, renewal_due, and grace_period).
     */
    public function getActiveSubscription(User|int $user): ?AgentSubscription
    {
        $userId = $user instanceof User ? $user->id : $user;
        return AgentSubscription::where('user_id', $userId)
            ->where('status', 'active')
            ->whereIn('lifecycle_status', ['active', 'renewal_due', 'grace_period'])
            ->latest('id')
            ->first();
    }

    /**
     * Check how many included MRU slots are available for an agent.
     */
    public function checkMruQuotaAvailable(User|int $user, ?int $excludeMruId = null): int
    {
        $userId = $user instanceof User ? $user->id : $user;
        $subscription = $this->getActiveSubscription($userId);

        if (!$subscription) {
            return 0;
        }

        $query = Mru::where('user_id', $userId)
            ->where('status', 'active')
            ->where('is_over_quota', false);

        if ($excludeMruId) {
            $query->where('id', '!=', $excludeMruId);
        }

        $activeMrusCount = $query->count();

        return max(0, $subscription->included_mrus_locked - $activeMrusCount);
    }

    /**
     * Consume an MRU slot upon MRU creation.
     * If within included quota, allowed immediately.
     * If over quota, blocks and requires pay-gate wallet deduction.
     */
    public function consumeMruSlot(User|int $user, Mru $mru, bool $payOverage = false): array
    {
        $userModel = $user instanceof User ? $user : User::findOrFail($user);
        $subscription = $this->getActiveSubscription($userModel);

        if (!$subscription) {
            return [
                'allowed' => false,
                'requires_subscription' => true,
                'message' => 'Active plan subscription required to create an MRU.',
            ];
        }

        $availableSlots = $this->checkMruQuotaAvailable($userModel, $mru->id);

        // Case 1: Within included plan quota
        if ($availableSlots > 0) {
            $mru->update([
                'status' => 'active',
                'is_over_quota' => false,
            ]);

            return [
                'allowed' => true,
                'is_over_quota' => false,
                'charge' => null,
            ];
        }

        // Case 2: Over quota - pay-gate flow
        $extraRate = (float) $subscription->extra_mru_rate_locked;

        if (!$payOverage) {
            return [
                'allowed' => false,
                'requires_payment' => true,
                'amount_due' => $extraRate,
                'reason' => "This exceeds your plan's MRU limit ({$subscription->included_mrus_locked}). Pay ₹" . number_format($extraRate, 2) . " to continue.",
            ];
        }

        // Process wallet deduction for extra MRU inside atomic transaction
        return DB::transaction(function () use ($userModel, $extraRate, $mru) {
            $debitResult = $this->walletService->debit(
                user: $userModel,
                amount: $extraRate,
                source: 'mru_creation',
                referenceType: 'mru',
                referenceId: (string) $mru->id,
                description: "Extra MRU creation fee ({$mru->name})"
            );

            if ($debitResult !== DebitResult::SUCCESS) {
                return [
                    'allowed' => false,
                    'requires_payment' => true,
                    'debit_result' => $debitResult->value,
                    'amount_due' => $extraRate,
                    'message' => $debitResult === DebitResult::WALLET_FROZEN
                        ? 'Wallet is frozen. Please contact administrator.'
                        : 'Insufficient wallet balance. Please top up to proceed.',
                ];
            }

            // Record overage charge
            $latestTx = $userModel->transactions()->latest('id')->first();

            $charge = PlanOverageCharge::create([
                'user_id' => $userModel->id,
                'charge_type' => 'mru_creation',
                'reference_type' => 'mru',
                'reference_id' => (string) $mru->id,
                'amount' => $extraRate,
                'wallet_transaction_id' => $latestTx?->id ? (string)$latestTx->id : null,
                'created_at' => now(),
            ]);

            $mru->update([
                'status' => 'active',
                'is_over_quota' => true,
            ]);

            event(new MruOverageChargedEvent($userModel, $mru, $extraRate, $charge));

            return [
                'allowed' => true,
                'is_over_quota' => true,
                'charge' => $charge,
            ];
        });
    }

    /**
     * Release MRU slot on deletion.
     */
    public function releaseMruSlot(User|int $user, Mru $mru): void
    {
        // Deleting an MRU automatically frees its standing quota slot
    }

    /**
     * Lock an MRU (due to unpaid overage or admin action).
     */
    public function lockMru(Mru $mru, string $reason = 'over_quota_unpaid'): Mru
    {
        $mru->update([
            'status' => 'locked',
            'locked_reason' => $reason,
            'locked_at' => now(),
        ]);

        event(new MruLockedEvent($mru, $reason));

        return $mru;
    }

    /**
     * Unlock a locked MRU.
     */
    public function unlockMru(Mru $mru, bool $payOverage = false): array
    {
        $userModel = $mru->user;
        $subscription = $this->getActiveSubscription($userModel);
        $extraRate = $subscription ? (float) $subscription->extra_mru_rate_locked : 0.0;

        return DB::transaction(function () use ($userModel, $mru, $payOverage, $extraRate) {
            $charge = null;
            if ($payOverage && $extraRate > 0) {
                $debitResult = $this->walletService->debit(
                    user: $userModel,
                    amount: $extraRate,
                    source: 'mru_unlock',
                    referenceType: 'mru',
                    referenceId: (string) $mru->id,
                    description: "MRU unlock fee ({$mru->name})"
                );

                if ($debitResult !== DebitResult::SUCCESS) {
                    return [
                        'success' => false,
                        'debit_result' => $debitResult->value,
                        'amount_due' => $extraRate,
                        'message' => $debitResult === DebitResult::WALLET_FROZEN
                            ? 'Wallet is frozen. Please contact administrator.'
                            : 'Insufficient wallet balance to unlock MRU.',
                    ];
                }

                $latestTx = $userModel->transactions()->latest('id')->first();

                $charge = PlanOverageCharge::create([
                    'user_id' => $userModel->id,
                    'charge_type' => 'mru_unlock',
                    'reference_type' => 'mru',
                    'reference_id' => (string) $mru->id,
                    'amount' => $extraRate,
                    'wallet_transaction_id' => $latestTx?->id ? (string)$latestTx->id : null,
                    'created_at' => now(),
                ]);

                event(new MruOverageChargedEvent($userModel, $mru, $extraRate, $charge));
            }

            $mru->update([
                'status' => 'active',
                'locked_reason' => null,
                'unlocked_at' => now(),
                'is_over_quota' => $payOverage,
            ]);

            event(new MruUnlockedEvent($mru, $payOverage ? $extraRate : 0.0));

            return [
                'success' => true,
                'mru' => $mru->fresh(),
            ];
        });
    }

    /**
     * Check if a specific action is allowed on an MRU based on its locked status.
     * Enforces PRD section 5.1 step 3:
     * Allowed: view, rename, delete, add_consumer, remove_consumer
     * Blocked: modify_consumer_details, create_cycle, process_pdf, download_pdf
     */
    public function isActionAllowed(Mru $mru, string $action): bool
    {
        if (!$mru->isLocked()) {
            return true;
        }

        $allowedActions = [
            'view',
            'read',
            'rename',
            'delete',
            'add_consumer',
            'remove_consumer',
        ];

        return in_array(strtolower($action), $allowedActions, true);
    }
}
