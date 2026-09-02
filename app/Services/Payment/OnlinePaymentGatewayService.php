<?php

namespace App\Services\Payment;

use App\Enums\MandateStatus;
use App\Enums\PaymentMode;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Events\PaymentFailedEvent;
use App\Events\PaymentMandateFailedEvent;
use App\Events\PaymentSuccessEvent;
use App\Models\Payment;
use App\Models\PaymentMandate;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Razorpay\Api\Api as RazorpayApi;

class OnlinePaymentGatewayService
{
    public function __construct(
        protected PaymentSettingsService $settings
    ) {}

    /**
     * Create an online payment gateway order (supporting Cashfree & Razorpay).
     */
    public function createOrder(User $user, float $amount, PaymentPurpose $purpose, ?string $mandateId = null, array $meta = []): array
    {
        if (!$this->settings->isModeEnabled(PaymentMode::PG)) {
            throw new \InvalidArgumentException('Online Payment Gateway is currently disabled by administrator.');
        }

        $minAmount = $this->settings->getMinAmount();
        if ($amount < $minAmount) {
            throw new \InvalidArgumentException("Payment amount must be at least ₹{$minAmount}.");
        }

        $activeDriver = $this->settings->getActivePgDriver();

        if ($activeDriver === 'razorpay') {
            return $this->createRazorpayOrder($user, $amount, $purpose, $meta);
        }

        return $this->createCashfreeOrder($user, $amount, $purpose, $meta);
    }

    /**
     * Create a Razorpay Order.
     */
    protected function createRazorpayOrder(User $user, float $amount, PaymentPurpose $purpose, array $meta = []): array
    {
        $keyId = $this->settings->getRazorpayKeyId();
        $keySecret = $this->settings->getRazorpayKeySecret();
        $amountPaise = (int) round($amount * 100);

        $gatewayOrderId = 'order_' . Str::random(14);

        $payment = Payment::create([
            'user_id' => $user->id,
            'mode' => PaymentMode::PG,
            'purpose' => $purpose,
            'amount' => $amount,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING,
            'gateway_order_id' => $gatewayOrderId,
            'meta' => $meta,
        ]);

        try {
            if (class_exists(RazorpayApi::class) && !empty($keyId) && !empty($keySecret) && $keyId !== 'rzp_test_nbpdcl_saas') {
                $api = new RazorpayApi($keyId, $keySecret);
                $razorpayOrder = $api->order->create([
                    'receipt' => 'rcpt_' . $payment->id,
                    'amount' => $amountPaise,
                    'currency' => 'INR',
                    'notes' => [
                        'payment_id' => (string) $payment->id,
                        'user_id' => (string) $user->id,
                        'purpose' => $purpose->value,
                    ],
                ]);

                if (isset($razorpayOrder['id'])) {
                    $gatewayOrderId = $razorpayOrder['id'];
                    $payment->update(['gateway_order_id' => $gatewayOrderId]);
                }
            }
        } catch (\Throwable $e) {
            if (app()->isProduction() && config('services.razorpay.key_id') && !str_starts_with(config('services.razorpay.key_id'), 'rzp_test_')) {
                throw new \RuntimeException("Razorpay order creation failed in production: " . $e->getMessage(), 0, $e);
            }
            Log::warning('Razorpay API create order error (falling back to mock ID for test/sandbox)', [
                'message' => $e->getMessage(),
            ]);
        }

        $checkoutConfig = [
            'gateway' => 'razorpay',
            'key' => $keyId,
            'order_id' => $gatewayOrderId,
            'amount' => round($amount, 2),
            'amount_paise' => $amountPaise,
            'currency' => 'INR',
            'name' => config('app.name', 'NBPDCL SaaS Billing'),
            'description' => $purpose->label(),
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => $user->phone ?? '',
            'purpose' => $purpose->label(),
            'notes' => [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'purpose' => $purpose->value,
            ],
            'meta' => $meta,
            'theme' => [
                'color' => '#4f46e5', // Indigo brand
            ],
        ];

        return [
            'payment' => $payment,
            'checkout_config' => $checkoutConfig,
        ];
    }

