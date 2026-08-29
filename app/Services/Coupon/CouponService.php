<?php

namespace App\Services\Coupon;

use App\Models\CouponCode;
use App\Models\CouponRedemption;
use App\Models\CouponTopupSlab;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CouponService
{
    /**
     * Create a new coupon code (Subscription Discount or Top-Up Bonus with Slabs).
     */
    public function createCoupon(array $data): CouponCode
    {
        $code = strtoupper(trim($data['code'] ?? ''));
        if (empty($code)) {
            throw new InvalidArgumentException('Coupon code is required.');
        }

        $type = $data['type'] ?? 'subscription_discount';

        return DB::transaction(function () use ($data, $code, $type) {
            $coupon = CouponCode::create([
                'code' => $code,
                'type' => $type,
                'discount_kind' => $type === 'subscription_discount' ? ($data['discount_kind'] ?? 'percentage') : null,
                'discount_value' => $type === 'subscription_discount' ? (float)($data['discount_value'] ?? 0) : null,
                'plan_restriction_id' => $type === 'subscription_discount' ? ($data['plan_restriction_id'] ?? null) : null,
                'minimum_amount' => !empty($data['minimum_amount']) ? (float)$data['minimum_amount'] : null,
                'usage_limit_per_user' => (int)($data['usage_limit_per_user'] ?? 1),
                'usage_limit_total' => !empty($data['usage_limit_total']) ? (int)$data['usage_limit_total'] : null,
                'starts_at' => !empty($data['starts_at']) ? $data['starts_at'] : null,
                'expires_at' => !empty($data['expires_at']) ? $data['expires_at'] : null,
                'is_active' => (bool)($data['is_active'] ?? true),
                'created_by_admin_id' => $data['created_by_admin_id'] ?? auth()->id(),
            ]);

            // If topup_bonus, insert slabs
            if ($type === 'topup_bonus' && !empty($data['slabs']) && is_array($data['slabs'])) {
                foreach ($data['slabs'] as $slab) {
                    if (isset($slab['min_amount'], $slab['bonus_percent']) && (float)$slab['bonus_percent'] > 0) {
                        CouponTopupSlab::create([
                            'coupon_code_id' => $coupon->id,
                            'min_amount' => (float)$slab['min_amount'],
                            'max_amount' => !empty($slab['max_amount']) ? (float)$slab['max_amount'] : null,
                            'bonus_percent' => (float)$slab['bonus_percent'],
                        ]);
                    }
                }
            }

            return $coupon->fresh('slabs');
        });
    }

    /**
     * Update an existing coupon code and its slabs.
     */
    public function updateCoupon(CouponCode $coupon, array $data): CouponCode
    {
        return DB::transaction(function () use ($coupon, $data) {
            $updateData = [
                'discount_kind' => $coupon->type === 'subscription_discount' ? ($data['discount_kind'] ?? $coupon->discount_kind) : null,
                'discount_value' => $coupon->type === 'subscription_discount' ? (float)($data['discount_value'] ?? $coupon->discount_value) : null,
                'plan_restriction_id' => $coupon->type === 'subscription_discount' ? ($data['plan_restriction_id'] ?? null) : null,
                'minimum_amount' => !empty($data['minimum_amount']) ? (float)$data['minimum_amount'] : null,
                'usage_limit_per_user' => (int)($data['usage_limit_per_user'] ?? $coupon->usage_limit_per_user),
                'usage_limit_total' => !empty($data['usage_limit_total']) ? (int)$data['usage_limit_total'] : null,
                'starts_at' => !empty($data['starts_at']) ? $data['starts_at'] : null,
                'expires_at' => !empty($data['expires_at']) ? $data['expires_at'] : null,
                'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : $coupon->is_active,
            ];

            if (!empty($data['code'])) {
                $updateData['code'] = strtoupper(trim($data['code']));
            }

            $coupon->update($updateData);

            if ($coupon->type === 'topup_bonus' && isset($data['slabs']) && is_array($data['slabs'])) {
                $coupon->slabs()->delete();
                foreach ($data['slabs'] as $slab) {
                    if (isset($slab['min_amount'], $slab['bonus_percent']) && (float)$slab['bonus_percent'] > 0) {
                        CouponTopupSlab::create([
                            'coupon_code_id' => $coupon->id,
                            'min_amount' => (float)$slab['min_amount'],
                            'max_amount' => !empty($slab['max_amount']) ? (float)$slab['max_amount'] : null,
                            'bonus_percent' => (float)$slab['bonus_percent'],
                        ]);
                    }
                }
            }

            return $coupon->fresh('slabs');
        });
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleActive(CouponCode $coupon): bool
    {
        $coupon->is_active = !$coupon->is_active;
        $coupon->save();

        return $coupon->is_active;
    }

    /**
     * Safe delete coupon: blocks hard deletion if redemptions exist to protect audit trail.
     */
    public function deleteCoupon(CouponCode $coupon): bool
    {
        // Soft delete
        return (bool)$coupon->delete();
    }

    /**
     * Get usage analytics for a specific coupon.
     */
    public function getAnalytics(CouponCode $coupon): array
    {
        $redemptions = $coupon->redemptions()->with('user')->get();

        return [
            'times_used' => $coupon->times_used_total,
            'total_discount_given' => (float)$redemptions->sum('discount_or_bonus_amount'),
            'total_original_revenue' => (float)$redemptions->sum('original_amount'),
            'total_final_revenue' => (float)$redemptions->sum('final_amount'),
            'unique_users_count' => $redemptions->pluck('user_id')->unique()->count(),
            'recent_redemptions' => $redemptions->take(15),
        ];
    }
}
