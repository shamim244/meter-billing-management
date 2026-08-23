<?php

namespace App\Services\Wallet;

use App\Enums\DebitResult;
use App\Enums\WalletAdminAdjustmentType;
use App\Events\WalletCriticalBalanceEvent;
use App\Events\WalletCreditedEvent;
use App\Events\WalletDebitedEvent;
use App\Events\WalletFrozenEvent;
use App\Events\WalletInsufficientForRenewalEvent;
use App\Events\WalletLowBalanceEvent;
use App\Events\WalletUnfrozenEvent;
use App\Models\User;
use Bavix\Wallet\Exceptions\BalanceIsEmpty;
use Bavix\Wallet\Exceptions\InsufficientFunds;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

/**
 * WalletService — Thin application wrapper around bavix/laravel-wallet engine.
 * Handles custom freeze/unfreeze state, alert triggers, and admin adjustments.
 */
class WalletService
{
    /**
     * Resolve User instance.
     */
    protected function resolveUser(User|int $user): User
    {
        return $user instanceof User ? $user : User::findOrFail($user);
    }

    /**
     * Get the bavix Wallet model for a user.
     */
    public function getWallet(User|int $user): Wallet
    {
        $userModel = $this->resolveUser($user);
        return $userModel->wallet;
    }

    /**
     * Get current floating-point balance for an agent / user.
     */
    public function getBalance(User|int $user): float
    {
        $userModel = $this->resolveUser($user);
        return (float) $userModel->balanceFloat;
    }

