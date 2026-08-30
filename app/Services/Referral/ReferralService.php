<?php

namespace App\Services\Referral;

use App\Enums\DebitResult;
use App\Enums\WalletAdminAdjustmentType;
use App\Models\CouponCode;
use App\Models\CouponRedemption;
use App\Models\ReferralPayout;
use App\Models\ReferralSignup;
use App\Models\User;
use App\Services\Coupon\CouponService;
use App\Services\Notifications\NotificationDispatchService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ReferralService
{
    public function __construct(
        protected ReferralSettingsService $settingsService,
        protected CouponService $couponService,
        protected WalletService $walletService,
        protected NotificationDispatchService $notificationDispatcher
    ) {}

    /**
     * Generate an active unique referral code for a new agent.
     */
    public function generateCodeForNewAgent(int|User $user): CouponCode
    {
        $userId = $user instanceof User ? $user->id : $user;
        $userObj = $user instanceof User ? $user : User::findOrFail($userId);
        $settings = $this->settingsService->getSettings();

        // Generate unique readable code
        $code = null;
        $maxAttempts = 10;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $candidate = 'REF-' . strtoupper(Str::random(6));
            if (!CouponCode::where('code', $candidate)->exists()) {
                $code = $candidate;
                break;
            }
        }

        if (!$code) {
            $code = 'REF-' . $userId . '-' . strtoupper(Str::random(4));
        }

        return $this->couponService->createCoupon([
            'code' => $code,
            'type' => 'referral',
            'owner_user_id' => $userId,
            'discount_kind' => $settings['reward_kind'],
            'discount_value' => $settings['reward_value'],
            'usage_limit_per_user' => 1,
            'usage_limit_total' => null,
            'minimum_amount' => $settings['minimum_qualifying_amount'],
            'is_active' => true,
        ]);
    }

    /**
     * Regenerate an Agent's referral code.
     * Deactivates old code for new signups; existing pending payouts remain unaffected.
     */
    public function regenerateCode(int|User $user): CouponCode
    {
        $userId = $user instanceof User ? $user->id : $user;

        // Deactivate old active referral codes for this user
        CouponCode::where('owner_user_id', $userId)
            ->where('type', 'referral')
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return $this->generateCodeForNewAgent($userId);
    }

    /**
     * Get or create the active referral code for an Agent.
     */
    public function getOrCreateActiveCode(int|User $user): CouponCode
    {
        $userId = $user instanceof User ? $user->id : $user;

        $existing = CouponCode::where('owner_user_id', $userId)
            ->where('type', 'referral')
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return $this->generateCodeForNewAgent($userId);
    }

    /**
     * Validate a referral code for a new or registering Agent.
     *
     * @return array{valid: bool, message: string, coupon: ?CouponCode}
     */
    public function validateReferralCode(string $code, int|User|null $newUserId = null): array
    {
        $cleanCode = strtoupper(trim($code));
        if (empty($cleanCode)) {
            return ['valid' => false, 'message' => 'Referral code is required.', 'coupon' => null];
        }

        $coupon = CouponCode::where('code', $cleanCode)->first();
        if (!$coupon) {
            return ['valid' => false, 'message' => 'Invalid referral code.', 'coupon' => null];
        }

        if ($coupon->type !== 'referral') {
            return ['valid' => false, 'message' => 'The provided code is not a referral code.', 'coupon' => null];
        }

        if (!$coupon->is_active) {
            return ['valid' => false, 'message' => 'This referral code is inactive or has expired.', 'coupon' => null];
        }

        $userId = $newUserId instanceof User ? $newUserId->id : $newUserId;

        if ($userId !== null) {
            // Self-referral rejection
            if ($coupon->owner_user_id && (int) $coupon->owner_user_id === (int) $userId) {
                return ['valid' => false, 'message' => 'You cannot use your own referral code.', 'coupon' => null];
            }

            // Platform-wide one-time referral rule: has this user ever redeemed a referral code or signed up via referral?
            $hasPriorSignup = ReferralSignup::where('referee_user_id', $userId)->exists();
            $hasPriorRedemption = CouponRedemption::where('user_id', $userId)
                ->whereHas('couponCode', fn($q) => $q->where('type', 'referral'))
                ->exists();

            if ($hasPriorSignup || $hasPriorRedemption) {
                return ['valid' => false, 'message' => 'You have already redeemed a referral code previously.', 'coupon' => null];
            }
        }

        return [
            'valid' => true,
            'message' => 'Valid referral code.',
            'coupon' => $coupon,
        ];
    }

    /**
     * Link a referee to a referrer at signup time.
     */
    public function recordReferralSignup(string|CouponCode $codeOrCoupon, int|User $newUserId): ?ReferralSignup
    {
        $userId = $newUserId instanceof User ? $newUserId->id : $newUserId;

        $coupon = $codeOrCoupon instanceof CouponCode
            ? $codeOrCoupon
            : CouponCode::where('code', strtoupper(trim($codeOrCoupon)))->first();

        if (!$coupon || $coupon->type !== 'referral') {
            return null;
        }

        $validation = $this->validateReferralCode($coupon->code, $userId);
        if (!$validation['valid']) {
            Log::warning("[ReferralSignup] Validation failed for User #{$userId} with code {$coupon->code}: {$validation['message']}");
            return null;
        }

        return DB::transaction(function () use ($coupon, $userId) {
            $signup = ReferralSignup::firstOrCreate(
                ['referee_user_id' => $userId],
                [
                    'referrer_user_id' => $coupon->owner_user_id,
                    'referral_coupon_code_id' => $coupon->id,
                    'signed_up_at' => now(),
                ]
            );

            Log::info("[ReferralSignup] Successfully linked Referee #{$userId} to Referrer #{$coupon->owner_user_id} via Code {$coupon->code}");

            return $signup;
        });
    }

    /**
     * Evaluate qualifying payment and create a pending referral payout with hold period.
     */
    public function checkAndCreatePendingPayout(
        int|User $user,
        string $paymentReferenceType, // 'subscription_payment' | 'topup'
        string $paymentReferenceId,
        float $paymentAmount
    ): ?ReferralPayout {
        $userId = $user instanceof User ? $user->id : $user;
        $userObj = $user instanceof User ? $user : User::find($userId);

        // 1. Check if user was referred
        $signup = ReferralSignup::where('referee_user_id', $userId)->first();
        if (!$signup || !$signup->referrer_user_id) {
            return null;
        }

        // 2. Check if a payout already exists for this referee (ONE-TIME payout rule)
        $existingPayout = ReferralPayout::where('referee_user_id', $userId)->first();
        if ($existingPayout) {
            Log::info("[ReferralPayout] Referee #{$userId} already generated payout #{$existingPayout->id}. Skipping duplicate.");
            return null;
        }

        $settings = $this->settingsService->getSettings();
        if (!$settings['is_enabled']) {
            return null;
        }

        // 3. Minimum qualifying amount check
        if ($paymentAmount < (float) $settings['minimum_qualifying_amount']) {
            Log::info("[ReferralPayout] Payment ₹{$paymentAmount} below minimum qualifying threshold ₹{$settings['minimum_qualifying_amount']}. No payout generated.");
            return null;
        }

        // 4. Dynamic Trigger Matching
        $normalizedType = str_contains($paymentReferenceType, 'subscription') ? 'subscription' : 'topup';
        if ($normalizedType !== $settings['reward_trigger']) {
            Log::info("[ReferralPayout] Payment type '{$paymentReferenceType}' does not match current reward trigger '{$settings['reward_trigger']}'.");
            return null;
        }

        // 5. Determine reward amount (Per-Agent override on coupon_codes takes precedence over platform default)
        $referrerCoupon = $signup->couponCode
            ?? CouponCode::where('owner_user_id', $signup->referrer_user_id)->where('type', 'referral')->latest('id')->first();

        $rewardKind = $settings['reward_kind'];
        $rewardValue = (float) $settings['reward_value'];

        if ($referrerCoupon && $referrerCoupon->discount_value !== null && (float) $referrerCoupon->discount_value > 0) {
            $rewardKind = $referrerCoupon->discount_kind ?: $rewardKind;
            $rewardValue = (float) $referrerCoupon->discount_value;
        }

        if ($rewardKind === 'percentage') {
            $rewardAmount = round(($paymentAmount * $rewardValue) / 100, 2);
        } else {
            $rewardAmount = round($rewardValue, 2);
        }

        if ($rewardAmount <= 0) {
            return null;
        }

        $holdDays = (int) $settings['hold_period_days'];
        $holdExpiresAt = now()->addDays($holdDays);

        return DB::transaction(function () use (
            $signup,
            $userId,
            $referrerCoupon,
            $paymentReferenceType,
            $paymentReferenceId,
            $rewardAmount,
            $holdExpiresAt,
            $holdDays,
            $paymentAmount,
            $userObj
        ) {
            $payout = ReferralPayout::create([
                'referral_coupon_code_id' => $referrerCoupon?->id,
                'referrer_user_id' => $signup->referrer_user_id,
                'referee_user_id' => $userId,
                'qualifying_payment_reference_type' => $paymentReferenceType,
                'qualifying_payment_reference_id' => (string) $paymentReferenceId,
                'reward_amount' => $rewardAmount,
                'status' => 'pending',
                'hold_expires_at' => $holdExpiresAt,
            ]);

            Log::info("[ReferralPayout] Created pending payout #{$payout->id} (₹{$rewardAmount}) for Referrer #{$signup->referrer_user_id} with hold until {$holdExpiresAt}");

            // Dispatch referral.reward_pending notification to Referrer
            $referrer = $payout->referrer;
            if ($referrer) {
                try {
                    $this->notificationDispatcher->dispatch('referral.reward_pending', $referrer, [
                        'agent_name' => $referrer->name,
                        'referee_name' => $userObj?->name ?? 'Referred Agent',
                        'payment_amount' => number_format($paymentAmount, 2),
                        'reward_amount' => number_format($rewardAmount, 2),
                        'hold_days' => $holdDays,
                    ]);
                } catch (\Throwable $e) {
                    Log::error("[ReferralPayout] Failed to dispatch referral.reward_pending: " . $e->getMessage());
                }
            }

            return $payout;
        });
    }

    /**
     * Process expired hold periods and credit referrer wallets.
     * Scheduled daily command.
     */
    public function processExpiredHoldPeriods(): int
    {
        $maturedPayouts = ReferralPayout::where('status', 'pending')
            ->where('hold_expires_at', '<=', now())
            ->get();

        $processedCount = 0;

        foreach ($maturedPayouts as $payout) {
            DB::transaction(function () use ($payout, &$processedCount) {
                $locked = ReferralPayout::where('id', $payout->id)->lockForUpdate()->first();
                if (!$locked || $locked->status !== 'pending') {
                    return;
                }

                $referrer = $locked->referrer;
                if (!$referrer) {
                    $locked->update([
                        'status' => 'cancelled',
                        'clawback_reason' => 'referrer_account_deleted',
                    ]);
                    return;
                }

                // Credit referrer's wallet
                $walletTx = $this->walletService->credit(
                    user: $referrer,
                    amount: (float) $locked->reward_amount,
                    source: 'referral_bonus_paid',
                    referenceType: ReferralPayout::class,
                    referenceId: (string) $locked->id,
                    description: "Referral bonus for referring {$locked->referee?->name} (#{$locked->referee_user_id})"
                );

                $locked->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'wallet_transaction_id' => $walletTx?->id,
                ]);

                $processedCount++;

                // Dispatch referral.reward_paid notification
                try {
                    $this->notificationDispatcher->dispatch('referral.reward_paid', $referrer, [
                        'agent_name' => $referrer->name,
                        'referee_name' => $locked->referee?->name ?? 'Referred Agent',
                        'reward_amount' => number_format($locked->reward_amount, 2),
                    ]);
                } catch (\Throwable $e) {
                    Log::error("[ReferralPayout] Failed to dispatch referral.reward_paid: " . $e->getMessage());
                }
            });
        }

        return $processedCount;
    }

    /**
     * Handle clawback on refund or mid-cycle downgrade.
     */
    public function handleClawback(string $paymentReferenceType, string $paymentReferenceId, string $reason): int
    {
        $payouts = ReferralPayout::where('qualifying_payment_reference_type', $paymentReferenceType)
            ->where('qualifying_payment_reference_id', (string) $paymentReferenceId)
            ->get();

        $clawbackCount = 0;

        foreach ($payouts as $payout) {
            DB::transaction(function () use ($payout, $reason, &$clawbackCount) {
                $locked = ReferralPayout::where('id', $payout->id)->lockForUpdate()->first();
                if (!$locked) {
                    return;
                }

                $referrer = $locked->referrer;

                if ($locked->status === 'pending') {
                    // CASE A: Within hold period -> Cancel payout with zero wallet action
                    $locked->update([
                        'status' => 'cancelled',
                        'clawback_reason' => $reason,
                    ]);

                    $clawbackCount++;

                    if ($referrer) {
                        try {
                            $this->notificationDispatcher->dispatch('referral.reward_cancelled', $referrer, [
                                'agent_name' => $referrer->name,
                                'referee_name' => $locked->referee?->name ?? 'Referred Agent',
                                'reward_amount' => number_format($locked->reward_amount, 2),
                                'reason' => $reason,
                            ]);
                        } catch (\Throwable $e) {
                            Log::error("[ReferralClawback] Failed to dispatch referral.reward_cancelled: " . $e->getMessage());
                        }
                    }
                } elseif ($locked->status === 'paid') {
                    // CASE B: Payout already paid -> Reverse credit from wallet (force negative if insufficient)
                    if ($referrer) {
                        $debitResult = $this->walletService->debit(
                            user: $referrer,
                            amount: (float) $locked->reward_amount,
                            source: 'referral_bonus_clawback',
                            referenceType: ReferralPayout::class,
                            referenceId: (string) $locked->id,
                            description: "Clawback of referral bonus #{$locked->id}: {$reason}"
                        );

                        // If insufficient funds, use adminAdjust force negative
                        if ($debitResult === DebitResult::INSUFFICIENT_BALANCE) {
                            $adminUser = auth()->check() ? auth()->user() : User::find(1);
                            $this->walletService->adminAdjust(
                                user: $referrer,
                                admin: $adminUser ?? $referrer,
                                type: WalletAdminAdjustmentType::DEDUCT,
                                amount: (float) $locked->reward_amount,
                                reason: "Clawback of referral bonus #{$locked->id}: {$reason}"
                            );
                        }
                    }

                    $locked->update([
                        'status' => 'clawed_back',
                        'clawed_back_at' => now(),
                        'clawback_reason' => $reason,
                    ]);

                    $clawbackCount++;

                    if ($referrer) {
                        try {
                            $this->notificationDispatcher->dispatch('referral.reward_clawed_back', $referrer, [
                                'agent_name' => $referrer->name,
                                'referee_name' => $locked->referee?->name ?? 'Referred Agent',
                                'reward_amount' => number_format($locked->reward_amount, 2),
                                'reason' => $reason,
                            ]);
                        } catch (\Throwable $e) {
                            Log::error("[ReferralClawback] Failed to dispatch referral.reward_clawed_back: " . $e->getMessage());
                        }
                    }
                }
            });
        }

        return $clawbackCount;
    }

    /**
     * Handle Referrer Account Deletion.
     * Cancels any pending payouts tied to this deleted referrer.
     */
    public function handleReferrerAccountDeleted(int|User $referrer): int
    {
        $referrerId = $referrer instanceof User ? $referrer->id : $referrer;

        return ReferralPayout::where('referrer_user_id', $referrerId)
            ->where('status', 'pending')
            ->update([
                'status' => 'cancelled',
                'clawback_reason' => 'referrer_account_deleted',
            ]);
    }

    /**
     * Get 360° referral statistics for an Agent dashboard.
     */
    public function getAgentReferralStats(int|User $user): array
    {
        $userId = $user instanceof User ? $user->id : $user;
        $userObj = $user instanceof User ? $user : User::findOrFail($userId);

        $activeCoupon = $this->getOrCreateActiveCode($userObj);

        $totalReferred = ReferralSignup::where('referrer_user_id', $userId)->count();

        $pendingRewards = (float) ReferralPayout::where('referrer_user_id', $userId)
            ->where('status', 'pending')
            ->sum('reward_amount');

        $paidRewards = (float) ReferralPayout::where('referrer_user_id', $userId)
            ->where('status', 'paid')
            ->sum('reward_amount');

        $payouts = ReferralPayout::where('referrer_user_id', $userId)
            ->with(['referee', 'couponCode'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return [
            'referral_code' => $activeCoupon->code,
            'is_active' => $activeCoupon->is_active,
            'share_url' => url('/register?ref=' . $activeCoupon->code),
            'total_referred' => $totalReferred,
            'pending_rewards' => $pendingRewards,
            'paid_rewards' => $paidRewards,
            'total_earned' => $paidRewards,
            'payouts' => $payouts,
        ];
    }

    /**
     * Get Admin Per-Agent Override.
     */
    public function getAdminOverride(int|User $user): array
    {
        $userId = $user instanceof User ? $user->id : $user;
        $coupon = CouponCode::where('owner_user_id', $userId)
            ->where('type', 'referral')
            ->latest('id')
            ->first();

        return [
            'has_override' => $coupon && $coupon->discount_value !== null,
            'discount_kind' => $coupon?->discount_kind,
            'discount_value' => $coupon?->discount_value,
            'is_active' => $coupon?->is_active ?? true,
            'coupon' => $coupon,
        ];
    }

    /**
     * Set Admin Per-Agent Override.
     */
    public function setAdminOverride(int|User $user, ?string $kind, ?float $value, ?bool $isActive = null): CouponCode
    {
        $userId = $user instanceof User ? $user->id : $user;
        $coupon = $this->getOrCreateActiveCode($userId);

        $coupon->update([
            'discount_kind' => $kind,
            'discount_value' => $value,
            'is_active' => $isActive !== null ? $isActive : $coupon->is_active,
        ]);

        return $coupon->fresh();
    }
}