    /**
     * Create a Cashfree Order.
     */
    protected function createCashfreeOrder(User $user, float $amount, PaymentPurpose $purpose, array $meta = []): array
    {
        $gatewayOrderId = 'cf_ord_' . Str::lower(Str::random(16));

        $payment = Payment::create([
            'user_id' => $user->id,
            'mode' => PaymentMode::PG,
            'purpose' => $purpose,
            'amount' => $amount,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING,
            'gateway_order_id' => $gatewayOrderId,
            'meta' => $meta,
        ]);

        $appId = $this->settings->getCashfreeAppId();
        $secretKey = $this->settings->getCashfreeSecretKey();
        $baseUrl = $this->settings->getCashfreeBaseUrl();
        $apiVersion = $this->settings->getCashfreeApiVersion();
        $environment = $this->settings->getCashfreeEnvironment();

        $returnUrl = url('/payments/verify') . '?order_id={order_id}';
        $notifyUrl = url('/webhooks/payments/cashfree');

        $phone = preg_replace('/[^0-9]/', '', (string) ($user->phone ?? '9999999999'));
        if (strlen($phone) < 10) {
            $phone = '9999999999';
        }

        $requestPayload = [
            'order_id' => $gatewayOrderId,
            'order_amount' => round($amount, 2),
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id' => 'user_' . $user->id,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => substr($phone, -10),
            ],
            'order_meta' => [
                'return_url' => $returnUrl,
                'notify_url' => $notifyUrl,
            ],
            'order_note' => $purpose->label() . ' - User #' . $user->id,
            'order_tags' => [
                'payment_id' => (string) $payment->id,
                'user_id' => (string) $user->id,
                'purpose' => $purpose->value,
            ],
        ];

        $paymentSessionId = null;
        $cfOrderId = null;

