<?php

namespace App\Enums;

enum MandateStatus: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active Mandate',
            self::PAUSED => 'Paused',
            self::CANCELLED => 'Cancelled',
            self::FAILED => 'Failed',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
