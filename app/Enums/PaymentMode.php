<?php

namespace App\Enums;

enum PaymentMode: string
{
    case PG = 'pg';
    case MANUAL_UPI = 'manual_upi';
    case BANK_TRANSFER = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::PG => 'Online Payment Gateway',
            self::MANUAL_UPI => 'Manual UPI Transfer',
            self::BANK_TRANSFER => 'Bank Transfer (NEFT / IMPS)',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PG => '⚡',
            self::MANUAL_UPI => '📱',
            self::BANK_TRANSFER => '🏦',
        };
    }

    public function isManual(): bool
    {
        return in_array($this, [self::MANUAL_UPI, self::BANK_TRANSFER], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
