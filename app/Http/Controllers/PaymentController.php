<?php

namespace App\Http\Controllers;

use App\Enums\PaymentAuditAction;
use App\Enums\PaymentMode;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Events\ManualPaymentSubmittedEvent;
use App\Events\PaymentFailedEvent;
use App\Events\PaymentSuccessEvent;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Services\Payment\BankTransferPaymentService;
use App\Services\Payment\ManualUpiPaymentService;
use App\Services\Payment\OnlinePaymentGatewayService;
use App\Services\Payment\PaymentSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentSettingsService $settingsService,
        protected OnlinePaymentGatewayService $onlinePgService,
        protected ManualUpiPaymentService $manualUpiService,
        protected BankTransferPaymentService $bankTransferService
    ) {}

    /**
     * Display billing agent payment history & current wallet/plan status.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $payments = Payment::where('user_id', $user->id)
            ->latest('id')
            ->paginate(15);

        $stats = [
            'total_paid' => (float) Payment::where('user_id', $user->id)
                ->where('status', PaymentStatus::SUCCESS->value)
                ->sum('amount'),
            'pending_verification' => Payment::where('user_id', $user->id)
                ->where('status', PaymentStatus::PENDING_VERIFICATION->value)
                ->count(),
            'recent_success' => Payment::where('user_id', $user->id)
                ->where('status', PaymentStatus::SUCCESS->value)
                ->latest('id')
                ->first(),
        ];

        return view('payments.index', compact(
            'payments',
            'stats'
        ));
    }

    /**
     * Show Checkout / Add Funds form (Dedicated to Wallet Top-Up only).
     */
    public function create(Request $request): View|RedirectResponse
    {
        // If legacy direct_subscription parameter is passed, redirect to the subscription plan page
        if ($request->query('purpose') === 'direct_subscription' || $request->input('purpose') === 'direct_subscription') {
            return redirect()->route('user-panel.subscription')
                ->with('info', 'Direct subscription purchases are now completed via the dedicated Subscription Confirmation page. Please select your plan.');
        }

        $settings = $this->settingsService->getSettings();
        $presetAmount = max((float) $request->query('amount', 500.0), $settings['min_amount']);

        return view('payments.create', compact('settings', 'presetAmount'));
    }

    /**
     * Real-time AJAX validation for coupon codes on payment / topup forms.
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'action_type' => 'nullable|string|in:topup_bonus,subscription_discount',
            'plan_id' => 'nullable|integer',
        ]);

        $actionType = $request->input('action_type', 'topup_bonus');
        $amount = (float) $request->input('amount');
        $code = $request->input('code');
        $planId = $request->input('plan_id') ? (int)$request->input('plan_id') : null;

        $validation = app(\App\Services\Coupon\CouponRedemptionService::class)->validateCode(
            code: $code,
            user: $request->user(),
            actionType: $actionType,
            amount: $amount,
            planId: $planId
        );

        return response()->json($validation, $validation['valid'] ? 200 : 422);
    }

    /**
     * Process checkout submission across PG, Manual UPI, or Bank Transfer.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $settings = $this->settingsService->getSettings();
        $minAmount = $settings['min_amount'];

        $request->validate([
            'mode' => 'required|string|in:' . implode(',', PaymentMode::values()),
            'purpose' => 'required|string|in:' . implode(',', PaymentPurpose::values()),
            'amount' => "required|numeric|min:{$minAmount}",
            'utr_number' => 'required_if:mode,manual_upi|nullable|string|max:100',
            'bank_reference' => 'required_if:mode,bank_transfer|nullable|string|max:100',
            'screenshot' => 'nullable|image|max:5120', // Max 5MB image
        ]);

        $user = $request->user();
        $mode = PaymentMode::from($request->input('mode'));
        $purpose = PaymentPurpose::from($request->input('purpose'));
        $amount = (float) $request->input('amount');

        $meta = [];
        $couponCode = trim($request->input('coupon_code', ''));
        if (!empty($couponCode)) {
            $couponValidation = app(\App\Services\Coupon\CouponRedemptionService::class)->validateCode(
                code: $couponCode,
                user: $user,
                actionType: 'topup_bonus',
                amount: $amount
            );

            if (!$couponValidation['valid']) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'error' => $couponValidation['message']], 422);
                }
                return redirect()->back()->withInput()->with('error', $couponValidation['message']);
            }

            $meta['coupon_code'] = $couponValidation['code'];
            $meta['bonus_percent'] = $couponValidation['bonus_percent'];
            $meta['bonus_amount'] = $couponValidation['discount_or_bonus_amount'];
        }

        try {
            switch ($mode) {
                case PaymentMode::PG:
                    $orderData = $this->onlinePgService->createOrder($user, $amount, $purpose, null, $meta);
                    if ($request->wantsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => true,
                            'order' => $orderData['checkout_config'],
                            'payment_id' => $orderData['payment']->id,
                        ]);
                    }
                    return redirect()->route('payments.index')->with('info', "Online PG order #{$orderData['payment']->gateway_order_id} generated. Complete checkout to finalize.");

                case PaymentMode::MANUAL_UPI:
                    $payment = $this->manualUpiService->submitPayment(
                        $user,
                        $amount,
                        $purpose,
                        $request->input('utr_number'),
                        $request->file('screenshot'),
                        $meta
                    );
                    return redirect()->route('payments.index')->with('success', "Payment submitted with UTR: {$payment->utr_number}. Status is Pending Admin Verification.");

                case PaymentMode::BANK_TRANSFER:
                    $payment = $this->bankTransferService->submitPayment(
                        $user,
                        $amount,
                        $purpose,
                        $request->input('bank_reference'),
                        $request->file('screenshot'),
                        $meta
                    );
                    return redirect()->route('payments.index')->with('success', "Bank transfer submitted with Ref: {$payment->bank_reference}. Status is Pending Admin Verification.");
            }
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('payments.index');
    }

    /**
     * Verify payment status upon return from Cashfree or Razorpay hosted checkout.
     */
    public function verify(Request $request): RedirectResponse
    {
        $orderId = $request->query('razorpay_order_id') ?? $request->query('order_id');
        $paymentId = $request->query('razorpay_payment_id') ?? $request->query('payment_id');
        $signature = $request->query('razorpay_signature') ?? $request->query('signature');

        if (empty($orderId)) {
            return redirect()->route('payments.index')->with('error', 'No order ID provided for payment verification.');
        }

        $payment = Payment::where('gateway_order_id', $orderId)->first();

        // 1. Handle Razorpay client callback verification with signature
        if ($payment && !empty($paymentId) && !empty($signature)) {
            $isValid = $this->onlinePgService->verifyRazorpayPaymentSignature($orderId, $paymentId, $signature);
            if ($isValid) {
                if ($payment->status !== PaymentStatus::SUCCESS) {
                    $payment->update([
                        'status' => PaymentStatus::SUCCESS,
                        'gateway_payment_id' => $paymentId,
                        'verified_at' => now(),
                    ]);
                    event(new PaymentSuccessEvent($payment, $paymentId));
                }
                return redirect()->route('payments.index')->with('success', "Payment #{$payment->id} for ₹" . number_format((float)$payment->amount, 2) . " completed and verified successfully via Razorpay!");
            }
        }

        // 2. Handle Cashfree direct order verification
        $payment = $this->onlinePgService->verifyOrderWithCashfree($orderId);

        if (!$payment) {
            return redirect()->route('payments.index')->with('error', "Payment order '{$orderId}' not found.");
        }

        if ($payment->status === PaymentStatus::SUCCESS) {
            return redirect()->route('payments.index')->with('success', "Payment #{$payment->id} for ₹" . number_format((float)$payment->amount, 2) . " completed and verified successfully!");
        }

        if ($payment->status === PaymentStatus::FAILED) {
            return redirect()->route('payments.index')->with('error', "Payment #{$payment->id} failed: " . ($payment->rejection_reason ?: 'Transaction was declined or cancelled.'));
        }

        return redirect()->route('payments.index')->with('info', "Payment #{$payment->id} is pending gateway confirmation. It will update automatically once verified.");
    }
}
