<?php

namespace App\Services\Notifications;

use App\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Collection;

class NotificationTemplateService
{
    /**
     * Get or resolve template for an event and channel.
     */
    public function getTemplate(string $eventType, string $channel = 'email'): ?NotificationTemplate
    {
        return NotificationTemplate::where('event_type', $eventType)
            ->where('channel', $channel)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Resolve template details (database record or factory default).
     *
     * @return array{subject: ?string, body_template: string, priority: string, dispatch_mode: string}
     */
    public function resolveTemplate(string $eventType, string $channel = 'email'): array
    {
        $template = $this->getTemplate($eventType, $channel);
        if ($template) {
            return [
                'subject' => $template->subject,
                'body_template' => $template->body_template,
                'priority' => $template->priority,
                'dispatch_mode' => $template->dispatch_mode ?? 'queued',
            ];
        }

        $defaults = $this->getFactoryDefaults();
        $def = $defaults[$eventType] ?? [
            'subject' => 'Notification: ' . ucfirst(str_replace(['.', '_'], ' ', $eventType)),
            'body_template' => 'You have a new update regarding {event_type}.',
            'priority' => 'routine',
            'dispatch_mode' => 'queued',
            'category' => 'billing',
        ];

        return [
            'subject' => $def['subject'],
            'body_template' => $def['body_template'],
            'priority' => $def['priority'],
            'dispatch_mode' => $def['dispatch_mode'] ?? 'queued',
        ];
    }

    /**
     * Replace {placeholders} with event payload values.
     */
    public function renderMergeFields(string $template, array $data): string
    {
        $rendered = $template;
        foreach ($data as $key => $value) {
            if (is_scalar($value) || is_null($value)) {
                $rendered = str_replace('{' . $key . '}', (string) ($value ?? ''), $rendered);
            }
        }
        return $rendered;
    }

    /**
     * Get all templates for admin management.
     */
    public function getAllTemplates(): Collection
    {
        return NotificationTemplate::orderBy('event_type')->orderBy('channel')->get();
    }

    /**
     * Update template.
     */
    public function updateTemplate(int $id, array $data): NotificationTemplate
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->update([
            'subject' => $data['subject'] ?? $template->subject,
            'body_template' => $data['body_template'] ?? $template->body_template,
            'priority' => $data['priority'] ?? $template->priority,
            'dispatch_mode' => $data['dispatch_mode'] ?? $template->dispatch_mode ?? 'queued',
            'is_active' => $data['is_active'] ?? $template->is_active,
        ]);

        return $template;
    }

