<?php

namespace App\Enums;

enum PaymentPurpose: string
{
    case WALLET_TOPUP = 'wallet_topup';
    case DIRECT_SUBSCRIPTION = 'direct_subscription';

    public function label(): string
    {
        return match ($this) {
            self::WALLET_TOPUP => 'Wallet Top-up',
            self::DIRECT_SUBSCRIPTION => 'Direct Subscription Payment',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
