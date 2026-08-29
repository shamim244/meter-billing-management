<?php

namespace App\Http\Controllers;

use App\Enums\DebitResult;
use App\Enums\PaymentAuditAction;
use App\Enums\PaymentMode;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Events\ManualPaymentSubmittedEvent;
use App\Models\AgentSubscription;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Services\Billing\PlanChangeService;
use App\Services\Coupon\CouponRedemptionService;
use App\Services\Payment\BankTransferPaymentService;
use App\Services\Payment\ManualUpiPaymentService;
use App\Services\Payment\OnlinePaymentGatewayService;
use App\Services\Payment\PaymentSettingsService;
use App\Services\Plan\PlanService;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SubscriptionCheckoutController extends Controller
{
    public function __construct(
        protected PlanService $planService,
        protected PlanChangeService $planChangeService,
        protected WalletService $walletService,
        protected PaymentSettingsService $settingsService,
        protected OnlinePaymentGatewayService $onlinePgService,
        protected ManualUpiPaymentService $manualUpiService,
        protected BankTransferPaymentService $bankTransferService,
        protected CouponRedemptionService $couponRedemptionService
    ) {}

    /**
     * Get dynamic pre-payment quote and proration breakdown.
     */
    public function quote(Request $request, Plan $plan, PlanDuration $duration): JsonResponse
    {
        if ($duration->plan_id !== $plan->id || !$plan->is_active) {
            return response()->json(['success' => false, 'message' => 'Selected plan or duration is not currently available.'], 404);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $walletBalance = (float) $this->walletService->getBalance($user);

        $activeSubscription = $user->activeSubscription;

        $actionMode = $request->input('action_mode', 'auto');
        $pricingDetails = $this->calculatePricingDetails($user, $plan, $duration, $activeSubscription, $actionMode);

        $downgradeEligibility = null;
        if (($pricingDetails['action_type'] === 'downgrade' || ($pricingDetails['shift_option']['action_type'] ?? null) === 'downgrade') && $activeSubscription) {
            $downgradeEligibility = $this->planChangeService->checkDowngradeEligibility($activeSubscription, $plan);
        }

        // Check optional coupon code
        $couponCode = trim($request->input('coupon_code', ''));
        $couponData = null;
        $finalPayable = (float) $pricingDetails['final_amount'];

        if (!empty($couponCode)) {
            $couponValidation = $this->couponRedemptionService->validateCode(
                code: $couponCode,
                user: $user,
                actionType: 'subscription_discount',
                amount: $finalPayable,
                planId: $plan->id
            );

            if ($couponValidation['valid']) {
                $couponDiscount = (float) $couponValidation['discount_or_bonus_amount'];
                $finalPayable = (float) $couponValidation['final_amount'];
                $couponData = [
                    'valid' => true,
                    'code' => $couponValidation['code'],
                    'discount_amount' => $couponDiscount,
                    'discount_kind' => $couponValidation['discount_kind'],
                    'discount_value' => $couponValidation['discount_value'],
                    'message' => $couponValidation['message'],
                ];
            } else {
                $couponData = [
                    'valid' => false,
                    'code' => $couponCode,
                    'message' => $couponValidation['message'],
                ];
            }
        }

        return response()->json([
            'success' => true,
            'action_type' => $pricingDetails['action_type'],
            'action_mode' => $pricingDetails['action_mode'],
            'available_actions' => $pricingDetails['available_actions'],
            'shift_option' => $pricingDetails['shift_option'] ?? null,
            'extend_option' => $pricingDetails['extend_option'] ?? null,
            'start_date' => $pricingDetails['start_date'] ?? null,
            'end_date' => $pricingDetails['end_date'] ?? null,
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'description' => $plan->description,
                'included_mrus' => (int) $plan->included_mrus,
                'included_consumers' => (int) $plan->included_consumers,
                'extra_mru_rate' => (float) ($duration->extra_mru_rate ?? $plan->extra_mru_rate),
                'extra_consumer_rate' => (float) ($duration->extra_consumer_rate ?? $plan->extra_consumer_rate),
            ],
            'duration' => [
                'id' => $duration->id,
                'formatted_duration' => $duration->formatted_duration,
                'duration_months' => $duration->duration_months,
                'duration_unit' => $duration->duration_unit,
                'duration_value' => $duration->duration_value,
                'discount_percent' => (float) $duration->discount_percent,
                'final_price' => (float) $duration->final_price,
            ],
            'current_subscription' => $activeSubscription ? [
                'plan_name' => $activeSubscription->plan?->name ?? 'Active Plan',
                'included_mrus' => (int) $activeSubscription->included_mrus_locked,
                'included_consumers' => (int) $activeSubscription->included_consumers_locked,
                'billing_end' => $activeSubscription->billing_end ? $activeSubscription->billing_end->format('M d, Y') : null,
                'base_price_paid' => (float) $activeSubscription->base_price_paid,
            ] : null,
            'proration' => $pricingDetails['proration'],
            'final_amount' => $finalPayable,
            'original_final_amount' => (float) $pricingDetails['final_amount'],
            'coupon' => $couponData,
            'prorated_credit' => (float) ($pricingDetails['prorated_credit'] ?? 0.0),
            'wallet_balance' => $walletBalance,
            'can_pay_from_wallet' => $finalPayable <= 0 || $walletBalance >= $finalPayable,
            'downgrade_eligibility' => $downgradeEligibility ? [
                'eligible' => $downgradeEligibility['eligible'],
                'active_mrus_count' => $downgradeEligibility['active_mrus_count'],
                'new_plan_quota' => $downgradeEligibility['new_plan_quota'],
                'excess_mrus' => $downgradeEligibility['excess_mrus'] ?? 0,
                'active_mrus' => ($downgradeEligibility['active_mrus'] ?? collect())->map(fn($m) => [
                    'id' => $m->id,
                    'code' => $m->code,
                    'name' => $m->name,
                    'full_identifier' => $m->full_identifier,
                    'consumers_count' => $m->consumerAccounts()->count(),
                ]),
            ] : null,
        ]);
    }

    /**
     * Display Subscription Purchase Confirmation page (Fixed pricing derived server-side).
     */
    public function show(Request $request, Plan $plan, PlanDuration $duration): View|RedirectResponse
    {
        if ($duration->plan_id !== $plan->id || !$plan->is_active) {
            return redirect()->route('user-panel.subscription')
                ->with('error', 'Selected plan or duration is not currently available.');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $settings = $this->settingsService->getSettings();
        $walletBalance = (float) $this->walletService->getBalance($user);

        $activeSubscription = $user->activeSubscription;

        $actionMode = $request->input('action_mode', 'auto');
        $pricingDetails = $this->calculatePricingDetails($user, $plan, $duration, $activeSubscription, $actionMode);

        $downgradeEligibility = null;
        if ($pricingDetails['action_type'] === 'downgrade' && $activeSubscription) {
            $downgradeEligibility = $this->planChangeService->checkDowngradeEligibility($activeSubscription, $plan);
        }

        return view('subscription.purchase', compact(
            'plan',
            'duration',
            'user',
            'activeSubscription',
            'pricingDetails',
            'settings',
            'walletBalance',
            'downgradeEligibility',
            'actionMode'
        ));
    }

    /**
     * Process direct payment for subscription purchase (PG / Manual UPI / Bank Transfer).
     */
    public function process(Request $request, Plan $plan, PlanDuration $duration): RedirectResponse|JsonResponse
    {
        if ($duration->plan_id !== $plan->id || !$plan->is_active) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => 'Selected plan or duration is not available.'], 422);
            }
            return redirect()->route('user-panel.subscription')->with('error', 'Invalid plan or duration.');
        }

        $request->validate([
            'mode' => 'required|string|in:' . implode(',', PaymentMode::values()),
            'action_mode' => 'nullable|string|in:auto,shift,extend,new',
            'utr_number' => 'required_if:mode,manual_upi|nullable|string|max:100',
            'bank_reference' => 'required_if:mode,bank_transfer|nullable|string|max:100',
            'screenshot' => 'nullable|image|max:5120',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $mode = PaymentMode::from($request->input('mode'));

        $activeSubscription = $user->activeSubscription;

        $actionMode = $request->input('action_mode', 'auto');
        $pricingDetails = $this->calculatePricingDetails($user, $plan, $duration, $activeSubscription, $actionMode);
        $amount = (float) $pricingDetails['final_amount'];

        // If upgrade/purchase requires 0 payment (e.g. covered by credit or free tier)
        if ($amount <= 0) {
            if ($pricingDetails['action_type'] === 'downgrade' && $activeSubscription) {
                $eligibility = $this->planChangeService->checkDowngradeEligibility($activeSubscription, $plan);
                if (!$eligibility['eligible']) {
                    return redirect()->route('subscription.purchase', ['plan' => $plan->id, 'duration' => $duration->id, 'action_mode' => $actionMode])
                        ->with('error', $eligibility['message']);
                }
                $downgradeResult = $this->planChangeService->downgradePlan($activeSubscription, $plan, $duration);
                if ($downgradeResult['success']) {
                    return redirect()->route('user-panel.subscription')
                        ->with('success', "Plan successfully changed to {$plan->name}. Proration credit of ₹" . number_format($downgradeResult['amount_credited'], 2) . " was added to your wallet.");
                }
                return redirect()->route('user-panel.subscription')->with('error', $downgradeResult['message'] ?? 'Plan change failed.');
            }

            $subscription = $this->planService->subscribeAgent($user, $plan, $duration);
            return redirect()->route('user-panel.subscription')
                ->with('success', "🎉 Subscribed to {$plan->name} ({$duration->formatted_duration}) successfully!");
        }

        $meta = [
            'plan_id' => $plan->id,
            'duration_id' => $duration->id,
            'action_type' => $pricingDetails['action_type'],
            'action_mode' => $pricingDetails['action_mode'],
        ];

        // Check optional coupon code
        $couponCode = trim($request->input('coupon_code', ''));
        if (!empty($couponCode)) {
            $couponValidation = $this->couponRedemptionService->validateCode(
                code: $couponCode,
                user: $user,
                actionType: 'subscription_discount',
                amount: $amount,
                planId: $plan->id
            );

            if (!$couponValidation['valid']) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'error' => $couponValidation['message']], 422);
                }
                return back()->withInput()->with('error', $couponValidation['message']);
            }

            $meta['coupon_code'] = $couponValidation['code'];
            $meta['original_amount'] = $amount;
            $meta['coupon_discount'] = $couponValidation['discount_or_bonus_amount'];
            $amount = (float) $couponValidation['final_amount'];
        }

        try {
            switch ($mode) {
                case PaymentMode::PG:
                    $orderData = $this->onlinePgService->createOrder(
                        $user,
                        $amount,
                        PaymentPurpose::DIRECT_SUBSCRIPTION,
                        null,
                        $meta
                    );

                    if ($request->wantsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => true,
                            'order' => $orderData['checkout_config'],
                            'payment_id' => $orderData['payment']->id,
                        ]);
                    }
                    return redirect()->route('payments.index')->with('info', "Subscription PG order generated. Complete checkout to finalize.");

                case PaymentMode::MANUAL_UPI:
                    $payment = $this->manualUpiService->submitPayment(
                        $user,
                        $amount,
                        PaymentPurpose::DIRECT_SUBSCRIPTION,
                        $request->input('utr_number'),
                        $request->file('screenshot'),
                        $meta
                    );
                    return redirect()->route('payments.index')
                        ->with('success', "Direct subscription payment of ₹" . number_format($amount, 2) . " submitted with UTR: {$payment->utr_number}. Your plan will activate upon admin approval.");

                case PaymentMode::BANK_TRANSFER:
                    $payment = $this->bankTransferService->submitPayment(
                        $user,
                        $amount,
                        PaymentPurpose::DIRECT_SUBSCRIPTION,
                        $request->input('bank_reference'),
                        $request->file('screenshot'),
                        $meta
                    );
                    return redirect()->route('payments.index')
                        ->with('success', "Direct subscription payment of ₹" . number_format($amount, 2) . " submitted with Ref: {$payment->bank_reference}. Your plan will activate upon admin approval.");
            }
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('user-panel.subscription');
    }

    /**
     * In-place wallet subscription activation (No external redirect needed).
     */
    public function subscribeWallet(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
            'duration_id' => 'required|integer|exists:plan_durations,id',
            'action_mode' => 'nullable|string|in:auto,shift,extend,new',
            'coupon_code' => 'nullable|string|max:50',
        ]);

        $plan = Plan::findOrFail($request->input('plan_id'));
        $duration = PlanDuration::where('id', $request->input('duration_id'))
            ->where('plan_id', $plan->id)
            ->firstOrFail();

        if (!$plan->is_active) {
            $msg = 'Selected plan is not currently active.';
            return $request->wantsJson() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $activeSubscription = $user->activeSubscription;

        $actionMode = $request->input('action_mode', 'auto');
        $pricingDetails = $this->calculatePricingDetails($user, $plan, $duration, $activeSubscription, $actionMode);
        $amountDue = (float) $pricingDetails['final_amount'];
        $originalAmountDue = $amountDue;
        $walletBalance = (float) $this->walletService->getBalance($user);

        // Check optional coupon code
        $couponCode = trim($request->input('coupon_code', ''));
        $couponToRedeem = null;
        if (!empty($couponCode)) {
            $couponValidation = $this->couponRedemptionService->validateCode(
                code: $couponCode,
                user: $user,
                actionType: 'subscription_discount',
                amount: $amountDue,
                planId: $plan->id
            );

            if (!$couponValidation['valid']) {
                return $request->wantsJson()
                    ? response()->json(['success' => false, 'message' => $couponValidation['message']], 422)
                    : back()->with('error', $couponValidation['message']);
            }

            $couponToRedeem = $couponValidation['coupon'];
            $amountDue = (float) $couponValidation['final_amount'];
        }

        if ($amountDue > 0 && $walletBalance < $amountDue) {
            $msg = "Insufficient wallet balance. You need ₹" . number_format($amountDue, 2) . " but your wallet balance is ₹" . number_format($walletBalance, 2) . ".";
            return $request->wantsJson() ? response()->json(['success' => false, 'message' => $msg, 'requires_topup' => true], 422) : back()->with('error', $msg);
        }

        // Case 1: Shift Mode - Upgrade (Prorated difference debited, new cycle starts today)
        if ($pricingDetails['action_mode'] === 'shift' && $pricingDetails['is_upgrade'] && $activeSubscription) {
            $res = $this->planChangeService->upgradePlan($activeSubscription, $plan, $duration);
            if (!$res['success']) {
                $msg = $res['message'] ?? 'Upgrade failed.';
                return $request->wantsJson() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
            }

            if ($couponToRedeem) {
                $this->couponRedemptionService->redeemForSubscription(
                    coupon: $couponToRedeem,
                    user: $user,
                    originalAmount: $originalAmountDue,
                    referenceId: 'sub_' . $res['subscription']->id
                );
            }

            $msg = "🎉 Switched to {$plan->name} ({$duration->formatted_duration}) successfully! Prorated fee of ₹" . number_format($amountDue, 2) . " was debited from your wallet. Valid from today until " . $res['subscription']->billing_end->format('M d, Y') . ".";
            return $request->wantsJson() ? response()->json(['success' => true, 'message' => $msg, 'subscription_id' => $res['subscription']->id]) : back()->with('success', $msg);
        }

        // Case 2: Shift Mode - Downgrade (Prorated credit added to wallet, new cycle starts today)
        if ($pricingDetails['action_mode'] === 'shift' && $pricingDetails['is_downgrade'] && $activeSubscription) {
            $eligibility = $this->planChangeService->checkDowngradeEligibility($activeSubscription, $plan);
            if (!$eligibility['eligible']) {
                $msg = $eligibility['message'] ?? 'Downgrade ineligible due to active MRU count.';
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $msg,
                        'ineligible_mrus' => true,
                        'active_mrus' => $eligibility['active_mrus']->map(fn($m) => [
                            'id' => $m->id,
                            'code' => $m->code,
                            'name' => $m->name,
                            'full_identifier' => $m->full_identifier,
                            'consumers_count' => $m->consumerAccounts()->count(),
                        ]),
                        'excess_mrus' => $eligibility['excess_mrus'],
                        'new_plan_quota' => $eligibility['new_plan_quota'],
                        'active_mrus_count' => $eligibility['active_mrus_count'],
                    ], 422);
                }
                return back()->with('error', $msg);
            }

            $res = $this->planChangeService->downgradePlan($activeSubscription, $plan, $duration);
            if (!$res['success']) {
                $msg = $res['message'] ?? 'Downgrade failed.';
                return $request->wantsJson() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
            }
            $msg = "🎉 Switched to {$plan->name} ({$duration->formatted_duration}) successfully! Prorated credit of ₹" . number_format($res['amount_credited'], 2) . " was credited to your wallet. Valid from today until " . $res['subscription']->billing_end->format('M d, Y') . ".";
            return $request->wantsJson() ? response()->json(['success' => true, 'message' => $msg, 'subscription_id' => $res['subscription']->id]) : back()->with('success', $msg);
        }

        // Case 3: Extend Mode OR Brand New Subscription
        $isExtend = ($pricingDetails['action_mode'] === 'extend') && $activeSubscription;
        $txDesc = $isExtend
            ? "Plan Extension: +{$duration->formatted_duration} added to {$plan->name}"
            : "Subscription to {$plan->name} ({$duration->formatted_duration})";

        return DB::transaction(function () use ($user, $plan, $duration, $amountDue, $originalAmountDue, $couponToRedeem, $isExtend, $txDesc, $request) {
            if ($amountDue > 0) {
                $debitResult = $this->walletService->debit(
                    user: $user,
                    amount: $amountDue,
                    source: $isExtend ? 'subscription_renewal' : 'subscription_purchase',
                    referenceType: Plan::class,
                    referenceId: (string) $plan->id,
                    description: $txDesc
                );

                if ($debitResult !== DebitResult::SUCCESS) {
                    $msg = $debitResult === DebitResult::WALLET_FROZEN ? 'Wallet is frozen. Please contact admin.' : 'Insufficient wallet balance.';
                    return $request->wantsJson() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
                }
            }

            $subscription = $this->planService->subscribeAgent($user, $plan, $duration);

            if ($couponToRedeem) {
                $this->couponRedemptionService->redeemForSubscription(
                    coupon: $couponToRedeem,
                    user: $user,
                    originalAmount: $originalAmountDue,
                    referenceId: 'sub_' . $subscription->id
                );
            }

            $msg = $isExtend
                ? "🎉 Extended {$plan->name} (+{$duration->formatted_duration}) successfully! New validity until " . $subscription->billing_end->format('M d, Y') . "."
                : "🎉 Subscribed to {$plan->name} ({$duration->formatted_duration}) successfully! Valid until " . $subscription->billing_end->format('M d, Y') . ".";

            return $request->wantsJson()
                ? response()->json(['success' => true, 'message' => $msg, 'subscription_id' => $subscription->id])
                : back()->with('success', $msg);
        });
    }

    /**
     * Compute pricing details and proration server-side.
     * Supports both 'shift' (starts today with unused balance adjustment)
     * and 'extend' (adds validity onto existing end date).
     */
    protected function calculatePricingDetails($user, Plan $plan, PlanDuration $duration, ?AgentSubscription $activeSub, string $actionMode = 'auto'): array
    {
        $basePrice = (float) $duration->final_price;

        if (!$activeSub || !$activeSub->plan) {
            $now = now();
            $newEnd = $duration->calculateBillingEnd($now);
            return [
                'action_type' => 'new',
                'action_mode' => 'new',
                'base_price' => $basePrice,
                'proration' => null,
                'final_amount' => $basePrice,
                'prorated_credit' => 0.0,
                'discount_percent' => (float) $duration->discount_percent,
                'is_upgrade' => false,
                'is_downgrade' => false,
                'start_date' => $now->format('M d, Y'),
                'end_date' => $newEnd->format('M d, Y'),
                'available_actions' => ['new'],
            ];
        }

        // Calculate Prorated Shift details (Starts Today, Replaces Old Plan with Day-Based Credit)
        $proration = $this->planChangeService->calculateProration($activeSub, $plan, $duration);
        $amountDue = (float) $proration['amount_due'];
        $isUpgrade = $amountDue > 0;
        $proratedCredit = $amountDue < 0 ? abs($amountDue) : 0.0;
        $shiftNow = now();
        $shiftEnd = $duration->calculateBillingEnd($shiftNow);

        $shiftDetails = [
            'action_type' => $isUpgrade ? 'upgrade' : ($amountDue < 0 ? 'downgrade' : 'shift'),
            'action_mode' => 'shift',
            'base_price' => $basePrice,
            'proration' => $proration,
            'final_amount' => $isUpgrade ? $amountDue : 0.0,
            'prorated_credit' => $proratedCredit,
            'discount_percent' => (float) $duration->discount_percent,
            'is_upgrade' => $isUpgrade,
            'is_downgrade' => $amountDue < 0,
            'start_date' => $shiftNow->format('M d, Y'),
            'end_date' => $shiftEnd->format('M d, Y'),
        ];

        // Calculate Extend details (Adds Duration to End of Existing Validity)
        $extendBaseDate = ($activeSub->billing_end && $activeSub->billing_end > now()) ? $activeSub->billing_end->copy() : now();
        $extendEnd = $duration->calculateBillingEnd($extendBaseDate);

        $extendDetails = [
            'action_type' => 'extend',
            'action_mode' => 'extend',
            'base_price' => $basePrice,
            'proration' => null,
            'final_amount' => $basePrice,
            'prorated_credit' => 0.0,
            'discount_percent' => (float) $duration->discount_percent,
            'is_upgrade' => false,
            'is_downgrade' => false,
            'start_date' => $extendBaseDate->format('M d, Y'),
            'end_date' => $extendEnd->format('M d, Y'),
        ];

        $availableActions = ['shift', 'extend'];

        // Determine which mode to return as primary
        $effectiveMode = $actionMode;
        if ($effectiveMode === 'auto') {
            if ($activeSub->plan_id !== $plan->id) {
                $effectiveMode = 'shift';
            } else {
                // If same plan and same duration, default to 'extend'. If different duration (e.g. 1m -> 3m or 3m -> 7d), default to 'shift'
                $currentDurationValue = $activeSub->duration_value ?: $activeSub->duration_months;
                $targetDurationValue = $duration->duration_value ?: $duration->duration_months;
                $currentDurationUnit = $activeSub->duration_unit ?: 'month';
                $targetDurationUnit = $duration->duration_unit ?: 'month';

                $isSameExactDuration = ($currentDurationValue == $targetDurationValue && $currentDurationUnit == $targetDurationUnit);
                $effectiveMode = $isSameExactDuration ? 'extend' : 'shift';
            }
        }

        $chosen = ($effectiveMode === 'extend') ? $extendDetails : $shiftDetails;

        $chosen['available_actions'] = $availableActions;
        $chosen['shift_option'] = $shiftDetails;
        $chosen['extend_option'] = $extendDetails;
        return $chosen;
    }
}
