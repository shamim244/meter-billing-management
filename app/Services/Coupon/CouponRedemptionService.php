<?php

namespace App\Services\Coupon;

use App\Models\CouponCode;
use App\Models\CouponRedemption;
use App\Models\Payment;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CouponRedemptionService
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Validate a coupon code in real-time for an Agent action (subscription or wallet top-up).
     * Returns calculation details without applying yet.
     *
     * @param string $code
     * @param User $user
     * @param string $actionType 'subscription_discount' | 'topup_bonus'
     * @param float $amount The amount to apply coupon against (e.g. discounted duration price or topup amount)
     * @param int|null $planId Optional plan ID for plan restriction verification
     * @return array
     */
    public function validateCode(
        string $code,
        User $user,
        string $actionType,
        float $amount,
        ?int $planId = null
    ): array {
        $cleanCode = strtoupper(trim($code));
        if (empty($cleanCode)) {
            return [
                'valid' => false,
                'message' => 'Please enter a coupon code.',
                'coupon' => null,
            ];
        }

        $coupon = CouponCode::with('slabs')
            ->where('code', $cleanCode)
            ->first();

        if (!$coupon) {
            return [
                'valid' => false,
                'message' => "Coupon code '{$cleanCode}' is invalid.",
                'coupon' => null,
            ];
        }

        if (!$coupon->is_active) {
            return [
                'valid' => false,
                'message' => "Coupon code '{$cleanCode}' is currently inactive.",
                'coupon' => null,
            ];
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return [
                'valid' => false,
                'message' => "Coupon code '{$cleanCode}' will be active from " . $coupon->starts_at->format('M d, Y') . '.',
                'coupon' => null,
            ];
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return [
                'valid' => false,
                'message' => "Coupon code '{$cleanCode}' expired on " . $coupon->expires_at->format('M d, Y') . '.',
                'coupon' => null,
            ];
        }

        // Action type check
        if ($coupon->type !== $actionType) {
            $expected = $actionType === 'subscription_discount' ? 'Subscription purchases' : 'Wallet Top-ups';
            return [
                'valid' => false,
                'message' => "This coupon is only valid for {$expected}.",
                'coupon' => null,
            ];
        }

        // Total platform limit check
        if ($coupon->usage_limit_total !== null && $coupon->times_used_total >= $coupon->usage_limit_total) {
            return [
                'valid' => false,
                'message' => "Coupon code '{$cleanCode}' has reached its maximum total redemptions limit.",
                'coupon' => null,
            ];
        }

        // Per-user usage limit check
        $userRedemptionsCount = CouponRedemption::where('coupon_code_id', $coupon->id)
            ->where('user_id', $user->id)
            ->count();

        if ($userRedemptionsCount >= $coupon->usage_limit_per_user) {
            return [
                'valid' => false,
                'message' => "You have already used coupon code '{$cleanCode}' the maximum allowed number of times ({$coupon->usage_limit_per_user}).",
                'coupon' => null,
            ];
        }

        // Plan restriction check (for subscription_discount)
        if ($coupon->type === 'subscription_discount' && $coupon->plan_restriction_id !== null) {
            if ($planId === null || (int)$coupon->plan_restriction_id !== (int)$planId) {
                $planName = $coupon->restrictedPlan->name ?? 'a specific plan';
                return [
                    'valid' => false,
                    'message' => "Coupon code '{$cleanCode}' is only applicable to the {$planName}.",
                    'coupon' => null,
                ];
            }
        }

        // Minimum amount check
        if ($coupon->minimum_amount !== null && $amount < (float)$coupon->minimum_amount) {
            return [
                'valid' => false,
                'message' => "Minimum purchase of ₹" . number_format((float)$coupon->minimum_amount, 2) . " required to apply coupon '{$cleanCode}'.",
                'coupon' => null,
            ];
        }

        // Calculation based on type
        if ($coupon->type === 'subscription_discount') {
            $discountAmount = $coupon->calculateSubscriptionDiscount($amount);
            $finalAmount = max(0.00, round($amount - $discountAmount, 2));

            return [
                'valid' => true,
                'coupon' => $coupon,
                'code' => $coupon->code,
                'type' => $coupon->type,
                'discount_kind' => $coupon->discount_kind,
                'discount_value' => (float)$coupon->discount_value,
                'original_amount' => $amount,
                'discount_or_bonus_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'message' => $coupon->discount_kind === 'percentage'
                    ? "Coupon '{$coupon->code}' applied: {$coupon->discount_value}% OFF (Save ₹" . number_format($discountAmount, 2) . ")!"
                    : "Coupon '{$coupon->code}' applied: Flat ₹" . number_format($discountAmount, 2) . " OFF!",
            ];
        }

        if ($coupon->type === 'topup_bonus') {
            $topupCalc = $coupon->calculateTopupBonus($amount);
            $bonusAmount = $topupCalc['bonus_amount'];
            $bonusPercent = $topupCalc['bonus_percent'];
            $totalCredited = round($amount + $bonusAmount, 2);

            if ($bonusAmount <= 0) {
                return [
                    'valid' => false,
                    'message' => "Top-up amount of ₹" . number_format($amount, 2) . " does not meet any bonus slab for coupon '{$coupon->code}'.",
                    'coupon' => $coupon,
                ];
            }

            return [
                'valid' => true,
                'coupon' => $coupon,
                'code' => $coupon->code,
                'type' => $coupon->type,
                'bonus_percent' => $bonusPercent,
                'original_amount' => $amount,
                'discount_or_bonus_amount' => $bonusAmount,
                'final_amount' => $totalCredited,
                'message' => "Coupon '{$coupon->code}' applied: {$bonusPercent}% Bonus (+₹" . number_format($bonusAmount, 2) . ")! You will receive ₹" . number_format($totalCredited, 2) . " total in your wallet.",
            ];
        }

        return ['valid' => false, 'message' => 'Unsupported coupon type.', 'coupon' => null];
    }

    /**
     * Redeem a subscription discount coupon during checkout.
     */
    public function redeemForSubscription(
        CouponCode $coupon,
        User $user,
        float $originalAmount,
        ?string $referenceId = null
    ): CouponRedemption {
        $discountAmount = $coupon->calculateSubscriptionDiscount($originalAmount);
        $finalAmount = max(0.00, round($originalAmount - $discountAmount, 2));

        return DB::transaction(function () use ($coupon, $user, $originalAmount, $discountAmount, $finalAmount, $referenceId) {
            $redemption = CouponRedemption::create([
                'coupon_code_id' => $coupon->id,
                'user_id' => $user->id,
                'redeemed_for_type' => 'subscription_payment',
                'redeemed_for_reference_id' => (string)($referenceId ?? 'sub_' . uniqid()),
                'original_amount' => $originalAmount,
                'discount_or_bonus_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'wallet_transaction_id' => null,
                'redeemed_at' => now(),
            ]);

            $coupon->increment('times_used_total');

            return $redemption;
        });
    }

    /**
     * Redeem a top-up bonus coupon upon wallet recharge completion.
     */
    public function redeemForTopup(
        CouponCode $coupon,
        User $user,
        float $topupAmount,
        ?Payment $payment = null
    ): array {
        $calc = $coupon->calculateTopupBonus($topupAmount);
        $bonusAmount = $calc['bonus_amount'];

        if ($bonusAmount <= 0) {
            throw new InvalidArgumentException("Top-up amount of ₹{$topupAmount} does not qualify for any bonus slab under coupon {$coupon->code}.");
        }

        return DB::transaction(function () use ($coupon, $user, $topupAmount, $bonusAmount, $payment) {
            // Credit bonus to wallet with dedicated source tag
            $walletTx = $this->walletService->credit(
                $user,
                $bonusAmount,
                'coupon_topup_bonus'
            );

            $redemption = CouponRedemption::create([
                'coupon_code_id' => $coupon->id,
                'user_id' => $user->id,
                'redeemed_for_type' => 'topup',
                'redeemed_for_reference_id' => $payment ? (string)$payment->id : null,
                'original_amount' => $topupAmount,
                'discount_or_bonus_amount' => $bonusAmount,
                'final_amount' => round($topupAmount + $bonusAmount, 2),
                'wallet_transaction_id' => $walletTx ? $walletTx->id : null,
                'redeemed_at' => now(),
            ]);

            $coupon->increment('times_used_total');

            return [
                'redemption' => $redemption,
                'bonus_amount' => $bonusAmount,
                'wallet_transaction' => $walletTx,
            ];
        });
    }
}
