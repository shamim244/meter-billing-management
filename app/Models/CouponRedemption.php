<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponRedemption extends Model
{
    use HasFactory, BelongsToUser;

    protected $table = 'coupon_redemptions';

    protected $fillable = [
        'coupon_code_id',
        'user_id',
        'redeemed_for_type',
        'redeemed_for_reference_id',
        'original_amount',
        'discount_or_bonus_amount',
        'final_amount',
        'wallet_transaction_id',
        'redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'discount_or_bonus_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'wallet_transaction_id' => 'integer',
            'redeemed_at' => 'datetime',
        ];
    }

    /**
     * Coupon code that was redeemed.
     */
    public function couponCode(): BelongsTo
    {
        return $this->belongsTo(CouponCode::class, 'coupon_code_id')->withTrashed();
    }

    /**
     * User who redeemed the coupon.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
