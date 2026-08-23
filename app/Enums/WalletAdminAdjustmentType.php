<?php

namespace App\Enums;

enum WalletAdminAdjustmentType: string
{
    case ADD = 'add';
    case DEDUCT = 'deduct';

    public function label(): string
    {
        return match ($this) {
            self::ADD => 'Add Balance',
            self::DEDUCT => 'Deduct Balance',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
