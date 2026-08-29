<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CouponCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'coupon_codes';

    protected $fillable = [
        'code',
        'type',
        'discount_kind',
        'discount_value',
        'plan_restriction_id',
        'minimum_amount',
        'usage_limit_per_user',
        'usage_limit_total',
        'times_used_total',
        'starts_at',
        'expires_at',
        'is_active',
        'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'minimum_amount' => 'decimal:2',
            'usage_limit_per_user' => 'integer',
            'usage_limit_total' => 'integer',
            'times_used_total' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Always format coupon code to uppercase.
     */
    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    /**
     * Optional restricted plan relationship.
     */
    public function restrictedPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_restriction_id')->withTrashed();
    }

    /**
     * Top-up bonus slabs (only for type = 'topup_bonus').
     */
    public function slabs(): HasMany
    {
        return $this->hasMany(CouponTopupSlab::class, 'coupon_code_id')->orderBy('min_amount');
    }

    /**
     * All redemptions of this coupon code.
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class, 'coupon_code_id')->orderByDesc('id');
    }

    /**
     * Admin who created this coupon.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    /**
     * Check if coupon is currently valid (active, date within range, usage within total cap).
     */
    public function isValidNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit_total !== null && $this->times_used_total >= $this->usage_limit_total) {
            return false;
        }

        return true;
    }

    /**
     * Check if a specific user can redeem this coupon.
     */
    public function canUserRedeem(User|int $user): bool
    {
        if (!$this->isValidNow()) {
            return false;
        }

        $userId = $user instanceof User ? $user->id : $user;
        $userRedemptionsCount = $this->redemptions()->where('user_id', $userId)->count();

        return $userRedemptionsCount < $this->usage_limit_per_user;
    }

    /**
     * Calculate discount amount for a given base price (stacks on duration price).
     */
    public function calculateSubscriptionDiscount(float $durationPrice): float
    {
        if ($this->type !== 'subscription_discount' || $durationPrice <= 0) {
            return 0.00;
        }

        if ($this->discount_kind === 'percentage') {
            $discount = ($durationPrice * (float)$this->discount_value) / 100;
        } else {
            // Flat amount discount
            $discount = (float)$this->discount_value;
        }

        // Never exceed duration price
        return round(min($durationPrice, max(0.00, $discount)), 2);
    }

    /**
     * Calculate top-up bonus amount for a given wallet recharge amount based on slabs.
     */
    public function calculateTopupBonus(float $topupAmount): array
    {
        if ($this->type !== 'topup_bonus' || $topupAmount <= 0) {
            return ['bonus_percent' => 0.00, 'bonus_amount' => 0.00, 'slab' => null];
        }

        $matchingSlab = $this->slabs
            ->first(function (CouponTopupSlab $slab) use ($topupAmount) {
                if ($slab->max_amount !== null) {
                    return $topupAmount >= (float)$slab->min_amount && $topupAmount <= (float)$slab->max_amount;
                }
                return $topupAmount >= (float)$slab->min_amount;
            });

        if (!$matchingSlab || (float)$matchingSlab->bonus_percent <= 0) {
            return ['bonus_percent' => 0.00, 'bonus_amount' => 0.00, 'slab' => null];
        }

        $bonusAmount = round(($topupAmount * (float)$matchingSlab->bonus_percent) / 100, 2);

        return [
            'bonus_percent' => (float)$matchingSlab->bonus_percent,
            'bonus_amount' => $bonusAmount,
            'slab' => $matchingSlab,
        ];
    }
}
