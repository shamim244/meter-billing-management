<?php

namespace App\Models;

use Bavix\Wallet\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralPayout extends Model
{
    use HasFactory;

    protected $table = 'referral_payouts';

    protected $fillable = [
        'referral_coupon_code_id',
        'referrer_user_id',
        'referee_user_id',
        'qualifying_payment_reference_type',
        'qualifying_payment_reference_id',
        'reward_amount',
        'status',
        'hold_expires_at',
        'paid_at',
        'clawed_back_at',
        'clawback_reason',
        'wallet_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'reward_amount' => 'decimal:2',
            'hold_expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'clawed_back_at' => 'datetime',
        ];
    }

    /**
     * The referral coupon code used.
     */
    public function couponCode(): BelongsTo
    {
        return $this->belongsTo(CouponCode::class, 'referral_coupon_code_id')->withTrashed();
    }

    /**
     * The referring Agent who earns the reward.
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /**
     * The newly referred Agent who made the qualifying payment.
     */
    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_user_id');
    }

    /**
     * The wallet transaction associated with the paid bonus.
     */
    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'wallet_transaction_id');
    }

    /**
     * Scope for pending payouts.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for paid payouts.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for cancelled payouts.
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope for clawed back payouts.
     */
    public function scopeClawedBack(Builder $query): Builder
    {
        return $query->where('status', 'clawed_back');
    }

    /**
     * Check if hold period is still active.
     */
    public function isUnderHold(): bool
    {
        return $this->status === 'pending' && $this->hold_expires_at->isFuture();
    }
}