        try {
            $response = Http::timeout(10)->withHeaders([
                'x-client-id' => $appId,
                'x-client-secret' => $secretKey,
                'x-api-version' => $apiVersion,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post("{$baseUrl}/orders", $requestPayload);

            if ($response->successful()) {
                $responseData = $response->json();
                $paymentSessionId = $responseData['payment_session_id'] ?? null;
                $cfOrderId = $responseData['cf_order_id'] ?? null;
            } else {
                Log::warning('Cashfree create order API non-200 response', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);
                if (app()->isProduction() || $environment === 'production') {
                    throw new \RuntimeException("Cashfree order creation failed in production with status: {$response->status()}");
                }
            }
        } catch (\Throwable $e) {
            if (app()->isProduction() || $environment === 'production') {
                throw new \RuntimeException("Cashfree API connection error in production: " . $e->getMessage(), 0, $e);
            }
            Log::warning('Cashfree API connection error (falling back to mock session for test/sandbox)', [
                'message' => $e->getMessage(),
            ]);
        }

        if (empty($paymentSessionId)) {
            $paymentSessionId = 'session_' . Str::random(32);
        }

        $checkoutConfig = [
            'gateway' => 'cashfree',
            'order_id' => $gatewayOrderId,
            'cf_order_id' => $cfOrderId,
            'payment_session_id' => $paymentSessionId,
            'environment' => $environment,
            'amount' => round($amount, 2),
            'amount_paise' => (int) round($amount * 100),
            'currency' => 'INR',
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => $phone,
            'purpose' => $purpose->label(),
        ];

        return [
            'payment' => $payment,
            'checkout_config' => $checkoutConfig,
        ];
    }

    /**
     * Verify Webhook Signature for Cashfree or Razorpay.
     */
    public function verifyWebhookSignature(string $rawPayload, string $signature, ?string $timestamp = null): bool
    {
        // 1. Cashfree Signature verification (timestamp + rawPayload base64)
        $cfSecret = $this->settings->getCashfreeSecretKey();
        if (!empty($cfSecret)) {
            if ($timestamp !== null) {
                $expectedCf = base64_encode(hash_hmac('sha256', $timestamp . $rawPayload, $cfSecret, true));
                if (hash_equals($expectedCf, $signature)) {
                    return true;
                }
            }

            $expectedDirect = base64_encode(hash_hmac('sha256', $rawPayload, $cfSecret, true));
            if (hash_equals($expectedDirect, $signature)) {
                return true;
            }

            $expectedHex = hash_hmac('sha256', $rawPayload, $cfSecret);
            if (hash_equals($expectedHex, $signature)) {
                return true;
            }
        }

        // 2. Razorpay Signature verification (rawPayload HMAC SHA256 hex)
        $rzpSecret = $this->settings->getRazorpayWebhookSecret();
        if (!empty($rzpSecret)) {
            $expectedRzp = hash_hmac('sha256', $rawPayload, $rzpSecret);
            if (hash_equals($expectedRzp, $signature)) {
                return true;
            }

            // Using official Razorpay SDK utility if available
            try {
                if (class_exists(RazorpayApi::class)) {
                    $api = new RazorpayApi($this->settings->getRazorpayKeyId(), $this->settings->getRazorpayKeySecret());
                    $api->utility->verifyWebhookSignature($rawPayload, $signature, $rzpSecret);
                    return true;
                }
            } catch (\Throwable $e) {
                // Ignore failure and fallback
            }
        }

        return false;
    }

    /**
     * Check if a webhook signing secret is configured in settings or environment.
     */
    public function hasConfiguredWebhookSecret(): bool
    {
        return !empty($this->settings->getCashfreeSecretKey()) 
            || !empty($this->settings->getRazorpayWebhookSecret())
            || !empty(config('services.cashfree.secret_key'))
            || !empty(config('services.razorpay.webhook_secret'));
    }

    /**
     * Verify Razorpay Payment Client Signature on return / callback.
     */
    public function verifyRazorpayPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        $keySecret = $this->settings->getRazorpayKeySecret();
        if (empty($keySecret)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $keySecret);
        if (hash_equals($expectedSignature, $signature)) {
            return true;
        }

        try {
            if (class_exists(RazorpayApi::class)) {
                $api = new RazorpayApi($this->settings->getRazorpayKeyId(), $keySecret);
                $api->utility->verifyPaymentSignature([
                    'razorpay_order_id' => $orderId,
                    'razorpay_payment_id' => $paymentId,
                    'razorpay_signature' => $signature,
                ]);
                return true;
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }

    /**
     * Process incoming Cashfree or Razorpay webhook payload with strict Idempotency protection.
     */
    public function processWebhook(array $payload): array
    {
        // 1. Determine Event Type
        $event = $payload['type'] ?? ($payload['event'] ?? 'PAYMENT_SUCCESS_WEBHOOK');

        // 2. Extract Cashfree vs Razorpay Data Blocks
        $orderData = $payload['data']['order'] ?? ($payload['payload']['order']['entity'] ?? []);
        $paymentData = $payload['data']['payment'] ?? ($payload['payload']['payment']['entity'] ?? $payload);

        $gatewayOrderId = $orderData['order_id'] ?? ($paymentData['order_id'] ?? ($payload['order_id'] ?? null));
        $gatewayPaymentId = $paymentData['cf_payment_id'] ?? ($paymentData['id'] ?? ($payload['payment_id'] ?? null));
        $paymentStatus = strtoupper($paymentData['payment_status'] ?? ($paymentData['status'] ?? ''));

        // 3. Find payment record strictly by gateway IDs or tagged payment ID
        $payment = null;
        if ($gatewayOrderId) {
            $payment = Payment::where('gateway_order_id', $gatewayOrderId)->first();
        }
        if (!$payment && $gatewayPaymentId) {
            $payment = Payment::where('gateway_payment_id', (string) $gatewayPaymentId)->first();
        }
        if (!$payment && isset($orderData['order_tags']['payment_id'])) {
            $payment = Payment::find($orderData['order_tags']['payment_id']);
        }
        if (!$payment && isset($paymentData['notes']['payment_id'])) {
            $payment = Payment::find($paymentData['notes']['payment_id']);
        }

        // 4. Handle Mandate / Subscription failure events
        if (in_array($event, ['subscription.charged_failed', 'mandate.failed', 'SUBSCRIPTION_CHARGE_FAILED'], true)) {
            $mandateId = $payload['payload']['subscription']['entity']['id'] ?? ($payload['data']['subscription']['subscription_id'] ?? ($payload['mandate_id'] ?? null));
            if ($mandateId) {
                $mandate = PaymentMandate::where('gateway_mandate_id', $mandateId)->first();
                if ($mandate) {
                    $mandate->update(['status' => MandateStatus::FAILED]);
                    $desc = $paymentData['error_description'] ?? ($payload['error_description'] ?? 'Auto-debit mandate failed');
                    event(new PaymentMandateFailedEvent($mandate, $desc));
                    return ['status' => 'mandate_failed_processed', 'mandate_id' => $mandate->id];
                }
            }
        }

        if (!$payment) {
            Log::warning('Payment webhook received for unknown payment record', ['payload' => $payload]);
            return ['status' => 'payment_not_found'];
        }

        // 5. Idempotency Check: If already SUCCESS, return immediately without re-firing
        if ($payment->status === PaymentStatus::SUCCESS) {
            Log::info("Payment #{$payment->id} already processed successfully. Idempotent skip.");
            return ['status' => 'already_processed', 'payment_id' => $payment->id];
        }

        // 6. Handle SUCCESS Event
        if ($event === 'PAYMENT_SUCCESS_WEBHOOK' || $paymentStatus === 'SUCCESS' || in_array($event, ['payment.captured', 'payment.authorized', 'order.paid'], true)) {
            $payment->update([
                'status' => PaymentStatus::SUCCESS,
                'gateway_payment_id' => $gatewayPaymentId ? (string) $gatewayPaymentId : $payment->gateway_payment_id,
                'verified_at' => now(),
            ]);

            event(new PaymentSuccessEvent($payment, $gatewayPaymentId ? (string) $gatewayPaymentId : null));

            return ['status' => 'success_processed', 'payment_id' => $payment->id];
        }

        // 7. Handle FAILED Event
        if ($event === 'PAYMENT_FAILED_WEBHOOK' || $event === 'PAYMENT_USER_DROPPED_WEBHOOK' || $paymentStatus === 'FAILED' || in_array($event, ['payment.failed'], true)) {
            $reason = $paymentData['payment_message'] ?? ($paymentData['error_description'] ?? ($paymentData['error_reason'] ?? 'Payment failed or was dropped at gateway.'));
            
            $payment->update([
                'status' => PaymentStatus::FAILED,
                'gateway_payment_id' => $gatewayPaymentId ? (string) $gatewayPaymentId : $payment->gateway_payment_id,
                'rejection_reason' => $reason,
            ]);

            event(new PaymentFailedEvent($payment, $reason));

            return ['status' => 'failed_processed', 'payment_id' => $payment->id];
        }

        return ['status' => 'unhandled_event', 'event' => $event];
    }

    /**
     * Verify payment status directly with Cashfree or Razorpay API (Return URL / Polling verification).
     */
    public function verifyOrderWithCashfree(string $orderId): ?Payment
    {
        $payment = Payment::where('gateway_order_id', $orderId)->first();
        if (!$payment) {
            return null;
        }

        if ($payment->status === PaymentStatus::SUCCESS) {
            return $payment;
        }

        $appId = $this->settings->getCashfreeAppId();
        $secretKey = $this->settings->getCashfreeSecretKey();
        $baseUrl = $this->settings->getCashfreeBaseUrl();
        $apiVersion = $this->settings->getCashfreeApiVersion();

        try {
            $response = Http::timeout(10)->withHeaders([
                'x-client-id' => $appId,
                'x-client-secret' => $secretKey,
                'x-api-version' => $apiVersion,
                'Accept' => 'application/json',
            ])->get("{$baseUrl}/orders/{$orderId}");

            if ($response->successful()) {
                $data = $response->json();
                $orderStatus = strtoupper($data['order_status'] ?? '');

                if ($orderStatus === 'PAID') {
                    $cfPaymentId = null;
                    try {
                        $paymentsRes = Http::timeout(5)->withHeaders([
                            'x-client-id' => $appId,
                            'x-client-secret' => $secretKey,
                            'x-api-version' => $apiVersion,
                            'Accept' => 'application/json',
                        ])->get("{$baseUrl}/orders/{$orderId}/payments");

                        if ($paymentsRes->successful()) {
                            $paymentsList = $paymentsRes->json();
                            if (is_array($paymentsList) && !empty($paymentsList)) {
                                foreach ($paymentsList as $pItem) {
                                    if (strtoupper($pItem['payment_status'] ?? '') === 'SUCCESS') {
                                        $cfPaymentId = (string) ($pItem['cf_payment_id'] ?? $pItem['payment_id'] ?? null);
                                        break;
                                    }
                                }
                                if (!$cfPaymentId && isset($paymentsList[0]['cf_payment_id'])) {
                                    $cfPaymentId = (string) $paymentsList[0]['cf_payment_id'];
                                }
                            }
                        }
                    } catch (\Throwable) {
                        // ignore secondary query failure
                    }

                    $resolvedPaymentId = $cfPaymentId ?: (string) ($data['cf_payment_id'] ?? $data['payment_id'] ?? $payment->gateway_payment_id ?: $data['cf_order_id'] ?? $orderId);

                    $payment->update([
                        'status' => PaymentStatus::SUCCESS,
                        'gateway_payment_id' => $resolvedPaymentId,
                        'verified_at' => now(),
                    ]);

                    event(new PaymentSuccessEvent($payment, $resolvedPaymentId));
                } elseif (in_array($orderStatus, ['EXPIRED', 'FAILED', 'TERMINATED'], true)) {
                    $payment->update([
                        'status' => PaymentStatus::FAILED,
                        'rejection_reason' => "Cashfree order status: {$orderStatus}",
                    ]);

                    event(new PaymentFailedEvent($payment, "Cashfree order status: {$orderStatus}"));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Cashfree verify order API call failed', ['error' => $e->getMessage()]);
        }

        return $payment->fresh();
    }
}
