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
        protected BankTransferPaymentService $bankTransferService
    ) {}

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

        $activeSubscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('billing_end', '>', now())
            ->latest('id')
            ->first();

        $pricingDetails = $this->calculatePricingDetails($user, $plan, $duration, $activeSubscription);

        return view('subscription.purchase', compact(
            'plan',
            'duration',
            'user',
            'activeSubscription',
            'pricingDetails',
            'settings',
            'walletBalance'
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
            'utr_number' => 'required_if:mode,manual_upi|nullable|string|max:100',
            'bank_reference' => 'required_if:mode,bank_transfer|nullable|string|max:100',
            'screenshot' => 'nullable|image|max:5120',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $mode = PaymentMode::from($request->input('mode'));

        $activeSubscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('billing_end', '>', now())
            ->latest('id')
            ->first();

        $pricingDetails = $this->calculatePricingDetails($user, $plan, $duration, $activeSubscription);
        $amount = (float) $pricingDetails['final_amount'];

        // If upgrade/purchase requires 0 payment (e.g. covered by credit or free tier)
        if ($amount <= 0) {
            if ($pricingDetails['action_type'] === 'downgrade' && $activeSubscription) {
                $downgradeResult = $this->planChangeService->downgradePlan($activeSubscription, $plan, $duration);
                if ($downgradeResult['success']) {
                    return redirect()->route('user-panel.subscription')
                        ->with('success', "Plan successfully changed to {$plan->name}. Proration credit of ₹" . number_format($downgradeResult['amount_credited'], 2) . " was added to your wallet.");
                }
                return redirect()->route('user-panel.subscription')->with('error', $downgradeResult['message'] ?? 'Plan change failed.');
            }

            $subscription = $this->planService->subscribeAgent($user, $plan, $duration);
            return redirect()->route('user-panel.subscription')
                ->with('success', "🎉 Subscribed to {$plan->name} ({$duration->duration_months} Month" . ($duration->duration_months > 1 ? 's' : '') . ") successfully!");
        }

        $meta = [
            'plan_id' => $plan->id,
            'duration_id' => $duration->id,
            'action_type' => $pricingDetails['action_type'],
        ];

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
            'plan_id' => 'required|exists:plans,id',
            'duration_id' => 'required|exists:plan_durations,id',
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

        $activeSubscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('billing_end', '>', now())
            ->latest('id')
            ->first();

        $pricingDetails = $this->calculatePricingDetails($user, $plan, $duration, $activeSubscription);
        $amountDue = (float) $pricingDetails['final_amount'];
        $walletBalance = (float) $this->walletService->getBalance($user);

        if ($amountDue > 0 && $walletBalance < $amountDue) {
            $msg = "Insufficient wallet balance. You need ₹" . number_format($amountDue, 2) . " but your wallet balance is ₹" . number_format($walletBalance, 2) . ".";
            return $request->wantsJson() ? response()->json(['success' => false, 'message' => $msg, 'requires_topup' => true], 422) : back()->with('error', $msg);
        }

        // Case 1: Mid-cycle upgrade
        if ($pricingDetails['action_type'] === 'upgrade' && $activeSubscription) {
            $res = $this->planChangeService->upgradePlan($activeSubscription, $plan, $duration);
            if (!$res['success']) {
                $msg = $res['message'] ?? 'Upgrade failed.';
                return $request->wantsJson() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
            }
            $msg = "🎉 Upgraded to {$plan->name} successfully! Prorated fee of ₹" . number_format($amountDue, 2) . " was debited from your wallet.";
            return $request->wantsJson() ? response()->json(['success' => true, 'message' => $msg]) : back()->with('success', $msg);
        }

        // Case 2: Mid-cycle downgrade
        if ($pricingDetails['action_type'] === 'downgrade' && $activeSubscription) {
            $eligibility = $this->planChangeService->checkDowngradeEligibility($activeSubscription, $plan);
            if (!$eligibility['eligible']) {
                $msg = $eligibility['message'] ?? 'Downgrade ineligible due to active MRU count.';
                return $request->wantsJson() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
            }

            $res = $this->planChangeService->downgradePlan($activeSubscription, $plan, $duration);
            if (!$res['success']) {
                $msg = $res['message'] ?? 'Downgrade failed.';
                return $request->wantsJson() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
            }
            $msg = "🎉 Downgraded to {$plan->name} successfully! Prorated credit of ₹" . number_format($res['amount_credited'], 2) . " was credited to your wallet.";
            return $request->wantsJson() ? response()->json(['success' => true, 'message' => $msg]) : back()->with('success', $msg);
        }

        // Case 3: Initial Subscription or Same Plan Renewal
        if ($amountDue > 0) {
            $debitResult = $this->walletService->debit(
                user: $user,
                amount: $amountDue,
                source: 'subscription_purchase',
                referenceType: Plan::class,
                referenceId: (string) $plan->id,
                description: "Subscription to {$plan->name} ({$duration->duration_months} Month" . ($duration->duration_months > 1 ? 's' : '') . ")"
            );

            if ($debitResult !== DebitResult::SUCCESS) {
                $msg = $debitResult === DebitResult::WALLET_FROZEN ? 'Wallet is frozen. Please contact admin.' : 'Insufficient wallet balance.';
                return $request->wantsJson() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
            }
        }

        $subscription = $this->planService->subscribeAgent($user, $plan, $duration);
        $msg = "🎉 Subscribed to {$plan->name} ({$duration->duration_months} Month" . ($duration->duration_months > 1 ? 's' : '') . ") successfully!" . ($amountDue > 0 ? " ₹" . number_format($amountDue, 2) . " was debited from your wallet." : "");

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'subscription_id' => $subscription->id])
            : back()->with('success', $msg);
    }

    /**
     * Compute pricing details and proration server-side.
     */
    protected function calculatePricingDetails($user, Plan $plan, PlanDuration $duration, ?AgentSubscription $activeSub): array
    {
        $basePrice = (float) $duration->final_price;

        if (!$activeSub || !$activeSub->plan) {
            return [
                'action_type' => 'new',
                'base_price' => $basePrice,
                'proration' => null,
                'final_amount' => $basePrice,
                'discount_percent' => (float) $duration->discount_percent,
                'is_upgrade' => false,
                'is_downgrade' => false,
            ];
        }

        if ($activeSub->plan_id === $plan->id) {
            return [
                'action_type' => 'renewal',
                'base_price' => $basePrice,
                'proration' => null,
                'final_amount' => $basePrice,
                'discount_percent' => (float) $duration->discount_percent,
                'is_upgrade' => false,
                'is_downgrade' => false,
            ];
        }

        $isUpgrade = $plan->base_price >= $activeSub->plan->base_price;
        $proration = $this->planChangeService->calculateProration($activeSub, $plan, $duration);
        $proratedCredit = max(0.0, round(($proration['old_plan_credit'] ?? 0) - ($proration['new_plan_cost'] ?? 0), 2));

        return [
            'action_type' => $isUpgrade ? 'upgrade' : 'downgrade',
            'base_price' => $basePrice,
            'proration' => $proration,
            'final_amount' => $isUpgrade ? (float) $proration['amount_due'] : 0.0,
            'prorated_credit' => !$isUpgrade ? $proratedCredit : 0.0,
            'discount_percent' => (float) $duration->discount_percent,
            'is_upgrade' => $isUpgrade,
            'is_downgrade' => !$isUpgrade,
        ];
    }
}