    /**
     * Seed or reset templates to factory defaults.
     */
    public function resetToDefaults(?string $eventType = null): void
    {
        $defaults = $this->getFactoryDefaults();

        foreach ($defaults as $type => $info) {
            if ($eventType !== null && $type !== $eventType) {
                continue;
            }

            $dispatchMode = $info['dispatch_mode'] ?? 'queued';

            // Email template
            NotificationTemplate::updateOrCreate(
                ['event_type' => $type, 'channel' => 'email'],
                [
                    'subject' => $info['subject'],
                    'body_template' => $info['body_template'],
                    'priority' => $info['priority'],
                    'dispatch_mode' => $dispatchMode,
                    'is_active' => true,
                ]
            );

            // In-app template
            NotificationTemplate::updateOrCreate(
                ['event_type' => $type, 'channel' => 'in_app'],
                [
                    'subject' => null,
                    'body_template' => $info['body_template'],
                    'priority' => $info['priority'],
                    'dispatch_mode' => 'sync', // In-app is always sync
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Factory default definitions for all domain events.
     */
    public function getFactoryDefaults(): array
    {
        return [
            // Payment Gateway
            'payment.success' => [
                'subject' => 'Payment Successful — ₹{amount}',
                'body_template' => 'Your payment of ₹{amount} via {gateway} ({payment_method}) was successful. Reference: {transaction_id}.',
                'priority' => 'routine',
                'dispatch_mode' => 'sync',
                'category' => 'billing',
            ],
            'payment.failed' => [
                'subject' => 'Payment Failed — ₹{amount}',
                'body_template' => 'Your payment attempt of ₹{amount} failed. Reason: {failure_reason}.',
                'priority' => 'routine',
                'category' => 'billing',
            ],
            'payment.manual_submitted' => [
                'subject' => 'Manual Payment Submitted for Verification — ₹{amount}',
                'body_template' => 'Your manual payment request of ₹{amount} (Ref: {utr_number}) has been submitted and is pending Admin review.',
                'priority' => 'routine',
                'category' => 'billing',
            ],
            'payment.manual_approved' => [
                'subject' => 'Manual Payment Approved — ₹{amount}',
                'body_template' => 'Your manual payment of ₹{amount} has been approved by Administrator {admin_name} and credited to your account.',
                'priority' => 'routine',
                'category' => 'billing',
            ],
            'payment.manual_rejected' => [
                'subject' => 'Manual Payment Rejected — ₹{amount}',
                'body_template' => 'Your manual payment submission of ₹{amount} was rejected. Reason: {reason}.',
                'priority' => 'routine',
                'category' => 'billing',
            ],
            'payment.mandate_failed' => [
                'subject' => 'Recurring Payment Mandate Failed',
                'body_template' => 'Auto-debit recurring mandate attempt failed: {reason}. Please review your payment method.',
                'priority' => 'routine',
                'category' => 'billing',
            ],

            // Wallet System
            'wallet.credited' => [
                'subject' => 'Wallet Credited — ₹{amount}',
                'body_template' => '₹{amount} has been credited to your platform wallet ({description}). Current balance: ₹{balance}.',
                'priority' => 'routine',
                'category' => 'wallet',
            ],
            'wallet.debited' => [
                'subject' => 'Wallet Debited — ₹{amount}',
                'body_template' => '₹{amount} has been debited from your wallet ({description}). Current balance: ₹{balance}.',
                'priority' => 'routine',
                'category' => 'wallet',
            ],
            'wallet.low_balance' => [
                'subject' => 'Low Wallet Balance Alert — ₹{balance}',
                'body_template' => 'Your wallet balance of ₹{balance} is below the threshold of ₹{threshold}. Please top up to avoid service interruption.',
                'priority' => 'routine',
                'category' => 'wallet',
            ],
            'wallet.critical_balance' => [
                'subject' => 'CRITICAL: Low Wallet Balance (₹{balance})',
                'body_template' => 'CRITICAL ALERT: Your wallet balance has reached ₹{balance}. Automatic renewals and extra consumer processing will be blocked.',
                'priority' => 'critical',
                'category' => 'wallet',
            ],
            'wallet.insufficient_for_renewal' => [
                'subject' => 'Insufficient Wallet Balance for Upcoming Renewal',
                'body_template' => 'Your subscription renewal requires ₹{required_amount}, but your wallet balance is ₹{balance}. Please top up before your renewal date.',
                'priority' => 'routine',
                'category' => 'wallet',
            ],
            'wallet.frozen' => [
                'subject' => 'CRITICAL: Wallet Access Suspended / Frozen',
                'body_template' => 'Your wallet has been frozen by Admin ({admin_name}). Reason: {reason}. All billable features are locked.',
                'priority' => 'critical',
                'category' => 'wallet',
            ],
            'wallet.unfrozen' => [
                'subject' => 'Wallet Unfrozen / Restored',
                'body_template' => 'Your wallet has been unfrozen by Admin ({admin_name}). Normal operations and debits have been restored.',
                'priority' => 'routine',
                'category' => 'wallet',
            ],

            // Plan Management System
            'mru.locked' => [
                'subject' => 'MRU Workspace Locked — {mru_code}',
                'body_template' => 'MRU Workspace [{mru_code}] has been locked. Reason: {reason}. Unlock it from your dashboard or upgrade your plan.',
                'priority' => 'routine',
                'category' => 'billing',
            ],
            'mru.unlocked' => [
                'subject' => 'MRU Workspace Unlocked — {mru_code}',
                'body_template' => 'MRU Workspace [{mru_code}] has been unlocked ({method}). Data processing has resumed.',
                'priority' => 'routine',
                'category' => 'billing',
            ],
            'mru.overage_charged' => [
                'subject' => 'Extra MRU Creation Fee Charged — ₹{amount}',
                'body_template' => '₹{amount} was debited from your wallet for activating extra MRU [{mru_code}] beyond your included plan quota.',
                'priority' => 'routine',
                'category' => 'billing',
            ],
            'consumer.overage_charged' => [
                'subject' => 'Extra Consumer Processing Fee Charged — ₹{amount}',
                'body_template' => '₹{amount} was debited for processing {extra_count} extra consumers beyond your included monthly quota in cycle {cycle_month}/{cycle_year}.',
                'priority' => 'routine',
                'category' => 'billing',
            ],
            'agent.subscribed' => [
                'subject' => 'Welcome to Plan {plan_name}',
                'body_template' => 'You are now subscribed to {plan_name}. Included MRUs: {included_mrus}, Included Consumers: {included_consumers}.',
                'priority' => 'routine',
                'category' => 'billing',
            ],
            'agent.plan_migrated' => [
                'subject' => 'Subscription Plan Migrated to {to_plan}',
                'body_template' => 'Your subscription has been migrated from {from_plan} to {to_plan} by Administrator {admin_name}.',
                'priority' => 'routine',
                'category' => 'billing',
            ],

            // Billing & Subscription System
            'subscription.renewal_due' => [
                'subject' => 'Subscription Renewal Due in {days_remaining} Days',
                'body_template' => 'Your {plan_name} subscription is due for renewal in {days_remaining} days. Ensure sufficient wallet balance.',
                'priority' => 'routine',
                'category' => 'billing',
            ],
            'subscription.grace_period' => [
                'subject' => 'CRITICAL: Subscription Entered Grace Period',
                'body_template' => 'CRITICAL: Your subscription renewal could not be processed. You have entered a grace period ending on {grace_period_ends_at}. Top up your wallet to prevent account suspension.',
                'priority' => 'critical',
                'category' => 'billing',
            ],
            'subscription.suspended' => [
                'subject' => 'CRITICAL: Account Suspended — Read-Only Mode Activated',
                'body_template' => 'CRITICAL: Your subscription has expired and your account is SUSPENDED. All PDF processing and downloads are blocked until payment is received.',
                'priority' => 'critical',
                'category' => 'billing',
            ],
            'subscription.reactivated' => [
                'subject' => 'Subscription Reactivated Successfully',
                'body_template' => 'Your {plan_name} subscription has been reactivated ({method}). All features are now active.',
                'priority' => 'routine',
                'category' => 'billing',
            ],
            'subscription.renewal_failed' => [
                'subject' => 'Auto-Renewal Failed — Low Wallet Balance',
                'body_template' => 'Auto-renewal for {plan_name} failed. Required: ₹{required_amount}, Current Balance: ₹{wallet_balance}.',
                'priority' => 'routine',
                'category' => 'billing',
            ],
            'subscription.upgraded' => [
                'subject' => 'Subscription Upgraded: {old_plan} → {new_plan}',
                'body_template' => 'Your upgrade to {new_plan} is complete. Prorated charge of ₹{prorated_charge} was debited from your wallet.',
                'priority' => 'routine',
                'category' => 'billing',
            ],
            'subscription.downgraded' => [
                'subject' => 'Subscription Downgraded: {old_plan} → {new_plan}',
                'body_template' => 'Your downgrade to {new_plan} is complete. Prorated credit of ₹{prorated_credit} was added to your wallet.',
                'priority' => 'routine',
                'category' => 'billing',
            ],

            // Usage Reports
            'usage.monthly_summary_ready' => [
                'subject' => 'Monthly Usage & ROI Summary Ready — {month}/{year}',
                'body_template' => 'Your monthly operational report for {month}/{year} is ready: {bills_processed} bills processed across {mrus_active} active MRUs ({data_coverage}% coverage).',
                'priority' => 'routine',
                'category' => 'usage_reports',
            ],

            // System / Auth
            'auth.welcome' => [
                'subject' => 'Welcome to NBPDCL SaaS Billing Platform',
                'body_template' => 'Welcome {agent_name}! Your billing agent account ({email}) is active and ready to process electricity meter reading ledgers.',
                'priority' => 'routine',
                'dispatch_mode' => 'sync',
                'category' => 'billing',
            ],
            'auth.password_reset' => [
                'subject' => 'Reset Your Password — NBPDCL Platform',
                'body_template' => 'Hello {agent_name},\n\nYou are receiving this email because we received a password reset request for your account.\n\nReset URL: {reset_url}\n\nThis password reset link will expire in 60 minutes. If you did not request a password reset, no further action is required.',
                'priority' => 'critical',
                'dispatch_mode' => 'sync',
                'category' => 'billing',
            ],
        ];
    }
}
