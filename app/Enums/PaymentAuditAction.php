<?php

namespace App\Enums;

enum PaymentAuditAction: string
{
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::REFUNDED => 'Refunded',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
