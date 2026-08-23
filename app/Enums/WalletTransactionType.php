<?php

namespace App\Enums;

enum WalletTransactionType: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';

    public function label(): string
    {
        return match ($this) {
            self::CREDIT => 'Credit',
            self::DEBIT => 'Debit',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