    /**
     * Credit funds to an agent's wallet using bavix depositFloat.
     * Metadata (source, reference_type, reference_id, description) stored in native JSON meta.
     */
    public function credit(
        User|int $user,
        float $amount,
        string $source,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $description = null
    ): Transaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Credit amount must be greater than zero.");
        }

        $userModel = $this->resolveUser($user);

        $meta = [
            'source' => $source,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId ? (string) $referenceId : null,
            'description' => $description,
        ];

        // Use Bavix depositFloat method (auto-handles transactions and concurrency)
        $transaction = $userModel->depositFloat($amount, $meta);

        // Dispatch domain events
        event(new WalletCreditedEvent($userModel, $transaction));

        return $transaction;
    }

    /**
     * Debit funds from an agent's wallet using bavix withdrawFloat.
     * Returns DebitResult::SUCCESS or DebitResult::INSUFFICIENT_BALANCE (or WALLET_FROZEN).
     * NEVER throws an exception for normal insufficient balance.
     */
    public function debit(
        User|int $user,
        float $amount,
        string $source,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $description = null
    ): DebitResult {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Debit amount must be greater than zero.");
        }

        $userModel = $this->resolveUser($user);

        // 1. Check custom wallet freeze status
        if ($userModel->isWalletFrozen()) {
            return DebitResult::WALLET_FROZEN;
        }

        $meta = [
            'source' => $source,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId ? (string) $referenceId : null,
            'description' => $description,
        ];

        try {
            // Use Bavix withdrawFloat method
            $transaction = $userModel->withdrawFloat($amount, $meta);

            // Dispatch domain event on successful debit
            event(new WalletDebitedEvent($userModel, $transaction));

            // Check low balance alert thresholds
            $this->checkBalanceAlerts($userModel);

            return DebitResult::SUCCESS;
        } catch (InsufficientFunds | BalanceIsEmpty $e) {
            // Graceful non-throwing return for insufficient funds
            return DebitResult::INSUFFICIENT_BALANCE;
        }
    }

    /**
     * Administrative manual adjustment (+ Add Balance or - Deduct Balance).
     * Deductions use bavix forceWithdrawFloat — the ONLY method permitted to push balance negative.
     */
    public function adminAdjust(
        User|int $user,
        User|int $admin,
        string|WalletAdminAdjustmentType $type,
        float $amount,
        string $reason
    ): Transaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Adjustment amount must be greater than zero.");
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException("Adjustment reason is mandatory.");
        }

        $userModel = $this->resolveUser($user);
        $adminModel = $this->resolveUser($admin);
        $adjType = $type instanceof WalletAdminAdjustmentType ? $type : WalletAdminAdjustmentType::from($type);

        $meta = [
            'source' => 'admin_adjustment',
            'adjustment_type' => $adjType->value,
            'admin_id' => $adminModel->id,
            'admin_name' => $adminModel->name,
            'reason' => $reason,
            'description' => "[Admin: {$adminModel->name}] " . $reason,
        ];

        if ($adjType === WalletAdminAdjustmentType::ADD) {
            $transaction = $userModel->depositFloat($amount, $meta);
            event(new WalletCreditedEvent($userModel, $transaction));
        } else {
            // Bavix forceWithdrawFloat allows balance to push negative for admin overrides
            $transaction = $userModel->forceWithdrawFloat($amount, $meta);
            event(new WalletDebitedEvent($userModel, $transaction));
            $this->checkBalanceAlerts($userModel);
        }

        return $transaction;
    }

    /**
     * Freeze wallet (blocks debits until unfrozen).
     */
    public function freeze(User|int $user, User|int $admin, string $reason): User
    {
        $userModel = $this->resolveUser($user);
        $adminModel = $this->resolveUser($admin);

        $userModel->update([
            'is_wallet_frozen' => true,
            'wallet_frozen_reason' => $reason,
            'wallet_frozen_at' => now(),
            'wallet_frozen_by' => $adminModel->id,
        ]);

        event(new WalletFrozenEvent($userModel, $adminModel, $reason));

        return $userModel;
    }

    /**
     * Unfreeze wallet (restores normal debit capabilities).
     */
    public function unfreeze(User|int $user, User|int $admin, ?string $reason = null): User
    {
        $userModel = $this->resolveUser($user);
        $adminModel = $this->resolveUser($admin);

        $userModel->update([
            'is_wallet_frozen' => false,
            'wallet_frozen_reason' => null,
            'wallet_frozen_at' => null,
            'wallet_frozen_by' => null,
        ]);

        event(new WalletUnfrozenEvent($userModel, $adminModel, $reason));

        return $userModel;
    }

    /**
     * Get paginated transaction history with filters using bavix Transaction model.
     */
    public function getTransactionHistory(User|int $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $userModel = $this->resolveUser($user);

        $query = $userModel->transactions()->latest('id');

        if (!empty($filters['type'])) {
            // bavix type is 'deposit' for credit or 'withdraw' for debit
            $type = match ($filters['type']) {
                'credit' => 'deposit',
                'debit' => 'withdraw',
                default => $filters['type'],
            };
            $query->where('type', $type);
        }

        if (!empty($filters['source'])) {
            $query->where('meta->source', $filters['source']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('meta->description', 'like', $search)
                  ->orWhere('meta->reference_id', 'like', $search)
                  ->orWhere('meta->source', 'like', $search);
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Evaluate wallet balance against threshold alerts and fire domain events.
     */
    public function checkBalanceAlerts(
        User $user,
        ?float $upcomingRenewalAmount = null,
        ?Carbon $renewalDate = null
    ): void {
        $balance = (float) $user->balanceFloat;

        // Base subscription amount (Default Pro tier = 499, or user's specific tier if set)
        $baseSubscriptionAmount = match ($user->plan_tier ?? 'pro') {
            'enterprise' => 1499.00,
            default => 499.00,
        };

        // 1. Critical Balance Event (< 1 month base subscription)
        if ($balance < $baseSubscriptionAmount) {
            event(new WalletCriticalBalanceEvent($user, $balance, $baseSubscriptionAmount));
        }

        // 2. Low Balance Event (< configured threshold e.g. from Admin settings or config, default ₹200)
        $lowThreshold = (float) \App\Models\SystemSetting::get('wallet_low_balance_threshold', config('wallet.low_balance_threshold', 200.00));
        if ($balance < $lowThreshold) {
            event(new WalletLowBalanceEvent($user, $balance, $lowThreshold));
        }

        // 3. Insufficient for upcoming renewal
        if ($upcomingRenewalAmount !== null && $balance < $upcomingRenewalAmount) {
            event(new WalletInsufficientForRenewalEvent(
                $user,
                $balance,
                $upcomingRenewalAmount,
                $renewalDate
            ));
        }
    }
}
