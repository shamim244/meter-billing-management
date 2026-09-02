<?php

namespace App\Listeners;

use App\Events\AgentPlanMigratedEvent;
use App\Events\AgentSubscribedEvent;
use App\Events\ConsumerOverageChargedEvent;
use App\Events\ManualPaymentApprovedEvent;
use App\Events\ManualPaymentRejectedEvent;
use App\Events\ManualPaymentSubmittedEvent;
use App\Events\MruLockedEvent;
use App\Events\MruOverageChargedEvent;
use App\Events\MruUnlockedEvent;
use App\Events\PaymentFailedEvent;
use App\Events\PaymentMandateFailedEvent;
use App\Events\PaymentSuccessEvent;
use App\Events\PlanDowngradedEvent;
use App\Events\PlanUpgradedEvent;
use App\Events\RenewalFailedInsufficientBalanceEvent;
use App\Events\SubscriptionEnteredGracePeriodEvent;
use App\Events\SubscriptionReactivatedEvent;
use App\Events\SubscriptionRenewalDueEvent;
use App\Events\SubscriptionSuspendedEvent;
use App\Events\WalletCreditedEvent;
use App\Events\WalletCriticalBalanceEvent;
use App\Events\WalletDebitedEvent;
use App\Events\WalletFrozenEvent;
use App\Events\WalletInsufficientForRenewalEvent;
use App\Events\WalletLowBalanceEvent;
use App\Events\WalletUnfrozenEvent;
use App\Services\Notifications\NotificationDispatchService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Events\Dispatcher;

class DomainNotificationSubscriber
{
    protected NotificationDispatchService $dispatcher;

