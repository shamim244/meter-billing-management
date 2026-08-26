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
        protected BankTransferPaymentService $bankTransferService
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

        $pricingDetails = $this->calculatePricingDetails($user, $plan, $duration, $activeSubscription);

        $downgradeEligibility = null;
        if ($pricingDetails['action_type'] === 'downgrade' && $activeSubscription) {
            $downgradeEligibility = $this->planChangeService->checkDowngradeEligibility($activeSubscription, $plan);
        }

        return response()->json([
            'success' => true,
            'action_type' => $pricingDetails['action_type'],
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
            'final_amount' => (float) $pricingDetails['final_amount'],
            'prorated_credit' => (float) ($pricingDetails['prorated_credit'] ?? 0.0),
            'wallet_balance' => $walletBalance,
            'can_pay_from_wallet' => $pricingDetails['final_amount'] <= 0 || $walletBalance >= $pricingDetails['final_amount'],
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

        $pricingDetails = $this->calculatePricingDetails($user, $plan, $duration, $activeSubscription);

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
            'downgradeEligibility'
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

        $activeSubscription = $user->activeSubscription;

        $pricingDetails = $this->calculatePricingDetails($user, $plan, $duration, $activeSubscription);
        $amount = (float) $pricingDetails['final_amount'];

        // If upgrade/purchase requires 0 payment (e.g. covered by credit or free tier)
        if ($amount <= 0) {
            if ($pricingDetails['action_type'] === 'downgrade' && $activeSubscription) {
                $eligibility = $this->planChangeService->checkDowngradeEligibility($activeSubscription, $plan);
                if (!$eligibility['eligible']) {
                    return redirect()->route('subscription.purchase', ['plan' => $plan->id, 'duration' => $duration->id])
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

        $activeSubscription = $user->activeSubscription;

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

        if ($pricingDetails['action_type'] === 'downgrade' && $activeSubscription) {
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
            $msg = "🎉 Downgraded to {$plan->name} successfully! Prorated credit of ₹" . number_format($res['amount_credited'], 2) . " was credited to your wallet.";
            return $request->wantsJson() ? response()->json(['success' => true, 'message' => $msg]) : back()->with('success', $msg);
        }

        // Case 3: Initial Subscription or Same Plan Renewal
        $isRenewal = $activeSubscription && $activeSubscription->plan_id === $plan->id;
        $txDesc = $isRenewal
            ? "Plan Extension: +{$duration->formatted_duration} added to {$plan->name}"
            : "Subscription to {$plan->name} ({$duration->formatted_duration})";

        return DB::transaction(function () use ($user, $plan, $duration, $amountDue, $isRenewal, $txDesc, $request) {
            if ($amountDue > 0) {
                $debitResult = $this->walletService->debit(
                    user: $user,
                    amount: $amountDue,
                    source: $isRenewal ? 'subscription_renewal' : 'subscription_purchase',
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
            $msg = $isRenewal
                ? "🎉 Extended {$plan->name} (+{$duration->formatted_duration}) successfully! Valid until " . $subscription->billing_end->format('M d, Y') . "."
                : "🎉 Subscribed to {$plan->name} ({$duration->formatted_duration}) successfully! Valid until " . $subscription->billing_end->format('M d, Y') . ".";

            return $request->wantsJson()
                ? response()->json(['success' => true, 'message' => $msg, 'subscription_id' => $subscription->id])
                : back()->with('success', $msg);
        });
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

        $proration = $this->planChangeService->calculateProration($activeSub, $plan, $duration);
        $amountDue = (float) $proration['amount_due'];
        $isUpgrade = $amountDue > 0;
        $proratedCredit = $amountDue < 0 ? abs($amountDue) : 0.0;

        return [
            'action_type' => $isUpgrade ? 'upgrade' : 'downgrade',
            'base_price' => $basePrice,
            'proration' => $proration,
            'final_amount' => $isUpgrade ? $amountDue : 0.0,
            'prorated_credit' => $proratedCredit,
            'discount_percent' => (float) $duration->discount_percent,
            'is_upgrade' => $isUpgrade,
            'is_downgrade' => !$isUpgrade,
        ];
    }
}
