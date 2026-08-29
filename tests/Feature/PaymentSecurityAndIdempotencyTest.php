<?php

namespace Tests\Feature;

use App\Enums\PaymentMode;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Events\PaymentSuccessEvent;
use App\Models\CouponCode;
use App\Models\CouponRedemption;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\User;
use App\Services\Coupon\CouponRedemptionService;
use App\Services\Payment\PaymentSettingsService;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentSecurityAndIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $agent;
    protected WalletService $walletService;
    protected CouponRedemptionService $couponRedemptionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->walletService = app(WalletService::class);
        $this->couponRedemptionService = app(CouponRedemptionService::class);

        $agentRole = Role::firstOrCreate(['name' => 'user']);
        $this->agent = User::factory()->create([
            'email' => 'agent_security@example.com',
            'status' => 'active',
        ]);
        $this->agent->assignRole($agentRole);
        $this->walletService->credit($this->agent, 1000.00, 'test_initial_balance');
    }

    public function test_webhook_without_signature_is_rejected(): void
    {
        // Configure a webhook secret in settings
        $settings = app(PaymentSettingsService::class);
        $settings->updateSettings([
            'cashfree_secret_key' => 'cf_secret_key_12345',
        ]);

        $payload = [
            'type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'data' => [
                'order' => ['order_id' => 'order_fake_123'],
                'payment' => ['cf_payment_id' => 'cf_pay_123', 'payment_status' => 'SUCCESS'],
            ],
        ];

        // Send POST webhook without any signature header
        $response = $this->postJson(route('webhooks.payments.cashfree'), $payload);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid or missing webhook signature.']);
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        $settings = app(PaymentSettingsService::class);
        $settings->updateSettings([
            'cashfree_secret_key' => 'cf_secret_key_12345',
        ]);

        $payload = [
            'type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'data' => [
                'order' => ['order_id' => 'order_fake_123'],
                'payment' => ['cf_payment_id' => 'cf_pay_123', 'payment_status' => 'SUCCESS'],
            ],
        ];

        $response = $this->postJson(route('webhooks.payments.cashfree'), $payload, [
            'x-webhook-signature' => 'invalid_tampered_signature_string',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid or missing webhook signature.']);
    }

    public function test_webhook_with_valid_signature_is_accepted_and_processed(): void
    {
        $secret = 'cf_secret_key_test_999';
        $settings = app(PaymentSettingsService::class);
        $settings->updateSettings([
            'cashfree_secret_key' => $secret,
        ]);

        $payment = Payment::create([
            'user_id' => $this->agent->id,
            'mode' => PaymentMode::PG,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => 500.00,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING,
            'gateway_order_id' => 'cf_order_valid_777',
        ]);

        $payload = [
            'type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'data' => [
                'order' => ['order_id' => 'cf_order_valid_777'],
                'payment' => ['cf_payment_id' => 'cf_pay_777', 'payment_status' => 'SUCCESS'],
            ],
        ];

        $rawBody = json_encode($payload);
        $validSignature = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        $response = $this->call(
            'POST',
            route('webhooks.payments.cashfree'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_WEBHOOK_SIGNATURE' => $validSignature,
            ],
            $rawBody
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertEquals(PaymentStatus::SUCCESS, $payment->fresh()->status);
    }

    public function test_webhook_does_not_fall_back_to_latest_user_payment_for_unknown_order(): void
    {
        $secret = 'cf_secret_key_test_999';
        $settings = app(PaymentSettingsService::class);
        $settings->updateSettings([
            'cashfree_secret_key' => $secret,
        ]);

        // Agent's pending payment
        $payment = Payment::create([
            'user_id' => $this->agent->id,
            'mode' => PaymentMode::PG,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => 500.00,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING,
            'gateway_order_id' => 'legit_order_id_100',
        ]);

        // Attacker payload with unmatching order_id but matching customer_id
        $attackerPayload = [
            'type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'data' => [
                'order' => ['order_id' => 'unknown_random_attacker_order_999'],
                'payment' => ['cf_payment_id' => 'attacker_pay_id', 'payment_status' => 'SUCCESS'],
                'customer_details' => ['customer_id' => 'user_' . $this->agent->id],
            ],
        ];

        $rawBody = json_encode($attackerPayload);
        $validSignature = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        $response = $this->call(
            'POST',
            route('webhooks.payments.cashfree'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_WEBHOOK_SIGNATURE' => $validSignature,
            ],
            $rawBody
        );

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('payment_not_found', $data['result']['status']);

        // Legit payment must remain PENDING (not marked SUCCESS)
        $this->assertEquals(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    public function test_topup_coupon_bonus_is_strictly_idempotent_on_duplicate_events(): void
    {
        $coupon = CouponCode::create([
            'code' => 'TOPUPSEC20',
            'type' => 'topup_bonus',
            'usage_limit_per_user' => 1,
            'is_active' => true,
        ]);

        $coupon->slabs()->create([
            'min_amount' => 500,
            'max_amount' => 5000,
            'bonus_percent' => 20, // 20% on 1000 = ₹200 bonus
        ]);

        $payment = Payment::create([
            'user_id' => $this->agent->id,
            'mode' => PaymentMode::PG,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => 1000.00,
            'currency' => 'INR',
            'status' => PaymentStatus::SUCCESS,
            'gateway_payment_id' => 'pay_idem_topup_123',
            'meta' => [
                'coupon_code' => 'TOPUPSEC20',
                'bonus_percent' => 20,
                'bonus_amount' => 200.00,
            ],
        ]);

        $initialBalance = $this->walletService->getBalance($this->agent); // 1000.00

        // Fire PaymentSuccessEvent THREE times (simulating webhook retry + verify race)
        event(new PaymentSuccessEvent($payment, 'pay_idem_topup_123'));
        event(new PaymentSuccessEvent($payment, 'pay_idem_topup_123'));
        event(new PaymentSuccessEvent($payment, 'pay_idem_topup_123'));

        // Expected balance: 1000 (initial) + 1000 (topup exactly once) + 200 (bonus exactly once) = 2200
        $finalBalance = $this->walletService->getBalance($this->agent);
        $this->assertEquals(2200.00, $finalBalance);

        // Exactly 1 redemption record in database
        $redemptionsCount = CouponRedemption::where('coupon_code_id', $coupon->id)
            ->where('redeemed_for_reference_id', (string)$payment->id)
            ->count();
        $this->assertEquals(1, $redemptionsCount);

        // Coupon times_used_total must be exactly 1
        $this->assertEquals(1, $coupon->fresh()->times_used_total);
    }

    public function test_subscription_coupon_redemption_is_strictly_idempotent_by_reference(): void
    {
        $coupon = CouponCode::create([
            'code' => 'SUBSEC100',
            'type' => 'subscription_discount',
            'discount_kind' => 'flat',
            'discount_value' => 100.00,
            'usage_limit_per_user' => 1,
            'is_active' => true,
        ]);

        $referenceId = 'sub_ref_idem_999';

        // First redemption
        $redemption1 = $this->couponRedemptionService->redeemForSubscription(
            coupon: $coupon,
            user: $this->agent,
            originalAmount: 500.00,
            referenceId: $referenceId
        );

        // Second redemption with same reference
        $redemption2 = $this->couponRedemptionService->redeemForSubscription(
            coupon: $coupon,
            user: $this->agent,
            originalAmount: 500.00,
            referenceId: $referenceId
        );

        $this->assertEquals($redemption1->id, $redemption2->id);
        $this->assertEquals(1, $coupon->fresh()->times_used_total);
        $this->assertEquals(1, CouponRedemption::where('coupon_code_id', $coupon->id)->count());
    }

    public function test_redeem_for_topup_rejects_expired_or_deactivated_coupon(): void
    {
        $coupon = CouponCode::create([
            'code' => 'EXPIREDTOPUP',
            'type' => 'topup_bonus',
            'usage_limit_per_user' => 1,
            'is_active' => false, // Deactivated before redemption
        ]);

        $coupon->slabs()->create([
            'min_amount' => 100,
            'bonus_percent' => 10,
        ]);

        $payment = Payment::create([
            'user_id' => $this->agent->id,
            'mode' => PaymentMode::PG,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => 500.00,
            'currency' => 'INR',
            'status' => PaymentStatus::SUCCESS,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Coupon 'EXPIREDTOPUP' is no longer active or valid.");

        $this->couponRedemptionService->redeemForTopup(
            coupon: $coupon,
            user: $this->agent,
            topupAmount: 500.00,
            payment: $payment
        );
    }
}
