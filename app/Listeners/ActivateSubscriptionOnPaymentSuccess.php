<?php

namespace App\Listeners;

use App\Enums\PaymentAuditAction;
use App\Enums\PaymentPurpose;
use App\Events\PaymentSuccessEvent;
use App\Models\CouponCode;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Services\Billing\PlanChangeService;
use App\Services\Coupon\CouponRedemptionService;
use App\Services\Plan\PlanService;
use App\Services\Referral\ReferralService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\Log;

class ActivateSubscriptionOnPaymentSuccess
{
    public function __construct(
        protected PlanService $planService,
        protected PlanChangeService $planChangeService,
        protected ?WalletService $walletService = null
    ) {
        $this->walletService = $walletService ?? app(WalletService::class);
    }

    /**
     * Handle incoming PaymentSuccessEvent for direct subscription activations.
     */
    public function handle(PaymentSuccessEvent $event): void
    {
        $payment = $event->payment;
        $meta = $payment->meta ?? [];

        $purposeValue = $payment->purpose instanceof PaymentPurpose
            ? $payment->purpose->value
            : (string) $payment->purpose;

        $isDirect = ($purposeValue === PaymentPurpose::DIRECT_SUBSCRIPTION->value || $purposeValue === 'direct_subscription');
        $isAutoActivateTopup = ($purposeValue === PaymentPurpose::WALLET_TOPUP->value || $purposeValue === 'wallet_topup') && ! empty($meta['auto_activate']) && ! empty($meta['plan_id']);

        if (! $isDirect && ! $isAutoActivateTopup) {
            return;
        }

        // 2. Strict Idempotency Check (Prevent duplicate activations for same payment)
        $alreadyProcessed = PaymentAuditLog::where('payment_id', $payment->id)
            ->where('notes', 'like', '[SUBSCRIPTION_ACTIVATED]%')
            ->exists();

        if ($alreadyProcessed) {
            Log::warning("[SubscriptionActivationListener] Payment #{$payment->id} direct subscription already processed. Skipping duplicate event.");

            return;
        }

        $user = $payment->user;
        if (! $user) {
            Log::warning("[SubscriptionActivationListener] Payment #{$payment->id} has no associated user. Skipping activation.");

            return;
        }

        $meta = $payment->meta ?? [];
        $planId = $meta['plan_id'] ?? null;
        $durationId = $meta['duration_id'] ?? null;

        $plan = null;
        $duration = null;

        if ($planId && $durationId) {
            $plan = Plan::find($planId);
            $duration = PlanDuration::where('id', $durationId)->where('plan_id', $planId)->first();
        } elseif ($planId) {
            $plan = Plan::find($planId);
            $duration = $plan?->durations()->where('is_active', true)->first();
        }

        // Smart fallback: If meta was omitted, attempt resolving plan by matching duration price to payment amount
        if (! $plan || ! $duration) {
            $matchingDuration = PlanDuration::where('final_price', (float) $payment->amount)
                ->where('is_active', true)
                ->with('plan')
                ->first();

            if ($matchingDuration && $matchingDuration->plan) {
                $plan = $matchingDuration->plan;
                $duration = $matchingDuration;
                Log::info("[SubscriptionActivationListener] Payment #{$payment->id} resolved plan via amount match: Plan #{$plan->id}, Duration #{$duration->id}");
            }
        }

        if (! $plan || ! $duration) {
            Log::warning("[SubscriptionActivationListener] Payment #{$payment->id} missing or unresolvable plan/duration. Skipping direct subscription activation.", [
                'meta' => $meta,
                'amount' => $payment->amount,
            ]);

            return;
        }

        $activeSubscription = $user->activeSubscription;
        $actionMode = $meta['action_mode'] ?? null;
        if (! $actionMode) {
            $actionMode = ($activeSubscription && $activeSubscription->plan_id !== $plan->id) ? 'shift' : 'extend';
        }

        try {
            if ($isAutoActivateTopup) {
                $requiredPrice = (float) $duration->final_price;
                $currentBal = (float) $this->walletService->getBalance($user);
                if ($currentBal >= $requiredPrice) {
                    $this->walletService->debit(
                        user: $user,
                        amount: $requiredPrice,
                        source: 'subscription_auto_activation',
                        referenceType: Payment::class,
                        referenceId: (string) $payment->id,
                        description: "Subscription Auto-Activation for Plan: {$plan->name} ({$duration->formatted_duration})"
                    );
                }
            }

            // If user has an active subscription to a DIFFERENT plan, upgrade/switch plans
            if ($activeSubscription && $activeSubscription->plan_id !== $plan->id) {
                $proration = $this->planChangeService->calculateProration($activeSubscription, $plan, $duration);
                if ($proration['amount_due'] >= 0 || $proration['is_upgrade']) {
                    $res = $this->planChangeService->upgradePlan(
                        $activeSubscription,
                        $plan,
                        $duration,
                        debitWallet: false,
                        payment: $payment
                    );
                    if (! ($res['success'] ?? false)) {
                        throw new \RuntimeException($res['message'] ?? 'Failed to upgrade plan.');
                    }
                    $subId = $res['subscription']->id;
                    $actionText = "Switched / Upgraded to plan {$plan->name} ({$duration->formatted_duration})";
                } else {
                    $res = $this->planChangeService->downgradePlan($activeSubscription, $plan, $duration);
                    if (! ($res['success'] ?? false)) {
                        throw new \RuntimeException($res['message'] ?? 'Failed to downgrade plan.');
                    }
                    $subId = $res['subscription']->id;
                    $actionText = "Switched / Downgraded to plan {$plan->name} ({$duration->formatted_duration})";
                }
            } else {
                $subscription = $this->planService->subscribeAgent($user, $plan, $duration);
                $subId = $subscription->id;
                $actionText = ($activeSubscription && $activeSubscription->plan_id === $plan->id)
                    ? "Extended plan {$plan->name} (+{$duration->formatted_duration})"
                    : ($isAutoActivateTopup
                        ? "Auto-activated plan {$plan->name} ({$duration->formatted_duration})"
                        : "Directly subscribed to plan {$plan->name} ({$duration->formatted_duration})");
            }

            $meta = $payment->meta ?? [];
            if (! empty($meta['coupon_code'])) {
                $coupon = CouponCode::where('code', $meta['coupon_code'])->first();
                if ($coupon && $coupon->type === 'subscription_discount') {
                    $redemptionService = app(CouponRedemptionService::class);
                    try {
                        $redemptionService->redeemForSubscription(
                            coupon: $coupon,
                            user: $user,
                            originalAmount: (float) ($meta['original_amount'] ?? $payment->amount),
                            referenceId: 'payment_'.$payment->id
                        );
                    } catch (\Throwable $e) {
                        Log::error("[SubscriptionActivationListener] Failed to redeem coupon for Payment #{$payment->id}: ".$e->getMessage());
                    }
                }
            }

            PaymentAuditLog::create([
                'payment_id' => $payment->id,
                'admin_id' => $payment->verified_by,
                'action' => PaymentAuditAction::APPROVED,
                'notes' => "[SUBSCRIPTION_ACTIVATED] {$actionText} (Subscription #{$subId}) upon Payment #{$payment->id} success.",
                'created_at' => now(),
            ]);

            // Refer & Earn: check if referee's first subscription payment qualifies for a referral reward
            try {
                app(ReferralService::class)->checkAndCreatePendingPayout(
                    user: $user,
                    paymentReferenceType: 'subscription_payment',
                    paymentReferenceId: 'payment_'.$payment->id,
                    paymentAmount: (float) $payment->amount
                );
            } catch (\Throwable $e) {
                Log::error("[SubscriptionActivationListener] Referral payout check error for payment #{$payment->id}: ".$e->getMessage());
            }

            Log::info("[SubscriptionActivationListener] Activated {$actionText} for User #{$user->id} via Payment #{$payment->id}.");
        } catch (\Throwable $e) {
            Log::error("[SubscriptionActivationListener] Failed to activate subscription for Payment #{$payment->id}: ".$e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
