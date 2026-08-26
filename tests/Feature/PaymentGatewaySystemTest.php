<?php

namespace Tests\Feature;

use App\Enums\MandateStatus;
use App\Enums\PaymentAuditAction;
use App\Enums\PaymentMode;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Events\ManualPaymentApprovedEvent;
use App\Events\ManualPaymentRejectedEvent;
use App\Events\ManualPaymentSubmittedEvent;
use App\Events\PaymentFailedEvent;
use App\Events\PaymentMandateFailedEvent;
use App\Events\PaymentSuccessEvent;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\PaymentMandate;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Payment\BankTransferPaymentService;
use App\Services\Payment\ManualUpiPaymentService;
use App\Services\Payment\OnlinePaymentGatewayService;
use App\Services\Payment\PaymentSettingsService;
use App\Services\Payment\PaymentVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentGatewaySystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;
    protected User $otherAgent;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);

        $this->admin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@nbpdcl-test.local',
            'status' => 'active',
        ]);
        $this->admin->assignRole('admin');

        $this->agent = User::factory()->create([
            'name' => 'Operator Alpha',
            'email' => 'operator.alpha@nbpdcl-test.local',
            'status' => 'active',
        ]);
        $this->agent->assignRole('user');

        $this->otherAgent = User::factory()->create([
            'name' => 'Operator Beta',
            'email' => 'operator.beta@nbpdcl-test.local',
            'status' => 'active',
        ]);
        $this->otherAgent->assignRole('user');

        // Reset default settings
        SystemSetting::set('payment_pg_enabled', true);
        SystemSetting::set('payment_manual_upi_enabled', true);
        SystemSetting::set('payment_bank_transfer_enabled', true);
        SystemSetting::set('payment_min_amount', 100.0);
        SystemSetting::set('payment_active_pg_driver', 'cashfree');
        SystemSetting::set('payment_cashfree_app_id', 'test_cf_app_id_123');
        SystemSetting::set('payment_cashfree_secret_key', 'test_cf_secret_key_456');
        SystemSetting::set('payment_cashfree_environment', 'sandbox');
        SystemSetting::set('payment_razorpay_key_id', 'rzp_test_nbpdcl_saas');
        SystemSetting::set('payment_razorpay_key_secret', 'test_razorpay_secret');
        SystemSetting::set('payment_razorpay_webhook_secret', 'test_razorpay_webhook_secret');
    }

    public function test_cashfree_pg_order_creation(): void
    {
        SystemSetting::set('payment_active_pg_driver', 'cashfree');
        $service = app(OnlinePaymentGatewayService::class);

        $order = $service->createOrder($this->agent, 500.0, PaymentPurpose::WALLET_TOPUP);

        $this->assertInstanceOf(Payment::class, $order['payment']);
        $this->assertEquals(PaymentMode::PG, $order['payment']->mode);
        $this->assertEquals(PaymentStatus::PENDING, $order['payment']->status);
        $this->assertEquals(500.0, (float) $order['payment']->amount);
        $this->assertStringStartsWith('cf_ord_', $order['payment']->gateway_order_id);
        
        $this->assertEquals('cashfree', $order['checkout_config']['gateway']);
        $this->assertEquals('sandbox', $order['checkout_config']['environment']);
        $this->assertNotEmpty($order['checkout_config']['payment_session_id']);
        $this->assertEquals(50000, $order['checkout_config']['amount_paise']);
    }

    public function test_razorpay_pg_order_creation(): void
    {
        SystemSetting::set('payment_active_pg_driver', 'razorpay');
        $service = app(OnlinePaymentGatewayService::class);

        $order = $service->createOrder($this->agent, 1000.0, PaymentPurpose::DIRECT_SUBSCRIPTION);

        $this->assertInstanceOf(Payment::class, $order['payment']);
        $this->assertEquals(PaymentMode::PG, $order['payment']->mode);
        $this->assertEquals(PaymentStatus::PENDING, $order['payment']->status);
        $this->assertEquals(1000.0, (float) $order['payment']->amount);
        $this->assertStringStartsWith('order_', $order['payment']->gateway_order_id);

        $this->assertEquals('razorpay', $order['checkout_config']['gateway']);
        $this->assertEquals('rzp_test_nbpdcl_saas', $order['checkout_config']['key']);
        $this->assertEquals(100000, $order['checkout_config']['amount_paise']);
    }

    public function test_razorpay_callback_signature_verification_and_success(): void
    {
        Event::fake([PaymentSuccessEvent::class]);

        $service = app(OnlinePaymentGatewayService::class);
        $orderId = 'order_test_rzp_12345';
        $paymentId = 'pay_test_rzp_67890';
        $secret = 'test_razorpay_secret';
        $signature = hash_hmac('sha256', $orderId . '|' . $paymentId, $secret);

        $payment = Payment::create([
            'user_id' => $this->agent->id,
            'mode' => PaymentMode::PG,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => 1000.0,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING,
            'gateway_order_id' => $orderId,
        ]);

        $this->assertTrue($service->verifyRazorpayPaymentSignature($orderId, $paymentId, $signature));

        // Call verify route as agent
        $response = $this->actingAs($this->agent)->get(route('payments.verify', [
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature,
        ]));

        $response->assertRedirect(route('payments.index'));
        $response->assertSessionHas('success');

        $payment->refresh();
        $this->assertEquals(PaymentStatus::SUCCESS, $payment->status);
        $this->assertEquals($paymentId, $payment->gateway_payment_id);
        $this->assertNotNull($payment->verified_at);

        Event::assertDispatched(PaymentSuccessEvent::class);
    }

    public function test_razorpay_webhook_signature_verification_and_success(): void
    {
        Event::fake([PaymentSuccessEvent::class]);

        $service = app(OnlinePaymentGatewayService::class);
        $order = $service->createOrder($this->agent, 1500.0, PaymentPurpose::WALLET_TOPUP);
        $payment = $order['payment'];

        $payload = [
            'entity' => 'event',
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_rzp_wh_8899',
                        'order_id' => $payment->gateway_order_id,
                        'amount' => 150000,
                        'currency' => 'INR',
                        'status' => 'captured',
                    ]
                ]
            ]
        ];

        $rawJson = json_encode($payload);
        $secretKey = 'test_razorpay_webhook_secret';
        $signature = hash_hmac('sha256', $rawJson, $secretKey);

        // Test signature validator
        $this->assertTrue($service->verifyWebhookSignature($rawJson, $signature));

        // Process webhook
        $result = $service->processWebhook($payload);
        $this->assertEquals('success_processed', $result['status']);

        $payment->refresh();
        $this->assertEquals(PaymentStatus::SUCCESS, $payment->status);
        $this->assertEquals('pay_rzp_wh_8899', $payment->gateway_payment_id);
        $this->assertNotNull($payment->verified_at);

        Event::assertDispatched(PaymentSuccessEvent::class, 1);
    }

    public function test_cashfree_webhook_signature_verification_and_success(): void
    {
        Event::fake([PaymentSuccessEvent::class]);

        $service = app(OnlinePaymentGatewayService::class);
        $order = $service->createOrder($this->agent, 750.0, PaymentPurpose::DIRECT_SUBSCRIPTION);
        $payment = $order['payment'];

        $payload = [
            'type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'event_time' => '2026-08-21T14:00:00+05:30',
            'data' => [
                'order' => [
                    'order_id' => $payment->gateway_order_id,
                    'order_amount' => 750.00,
                    'order_currency' => 'INR',
                ],
                'payment' => [
                    'cf_payment_id' => 99887766,
                    'payment_status' => 'SUCCESS',
                    'payment_amount' => 750.00,
                    'payment_currency' => 'INR',
                    'payment_message' => 'Transaction Successful',
                ],
                'customer_details' => [
                    'customer_id' => "user_{$this->agent->id}",
                ]
            ]
        ];

        $rawJson = json_encode($payload);
        $timestamp = '1787308800000';
        $secretKey = 'test_cf_secret_key_456';
        $signature = base64_encode(hash_hmac('sha256', $timestamp . $rawJson, $secretKey, true));

        // Test signature validator
        $this->assertTrue($service->verifyWebhookSignature($rawJson, $signature, $timestamp));

        // 1st Webhook execution: should process successfully and fire event
        $result = $service->processWebhook($payload);
        $this->assertEquals('success_processed', $result['status']);

        $payment->refresh();
        $this->assertEquals(PaymentStatus::SUCCESS, $payment->status);
        $this->assertEquals('99887766', $payment->gateway_payment_id);
        $this->assertNotNull($payment->verified_at);

        Event::assertDispatched(PaymentSuccessEvent::class, 1);

        // 2nd Webhook execution (Duplicate/Retry): Idempotency check must skip without re-firing
        $repeatResult = $service->processWebhook($payload);
        $this->assertEquals('already_processed', $repeatResult['status']);
        Event::assertDispatched(PaymentSuccessEvent::class, 1); // Still only 1 event fired
    }

    public function test_cashfree_webhook_payment_failed(): void
    {
        Event::fake([PaymentFailedEvent::class]);

        $service = app(OnlinePaymentGatewayService::class);
        $order = $service->createOrder($this->agent, 1000.0, PaymentPurpose::WALLET_TOPUP);
        $payment = $order['payment'];

        $payload = [
            'type' => 'PAYMENT_FAILED_WEBHOOK',
            'data' => [
                'order' => [
                    'order_id' => $payment->gateway_order_id,
                ],
                'payment' => [
                    'cf_payment_id' => 11223344,
                    'payment_status' => 'FAILED',
                    'payment_message' => 'Bank server timed out during OTP entry',
                ]
            ]
        ];

        $result = $service->processWebhook($payload);
        $this->assertEquals('failed_processed', $result['status']);

        $payment->refresh();
        $this->assertEquals(PaymentStatus::FAILED, $payment->status);
        $this->assertEquals('Bank server timed out during OTP entry', $payment->rejection_reason);

        Event::assertDispatched(PaymentFailedEvent::class);
    }

    public function test_mandate_failure_event(): void
    {
        Event::fake([PaymentMandateFailedEvent::class]);

        $mandate = PaymentMandate::create([
            'user_id' => $this->agent->id,
            'gateway_mandate_id' => 'sub_auto_99182',
            'status' => MandateStatus::ACTIVE,
        ]);

        $service = app(OnlinePaymentGatewayService::class);
        $payload = [
            'type' => 'SUBSCRIPTION_CHARGE_FAILED',
            'mandate_id' => 'sub_auto_99182',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'error_description' => 'Insufficient funds in bank account for auto-debit',
                    ]
                ]
            ]
        ];

        $result = $service->processWebhook($payload);
        $this->assertEquals('mandate_failed_processed', $result['status']);

        $mandate->refresh();
        $this->assertEquals(MandateStatus::FAILED, $mandate->status);

        Event::assertDispatched(PaymentMandateFailedEvent::class);
    }

    public function test_manual_upi_submission_flow(): void
    {
        Event::fake([ManualPaymentSubmittedEvent::class]);

        $service = app(ManualUpiPaymentService::class);

        $payment = $service->submitPayment(
            $this->agent,
            1200.0,
            PaymentPurpose::WALLET_TOPUP,
            '423981729012'
        );

        $this->assertEquals(PaymentMode::MANUAL_UPI, $payment->mode);
        $this->assertEquals(PaymentStatus::PENDING_VERIFICATION, $payment->status);
        $this->assertEquals('423981729012', $payment->utr_number);
        $this->assertEquals(1200.0, (float) $payment->amount);

        Event::assertDispatched(ManualPaymentSubmittedEvent::class);
    }

    public function test_bank_transfer_submission_flow(): void
    {
        Event::fake([ManualPaymentSubmittedEvent::class]);

        $service = app(BankTransferPaymentService::class);

        $payment = $service->submitPayment(
            $this->agent,
            5000.0,
            PaymentPurpose::DIRECT_SUBSCRIPTION,
            'NEFT-SBIN2026-9812'
        );

        $this->assertEquals(PaymentMode::BANK_TRANSFER, $payment->mode);
        $this->assertEquals(PaymentStatus::PENDING_VERIFICATION, $payment->status);
        $this->assertEquals('NEFT-SBIN2026-9812', $payment->bank_reference);
        $this->assertEquals(5000.0, (float) $payment->amount);

        Event::assertDispatched(ManualPaymentSubmittedEvent::class);
    }

    public function test_admin_approves_manual_payment(): void
    {
        Event::fake([PaymentSuccessEvent::class, ManualPaymentApprovedEvent::class]);

        $payment = Payment::create([
            'user_id' => $this->agent->id,
            'mode' => PaymentMode::MANUAL_UPI,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => 1500.0,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING_VERIFICATION,
            'utr_number' => '998822331144',
        ]);

        $verifier = app(PaymentVerificationService::class);
        $approved = $verifier->approve($payment, $this->admin, 'Verified on SBI Corporate NetBanking');

        $this->assertEquals(PaymentStatus::SUCCESS, $approved->status);
        $this->assertEquals($this->admin->id, $approved->verified_by);
        $this->assertNotNull($approved->verified_at);

        // Verify Audit Log
        $audit = PaymentAuditLog::where('payment_id', $payment->id)->first();
        $this->assertNotNull($audit);
        $this->assertEquals(PaymentAuditAction::APPROVED, $audit->action);
        $this->assertEquals($this->admin->id, $audit->admin_id);
        $this->assertStringContainsString('SBI Corporate NetBanking', $audit->notes);

        Event::assertDispatched(PaymentSuccessEvent::class);
        Event::assertDispatched(ManualPaymentApprovedEvent::class);
    }

    public function test_admin_rejects_manual_payment_with_mandatory_reason(): void
    {
        Event::fake([ManualPaymentRejectedEvent::class]);

        $payment = Payment::create([
            'user_id' => $this->agent->id,
            'mode' => PaymentMode::MANUAL_UPI,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => 2000.0,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING_VERIFICATION,
            'utr_number' => 'INVALID_UTR_999',
        ]);

        $verifier = app(PaymentVerificationService::class);
        $rejected = $verifier->reject($payment, $this->admin, 'UTR not found in bank statement records.');

        $this->assertEquals(PaymentStatus::REJECTED, $rejected->status);
        $this->assertEquals('UTR not found in bank statement records.', $rejected->rejection_reason);

        // Verify Audit Log
        $audit = PaymentAuditLog::where('payment_id', $payment->id)->first();
        $this->assertNotNull($audit);
        $this->assertEquals(PaymentAuditAction::REJECTED, $audit->action);

        Event::assertDispatched(ManualPaymentRejectedEvent::class);
    }

    public function test_admin_reject_fails_without_reason(): void
    {
        $payment = Payment::create([
            'user_id' => $this->agent->id,
            'mode' => PaymentMode::MANUAL_UPI,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => 500.0,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING_VERIFICATION,
            'utr_number' => '123456789012',
        ]);

        $verifier = app(PaymentVerificationService::class);

        $this->expectException(\InvalidArgumentException::class);
        $verifier->reject($payment, $this->admin, '   '); // Empty reason
    }

    public function test_admin_logs_manual_refund(): void
    {
        $payment = Payment::create([
            'user_id' => $this->agent->id,
            'mode' => PaymentMode::MANUAL_UPI,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => 500.0,
            'currency' => 'INR',
            'status' => PaymentStatus::SUCCESS,
            'utr_number' => 'REFUND_TEST_123',
            'verified_by' => $this->admin->id,
            'verified_at' => now(),
        ]);

        $verifier = app(PaymentVerificationService::class);
        $refunded = $verifier->refund($payment, $this->admin, 'Refunded ₹500 back to agent UPI on request');

        $audit = PaymentAuditLog::where('payment_id', $payment->id)->where('action', PaymentAuditAction::REFUNDED->value)->first();
        $this->assertNotNull($audit);
        $this->assertStringContainsString('Refunded ₹500', $audit->notes);
    }

    public function test_disabled_payment_mode_blocks_submission(): void
    {
        SystemSetting::set('payment_manual_upi_enabled', false);

        $service = app(ManualUpiPaymentService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Manual UPI payment mode is currently disabled.');

        $service->submitPayment(
            $this->agent,
            500.0,
            PaymentPurpose::WALLET_TOPUP,
            '123123123123'
        );
    }

    public function test_minimum_amount_threshold_enforced(): void
    {
        SystemSetting::set('payment_min_amount', 500.0);

        $service = app(BankTransferPaymentService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment amount must be at least ₹500.');

        $service->submitPayment(
            $this->agent,
            200.0, // below 500
            PaymentPurpose::WALLET_TOPUP,
            'BANK-REF-99'
        );
    }

    public function test_user_isolation_on_payments(): void
    {
        Payment::create([
            'user_id' => $this->agent->id,
            'mode' => PaymentMode::MANUAL_UPI,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => 100.0,
            'currency' => 'INR',
            'status' => PaymentStatus::SUCCESS,
            'utr_number' => 'ALPHA_UTR_1',
        ]);

        Payment::create([
            'user_id' => $this->otherAgent->id,
            'mode' => PaymentMode::MANUAL_UPI,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => 200.0,
            'currency' => 'INR',
            'status' => PaymentStatus::SUCCESS,
            'utr_number' => 'BETA_UTR_1',
        ]);

        // When logged in as Agent Alpha
        $this->actingAs($this->agent);
        $alphaPayments = Payment::all();
        $this->assertCount(1, $alphaPayments);
        $this->assertEquals('ALPHA_UTR_1', $alphaPayments->first()->utr_number);

        // When logged in as Admin
        $this->actingAs($this->admin);
        $allPayments = Payment::withoutUserScope()->get();
        $this->assertCount(2, $allPayments);
    }

    public function test_client_can_access_sandbox_page(): void
    {
        $response = $this->actingAs($this->agent)->get(route('payments.sandbox'));
        $response->assertStatus(200);
        $response->assertSeeText('Payment Sandbox & Test Checkout Playground');
        $response->assertSeeText('Razorpay Standard PG');
    }

    public function test_client_can_run_sandbox_success_checkout(): void
    {
        Event::fake([PaymentSuccessEvent::class]);

        $response = $this->actingAs($this->agent)->post(route('payments.sandbox.checkout'), [
            'test_mode' => 'pg_razorpay',
            'purpose' => 'wallet_topup',
            'amount' => 500.0,
            'outcome' => 'success',
        ]);

        $response->assertRedirect(route('payments.sandbox'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->agent->id,
            'amount' => 500.0,
            'status' => PaymentStatus::SUCCESS->value,
        ]);

        Event::assertDispatched(PaymentSuccessEvent::class);
    }

    public function test_client_can_run_sandbox_failed_checkout(): void
    {
        Event::fake([PaymentFailedEvent::class]);

        $response = $this->actingAs($this->agent)->post(route('payments.sandbox.checkout'), [
            'test_mode' => 'pg_cashfree',
            'purpose' => 'direct_subscription',
            'amount' => 1000.0,
            'outcome' => 'failed',
        ]);

        $response->assertRedirect(route('payments.sandbox'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->agent->id,
            'amount' => 1000.0,
            'status' => PaymentStatus::FAILED->value,
        ]);

        Event::assertDispatched(PaymentFailedEvent::class);
    }

    public function test_client_can_run_sandbox_manual_upi_submission(): void
    {
        Event::fake([ManualPaymentSubmittedEvent::class]);

        $response = $this->actingAs($this->agent)->post(route('payments.sandbox.checkout'), [
            'test_mode' => 'manual_upi',
            'purpose' => 'wallet_topup',
            'amount' => 1500.0,
            'outcome' => 'success',
        ]);

        $response->assertRedirect(route('payments.sandbox'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->agent->id,
            'amount' => 1500.0,
            'mode' => PaymentMode::MANUAL_UPI->value,
            'status' => PaymentStatus::PENDING_VERIFICATION->value,
        ]);

        Event::assertDispatched(ManualPaymentSubmittedEvent::class);
    }

    public function test_activate_subscription_on_payment_success_is_idempotent(): void
    {
        $plan = \App\Models\Plan::create([
            'name' => 'Idempotent Plan',
            'included_mrus' => 2,
            'included_consumers' => 2000,
            'base_price' => 500.0,
            'extra_mru_rate' => 20.0,
            'extra_consumer_rate' => 0.2,
            'is_active' => true,
        ]);
        $duration = $plan->durations()->create([
            'duration_unit' => 'month',
            'duration_value' => 1,
            'final_price' => 500.0,
            'is_active' => true,
        ]);

        $payment = Payment::create([
            'user_id' => $this->agent->id,
            'amount' => 500.0,
            'currency' => 'INR',
            'mode' => PaymentMode::PG->value,
            'purpose' => PaymentPurpose::DIRECT_SUBSCRIPTION->value,
            'status' => PaymentStatus::SUCCESS->value,
            'meta' => [
                'plan_id' => $plan->id,
                'duration_id' => $duration->id,
            ],
        ]);

        $listener = app(\App\Listeners\ActivateSubscriptionOnPaymentSuccess::class);

        // First dispatch
        $listener->handle(new PaymentSuccessEvent($payment));
        $this->assertEquals(1, $this->agent->subscriptions()->count());
        $initialSubEnd = $this->agent->activeSubscription->billing_end->timestamp;

        // Second dispatch (duplicate webhook)
        $listener->handle(new PaymentSuccessEvent($payment));

        // Subscriptions count MUST remain 1 and billing_end must not be extended again
        $this->assertEquals(1, $this->agent->subscriptions()->count());
        $this->assertEquals($initialSubEnd, $this->agent->fresh()->activeSubscription->billing_end->timestamp);

        // Audit log count for this payment must remain 1
        $auditLogsCount = PaymentAuditLog::where('payment_id', $payment->id)
            ->where('notes', 'like', '[SUBSCRIPTION_ACTIVATED]%')
            ->count();
        $this->assertEquals(1, $auditLogsCount);
    }
}
