<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PENDING_VERIFICATION = 'pending_verification';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Checkout',
            self::PENDING_VERIFICATION => 'Pending Admin Verification',
            self::SUCCESS => 'Payment Successful',
            self::FAILED => 'Payment Failed',
            self::REJECTED => 'Payment Rejected',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700',
            self::PENDING_VERIFICATION => 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-300 dark:border-amber-800',
            self::SUCCESS => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-300 dark:border-emerald-800',
            self::FAILED => 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-300 dark:border-rose-800',
            self::REJECTED => 'bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-200 border-rose-300 dark:border-rose-800',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::SUCCESS, self::FAILED, self::REJECTED], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
