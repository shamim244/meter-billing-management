<?php

namespace App\Listeners;

use App\Enums\PaymentAuditAction;
use App\Enums\PaymentPurpose;
use App\Events\PaymentSuccessEvent;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Services\Billing\PlanChangeService;
use App\Services\Plan\PlanService;
use Illuminate\Support\Facades\Log;

class ActivateSubscriptionOnPaymentSuccess
{
    public function __construct(
        protected PlanService $planService,
        protected PlanChangeService $planChangeService
    ) {}

    /**
     * Handle incoming PaymentSuccessEvent for direct subscription activations.
     */
    public function handle(PaymentSuccessEvent $event): void
    {
        $payment = $event->payment;

        $purposeValue = $payment->purpose instanceof PaymentPurpose
            ? $payment->purpose->value
            : (string) $payment->purpose;

        if ($purposeValue !== PaymentPurpose::DIRECT_SUBSCRIPTION->value && $purposeValue !== 'direct_subscription') {
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
        if (!$user) {
            Log::warning("[SubscriptionActivationListener] Payment #{$payment->id} has no associated user.");
            return;
        }

        $meta = $payment->meta ?? [];
        $planId = $meta['plan_id'] ?? null;
        $durationId = $meta['duration_id'] ?? null;

        if (!$planId || !$durationId) {
            Log::warning("[SubscriptionActivationListener] Payment #{$payment->id} missing plan_id or duration_id in meta.", $meta);
            return;
        }

        $plan = Plan::find($planId);
        $duration = PlanDuration::where('id', $durationId)->where('plan_id', $planId)->first();

        if (!$plan || !$duration) {
            Log::error("[SubscriptionActivationListener] Plan #{$planId} or Duration #{$durationId} not found for Payment #{$payment->id}.");
            return;
        }

        $activeSubscription = $user->activeSubscription;
        $meta = $payment->meta ?? [];
        $actionMode = $meta['action_mode'] ?? null;
        if (!$actionMode) {
            $actionMode = ($activeSubscription && $activeSubscription->plan_id !== $plan->id) ? 'shift' : 'extend';
        }

        try {
            if ($activeSubscription && $actionMode === 'shift') {
                $proration = $this->planChangeService->calculateProration($activeSubscription, $plan, $duration);
                if ($proration['is_upgrade']) {
                    $res = $this->planChangeService->upgradePlan($activeSubscription, $plan, $duration);
                    $subId = $res['subscription']->id ?? $activeSubscription->id;
                    $actionText = "Switched / Upgraded to plan {$plan->name} ({$duration->formatted_duration})";
                } else {
                    $res = $this->planChangeService->downgradePlan($activeSubscription, $plan, $duration);
                    $subId = $res['subscription']->id ?? $activeSubscription->id;
                    $actionText = "Switched / Downgraded to plan {$plan->name} ({$duration->formatted_duration})";
                }
            } else {
                $subscription = $this->planService->subscribeAgent($user, $plan, $duration);
                $subId = $subscription->id;
                $actionText = ($activeSubscription && $activeSubscription->plan_id === $plan->id)
                    ? "Extended plan {$plan->name} (+{$duration->formatted_duration})"
                    : "Directly subscribed to plan {$plan->name} ({$duration->formatted_duration})";
            }

            $meta = $payment->meta ?? [];
            if (!empty($meta['coupon_code'])) {
                $coupon = \App\Models\CouponCode::where('code', $meta['coupon_code'])->first();
                if ($coupon && $coupon->type === 'subscription_discount') {
                    $redemptionService = app(\App\Services\Coupon\CouponRedemptionService::class);
                    try {
                        $redemptionService->redeemForSubscription(
                            coupon: $coupon,
                            user: $user,
                            originalAmount: (float)($meta['original_amount'] ?? $payment->amount),
                            referenceId: 'payment_' . $payment->id
                        );
                    } catch (\Throwable $e) {
                        Log::error("[SubscriptionActivationListener] Failed to redeem coupon for Payment #{$payment->id}: " . $e->getMessage());
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
                app(\App\Services\Referral\ReferralService::class)->checkAndCreatePendingPayout(
                    user: $user,
                    paymentReferenceType: 'subscription_payment',
                    paymentReferenceId: 'payment_' . $payment->id,
                    paymentAmount: (float) $payment->amount
                );
            } catch (\Throwable $e) {
                Log::error("[SubscriptionActivationListener] Referral payout check error for payment #{$payment->id}: " . $e->getMessage());
            }

            Log::info("[SubscriptionActivationListener] Activated {$actionText} for User #{$user->id} via Payment #{$payment->id}.");
        } catch (\Throwable $e) {
            Log::error("[SubscriptionActivationListener] Failed to activate subscription for Payment #{$payment->id}: " . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
