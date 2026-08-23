<?php

namespace App\Enums;

enum DebitResult: string
{
    case SUCCESS = 'success';
    case INSUFFICIENT_BALANCE = 'insufficient_balance';
    case WALLET_FROZEN = 'wallet_frozen';

    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }

    public function message(): string
    {
        return match ($this) {
            self::SUCCESS => 'Debit transaction completed successfully.',
            self::INSUFFICIENT_BALANCE => 'Insufficient wallet balance for this transaction.',
            self::WALLET_FROZEN => 'Wallet is currently frozen. Debits are disabled.',
        };
    }
}
