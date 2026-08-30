<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralSignup extends Model
{
    use HasFactory;

    protected $table = 'referral_signups';

    protected $fillable = [
        'referrer_user_id',
        'referee_user_id',
        'referral_coupon_code_id',
        'signed_up_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_up_at' => 'datetime',
        ];
    }

    /**
     * The referring Agent.
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /**
     * The referee Agent.
     */
    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_user_id');
    }

    /**
     * The referral coupon code used at signup.
     */
    public function couponCode(): BelongsTo
    {
        return $this->belongsTo(CouponCode::class, 'referral_coupon_code_id')->withTrashed();
    }
}