    public function __construct(NotificationDispatchService $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    // --- Payment Gateway Handlers ---
    public function handlePaymentSuccess(PaymentSuccessEvent $event): void
    {
        $p = $event->payment;
        $modeStr = $p->mode instanceof \BackedEnum ? $p->mode->value : (string) $p->mode;
        
        // If it was a manual payment (manual_upi / bank_transfer), handleManualPaymentApproved handles sending the dedicated approval notification.
        if (in_array($modeStr, ['manual_upi', 'bank_transfer', 'upi_manual'], true)) {
            return;
        }

        $gatewayName = $p->gateway_payment_id ? 'Razorpay' : 'Payment Gateway';
        $methodName = $p->mode ? $p->mode->label() : 'Online';
        $txnId = $p->gateway_payment_id ?: ($p->utr_number ?: ($p->bank_reference ?: (string) $p->id));

        $this->dispatcher->dispatch('payment.success', $p->user, [
            'amount' => number_format($p->amount, 2),
            'gateway' => $gatewayName,
            'payment_method' => $methodName,
            'transaction_id' => $txnId,
            'purpose' => $p->purpose?->value ?? (string) $p->purpose,
        ]);
    }

    public function handlePaymentFailed(PaymentFailedEvent $event): void
    {
        $p = $event->payment;
        $this->dispatcher->dispatch('payment.failed', $p->user, [
            'amount' => number_format($p->amount, 2),
            'failure_reason' => $event->failureReason ?: 'Transaction declined by bank or payment gateway.',
        ]);
    }

    public function handleManualPaymentSubmitted(ManualPaymentSubmittedEvent $event): void
    {
        $p = $event->payment;
        $utr = $p->utr_number ?: ($p->bank_reference ?: 'Pending Verification');
        $this->dispatcher->dispatch('payment.manual_submitted', $p->user, [
            'amount' => number_format($p->amount, 2),
            'utr_number' => $utr,
        ]);
    }

    public function handleManualPaymentApproved(ManualPaymentApprovedEvent $event): void
    {
        $p = $event->payment;
        $this->dispatcher->dispatch('payment.manual_approved', $p->user, [
            'amount' => number_format($p->amount, 2),
            'admin_name' => $event->admin?->name ?? 'System Administrator',
        ]);
    }

    public function handleManualPaymentRejected(ManualPaymentRejectedEvent $event): void
    {
        $p = $event->payment;
        $this->dispatcher->dispatch('payment.manual_rejected', $p->user, [
            'amount' => number_format($p->amount, 2),
            'reason' => $event->rejectionReason ?: 'Payment proof could not be verified.',
            'admin_name' => $event->admin?->name ?? 'System Administrator',
        ]);
    }

    public function handlePaymentMandateFailed(PaymentMandateFailedEvent $event): void
    {
        $m = $event->mandate;
        $this->dispatcher->dispatch('payment.mandate_failed', $m->user, [
            'reason' => $event->reason ?: 'Mandate auto-debit request failed.',
        ]);
    }

    // --- Wallet System Handlers ---
    public function handleWalletCredited(WalletCreditedEvent $event): void
    {
        $source = $event->transaction->meta['source'] ?? '';
        // If it was a top-up payment, the user already received the payment confirmation receipt.
        if ($source === 'payment_topup') {
            return;
        }

        $amount = abs((float) ($event->transaction->amountFloat ?? ($event->transaction->amount ?? 0)));
        $description = $event->transaction->meta['description'] ?? 'Wallet Top-Up';
        $this->dispatcher->dispatch('wallet.credited', $event->user, [
            'amount' => number_format($amount, 2),
            'balance' => number_format((float) ($event->user->wallet?->balanceFloat ?? 0), 2),
            'description' => $description,
        ]);
    }

    public function handleWalletDebited(WalletDebitedEvent $event): void
    {
        $amount = abs((float) ($event->transaction->amountFloat ?? ($event->transaction->amount ?? 0)));
        $description = $event->transaction->meta['description'] ?? 'Service charge deduction';
        $this->dispatcher->dispatch('wallet.debited', $event->user, [
            'amount' => number_format($amount, 2),
            'balance' => number_format((float) ($event->user->wallet?->balanceFloat ?? 0), 2),
            'description' => $description,
        ]);
    }

    public function handleWalletLowBalance(WalletLowBalanceEvent $event): void
    {
        $this->dispatcher->dispatch('wallet.low_balance', $event->user, [
            'balance' => number_format($event->balance, 2),
            'threshold' => number_format($event->threshold, 2),
        ]);
    }

    public function handleWalletCriticalBalance(WalletCriticalBalanceEvent $event): void
    {
        $threshold = $event->subscriptionAmount ?? 0;
        $this->dispatcher->dispatch('wallet.critical_balance', $event->user, [
            'balance' => number_format($event->balance, 2),
            'threshold' => number_format($threshold, 2),
        ], 'critical');
    }

    public function handleWalletInsufficientForRenewal(WalletInsufficientForRenewalEvent $event): void
    {
        $this->dispatcher->dispatch('wallet.insufficient_for_renewal', $event->user, [
            'balance' => number_format($event->balance, 2),
            'required_amount' => number_format($event->requiredAmount, 2),
        ]);
    }

    public function handleWalletFrozen(WalletFrozenEvent $event): void
    {
        $this->dispatcher->dispatch('wallet.frozen', $event->user, [
            'reason' => $event->reason ?: 'Administrative security action',
            'admin_name' => $event->admin?->name ?? 'System Administrator',
        ], 'critical');
    }

    public function handleWalletUnfrozen(WalletUnfrozenEvent $event): void
    {
        $this->dispatcher->dispatch('wallet.unfrozen', $event->user, [
            'admin_name' => $event->admin?->name ?? 'System Administrator',
        ]);
    }

    // --- Plan Management Handlers ---
    public function handleMruLocked(MruLockedEvent $event): void
    {
        $this->dispatcher->dispatch('mru.locked', $event->mru->user, [
            'mru_code' => $event->mru->code,
            'reason' => $event->reason ?: 'Overage quota limit reached',
        ]);
    }

    public function handleMruUnlocked(MruUnlockedEvent $event): void
    {
        $feeText = $event->unlockFee ? ('Fee: ₹' . number_format($event->unlockFee, 2)) : 'Self-service unlock';
        $this->dispatcher->dispatch('mru.unlocked', $event->mru->user, [
            'mru_code' => $event->mru->code,
            'method' => $feeText,
        ]);
    }

    public function handleMruOverageCharged(MruOverageChargedEvent $event): void
    {
        $this->dispatcher->dispatch('mru.overage_charged', $event->user, [
            'mru_code' => $event->mru->code,
            'amount' => number_format($event->amount, 2),
        ]);
    }

    public function handleConsumerOverageCharged(ConsumerOverageChargedEvent $event): void
    {
        $this->dispatcher->dispatch('consumer.overage_charged', $event->user, [
            'cycle_month' => $event->cycle->cycle_month,
            'cycle_year' => $event->cycle->cycle_year,
            'extra_count' => number_format($event->extraConsumers),
            'amount' => number_format($event->amount, 2),
        ]);
    }

    public function handleAgentSubscribed(AgentSubscribedEvent $event): void
    {
        $sub = $event->subscription;
        $this->dispatcher->dispatch('agent.subscribed', $sub->user, [
            'plan_name' => $sub->plan?->name ?? 'Custom Plan',
            'included_mrus' => $sub->included_mrus_locked,
            'included_consumers' => number_format($sub->included_consumers_locked),
        ]);
    }

    public function handleAgentPlanMigrated(AgentPlanMigratedEvent $event): void
    {
        $sub = $event->subscription;
        $this->dispatcher->dispatch('agent.plan_migrated', $sub->user, [
            'from_plan' => $event->oldPlan?->name ?? 'Previous Plan',
            'to_plan' => $sub->plan?->name ?? 'New Plan',
            'admin_name' => 'System Administrator',
        ]);
    }

    // --- Billing & Subscription Handlers ---
    public function handleSubscriptionRenewalDue(SubscriptionRenewalDueEvent $event): void
    {
        $sub = $event->subscription;
        $days = (int) now()->diffInDays($sub->billing_end, false);
        $this->dispatcher->dispatch('subscription.renewal_due', $sub->user, [
            'plan_name' => $sub->plan?->name ?? 'Custom Plan',
            'days_remaining' => max(0, $days),
        ]);
    }

    public function handleSubscriptionEnteredGracePeriod(SubscriptionEnteredGracePeriodEvent $event): void
    {
        $sub = $event->subscription;
        $this->dispatcher->dispatch('subscription.grace_period', $sub->user, [
            'grace_period_ends_at' => $sub->grace_period_ends_at ? $sub->grace_period_ends_at->format('Y-m-d H:i') : 'in 3 days',
        ], 'critical');
    }

    public function handleSubscriptionSuspended(SubscriptionSuspendedEvent $event): void
    {
        $sub = $event->subscription;
        $this->dispatcher->dispatch('subscription.suspended', $sub->user, [
            'reason' => 'Grace period expired without payment renewal.',
        ], 'critical');
    }

    public function handleSubscriptionReactivated(SubscriptionReactivatedEvent $event): void
    {
        $sub = $event->subscription;
        $this->dispatcher->dispatch('subscription.reactivated', $sub->user, [
            'plan_name' => $sub->plan?->name ?? 'Custom Plan',
            'method' => $event->reason ?: 'Payment renewal',
        ]);
    }

    public function handleRenewalFailed(RenewalFailedInsufficientBalanceEvent $event): void
    {
        $sub = $event->subscription;
        $this->dispatcher->dispatch('subscription.renewal_failed', $sub->user, [
            'plan_name' => $sub->plan?->name ?? 'Custom Plan',
            'required_amount' => number_format($event->amountDue, 2),
            'wallet_balance' => number_format((float) ($sub->user->wallet?->balanceFloat ?? 0), 2),
        ]);
    }

    public function handlePlanUpgraded(PlanUpgradedEvent $event): void
    {
        $sub = $event->subscription;
        $this->dispatcher->dispatch('subscription.upgraded', $sub->user, [
            'old_plan' => $event->fromPlan->name,
            'new_plan' => $event->toPlan->name,
            'prorated_charge' => number_format($event->log->amount_charged ?? 0, 2),
        ]);
    }

    public function handlePlanDowngraded(PlanDowngradedEvent $event): void
    {
        $sub = $event->subscription;
        $this->dispatcher->dispatch('subscription.downgraded', $sub->user, [
            'old_plan' => $event->fromPlan->name,
            'new_plan' => $event->toPlan->name,
            'prorated_credit' => number_format($event->log->amount_charged ?? 0, 2),
        ]);
    }

    // --- Auth Events ---
    public function handleRegistered(Registered $event): void
    {
        if ($event->user instanceof \App\Models\User) {
            $this->dispatcher->dispatch('auth.welcome', $event->user, [
                'agent_name' => $event->user->name,
                'email' => $event->user->email,
            ]);
        }
    }

    /**
     * Register listeners for subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            PaymentSuccessEvent::class => 'handlePaymentSuccess',
            PaymentFailedEvent::class => 'handlePaymentFailed',
            ManualPaymentSubmittedEvent::class => 'handleManualPaymentSubmitted',
            ManualPaymentApprovedEvent::class => 'handleManualPaymentApproved',
            ManualPaymentRejectedEvent::class => 'handleManualPaymentRejected',
            PaymentMandateFailedEvent::class => 'handlePaymentMandateFailed',

            WalletCreditedEvent::class => 'handleWalletCredited',
            WalletDebitedEvent::class => 'handleWalletDebited',
            WalletLowBalanceEvent::class => 'handleWalletLowBalance',
            WalletCriticalBalanceEvent::class => 'handleWalletCriticalBalance',
            WalletInsufficientForRenewalEvent::class => 'handleWalletInsufficientForRenewal',
            WalletFrozenEvent::class => 'handleWalletFrozen',
            WalletUnfrozenEvent::class => 'handleWalletUnfrozen',

            MruLockedEvent::class => 'handleMruLocked',
            MruUnlockedEvent::class => 'handleMruUnlocked',
            MruOverageChargedEvent::class => 'handleMruOverageCharged',
            ConsumerOverageChargedEvent::class => 'handleConsumerOverageCharged',
            AgentSubscribedEvent::class => 'handleAgentSubscribed',
            AgentPlanMigratedEvent::class => 'handleAgentPlanMigrated',

            SubscriptionRenewalDueEvent::class => 'handleSubscriptionRenewalDue',
            SubscriptionEnteredGracePeriodEvent::class => 'handleSubscriptionEnteredGracePeriod',
            SubscriptionSuspendedEvent::class => 'handleSubscriptionSuspended',
            SubscriptionReactivatedEvent::class => 'handleSubscriptionReactivated',
            RenewalFailedInsufficientBalanceEvent::class => 'handleRenewalFailed',
            PlanUpgradedEvent::class => 'handlePlanUpgraded',
            PlanDowngradedEvent::class => 'handlePlanDowngraded',

            Registered::class => 'handleRegistered',
        ];
    }
}
