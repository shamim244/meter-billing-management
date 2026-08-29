<?php

namespace App\Listeners;

use App\Enums\PaymentPurpose;
use App\Events\PaymentSuccessEvent;
use App\Models\Payment;
use App\Services\Wallet\WalletService;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Support\Facades\Log;

class CreditWalletOnPaymentSuccess
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Handle the incoming PaymentSuccessEvent.
     */
    public function handle(PaymentSuccessEvent $event): void
    {
        $payment = $event->payment;

        // 1. Only process top-up payments
        $purposeValue = $payment->purpose instanceof PaymentPurpose 
            ? $payment->purpose->value 
            : (string) $payment->purpose;

        if ($purposeValue !== PaymentPurpose::WALLET_TOPUP->value && $purposeValue !== 'wallet_topup') {
            return;
        }

        // 2. Strict Idempotency Check (Prevent duplicate credits for same payment)
        $alreadyCredited = Transaction::where('meta->source', 'payment_topup')
            ->where('meta->reference_type', Payment::class)
            ->where('meta->reference_id', (string) $payment->id)
            ->exists();

        if ($alreadyCredited) {
            Log::warning("[WalletListener] Payment #{$payment->id} already credited to wallet. Skipping duplicate event.");
            return;
        }

        // 3. Credit wallet balance
        $description = "Wallet Top-up via Payment #{$payment->id} (" . strtoupper($payment->mode instanceof \BackedEnum ? $payment->mode->value : (string)$payment->mode) . ")";
        
        $this->walletService->credit(
            user: $payment->user_id,
            amount: (float) $payment->amount,
            source: 'payment_topup',
            referenceType: Payment::class,
            referenceId: (string) $payment->id,
            description: $description
        );

        // 4. Check and credit Top-Up Bonus coupon if applied
        $meta = $payment->meta ?? [];
        if (!empty($meta['coupon_code'])) {
            $coupon = \App\Models\CouponCode::where('code', $meta['coupon_code'])->first();
            if ($coupon && $coupon->type === 'topup_bonus') {
                $redemptionService = app(\App\Services\Coupon\CouponRedemptionService::class);
                try {
                    $redemptionService->redeemForTopup(
                        coupon: $coupon,
                        user: $payment->user,
                        topupAmount: (float) $payment->amount,
                        payment: $payment
                    );
                    Log::info("[WalletListener] Successfully applied topup coupon #{$coupon->code} for Payment #{$payment->id}");
                } catch (\Throwable $e) {
                    Log::error("[WalletListener] Failed to apply topup coupon for Payment #{$payment->id}: " . $e->getMessage());
                }
            }
        }

        Log::info("[WalletListener] Successfully credited ₹{$payment->amount} to user #{$payment->user_id} for Payment #{$payment->id}");
    }
}
