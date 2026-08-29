<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponTopupSlab extends Model
{
    use HasFactory;

    protected $table = 'coupon_topup_slabs';

    protected $fillable = [
        'coupon_code_id',
        'min_amount',
        'max_amount',
        'bonus_percent',
    ];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'bonus_percent' => 'decimal:2',
        ];
    }

    /**
     * Parent coupon code relationship.
     */
    public function couponCode(): BelongsTo
    {
        return $this->belongsTo(CouponCode::class, 'coupon_code_id');
    }

    /**
     * Formatted slab description string (e.g. "₹101 – ₹1,000 → 5% bonus").
     */
    public function getFormattedRangeAttribute(): string
    {
        $min = '₹' . number_format((float)$this->min_amount, 0);
        $max = $this->max_amount !== null ? '₹' . number_format((float)$this->max_amount, 0) : 'Above';

        return "{$min} – {$max}";
    }
}
