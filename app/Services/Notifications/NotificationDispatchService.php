<?php

namespace App\Services\Notifications;

use App\Jobs\SendEmailNotificationJob;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Services\Notifications\Contracts\ChannelDriverInterface;
use App\Services\Notifications\Drivers\Channels\EmailChannelDriver;
use App\Services\Notifications\Drivers\Channels\InAppChannelDriver;
use App\Services\Notifications\Drivers\Channels\PushChannelDriver;
use Illuminate\Support\Facades\Log;

class NotificationDispatchService
{
    protected NotificationTemplateService $templateService;
    protected AgentPreferenceService $preferenceService;
    protected InAppChannelDriver $inAppDriver;
    protected EmailChannelDriver $emailDriver;
    protected PushChannelDriver $pushDriver;

    public function __construct(
        NotificationTemplateService $templateService,
        AgentPreferenceService $preferenceService,
        InAppChannelDriver $inAppDriver,
        EmailChannelDriver $emailDriver,
        PushChannelDriver $pushDriver
    ) {
        $this->templateService = $templateService;
        $this->preferenceService = $preferenceService;
        $this->inAppDriver = $inAppDriver;
        $this->emailDriver = $emailDriver;
        $this->pushDriver = $pushDriver;
    }

    /**
     * Single entry point for dispatching notifications from domain events.
     *
     * @param string $eventType e.g. 'subscription.suspended', 'wallet.debited'
     * @param User|null $recipient Recipient user or null for Admin broadcasts
     * @param array<string, mixed> $payload Merge field values
     * @param string|null $priorityOverride Explicit priority override ('critical' | 'routine')
     */
    public function dispatch(
        string $eventType,
        ?User $recipient,
        array $payload = [],
        ?string $priorityOverride = null
    ): Notification {
        // Automatically inject standard user placeholders
        if ($recipient) {
            $payload['agent_name'] = $payload['agent_name'] ?? $recipient->name;
            $payload['email'] = $payload['email'] ?? $recipient->email;
        }

        // 1. Resolve template, priority & dispatch_mode
        $templateInfo = $this->templateService->resolveTemplate($eventType, 'email');
        $priority = strtolower($priorityOverride ?: ($templateInfo['priority'] ?? 'routine'));
        $dispatchMode = strtolower($templateInfo['dispatch_mode'] ?? 'queued');
        $category = $this->resolveCategory($eventType);

        // 2. Render title & body
        $title = $this->templateService->renderMergeFields($templateInfo['subject'] ?: ucfirst(str_replace(['.', '_'], ' ', $eventType)), $payload);
        $body = $this->templateService->renderMergeFields($templateInfo['body_template'], $payload);

        // 3. Create master Notification record
        $notification = Notification::create([
            'user_id' => $recipient?->id,
            'event_type' => $eventType,
            'priority' => $priority,
            'title' => $title,
            'body' => $body,
            'data' => $payload,
            'read_at' => null,
            'created_at' => now(),
        ]);

        // 4. Determine applicable channels based on priority and Agent preferences
        $channels = $this->resolveApplicableChannels($recipient, $category, $priority);

        // 5. Create delivery records and dispatch to drivers
        foreach ($channels as $channel) {
            $delivery = NotificationDelivery::create([
                'notification_id' => $notification->id,
                'channel' => $channel,
                'status' => 'pending',
                'attempt_count' => 0,
                'created_at' => now(),
            ]);

            $this->handOffToChannel($channel, $notification, $delivery, $dispatchMode);
        }

        return $notification;
    }

    /**
     * Resolve which channels should fire for this notification.
     * Enforces: In-App is ALWAYS included for CRITICAL events regardless of user preference.
     *
     * @return array<int, string>
     */
    public function resolveApplicableChannels(?User $recipient, string $category, string $priority): array
    {
        $channels = [];

        // In-App channel is always applicable
        $channels[] = 'in_app';

        if (!$recipient) {
            return $channels;
        }

        // Email channel
        if ($this->preferenceService->isChannelEnabled($recipient->id, $category, 'email', $priority)) {
            $channels[] = 'email';
        }

        // Push channel (only for critical or if enabled)
        if ($priority === 'critical' || $this->preferenceService->isChannelEnabled($recipient->id, $category, 'push', $priority)) {
            $channels[] = 'push';
        }

        return array_unique($channels);
    }

    /**
     * Hand off delivery to appropriate channel driver or queue.
     */
    protected function handOffToChannel(
        string $channel,
        Notification $notification,
        NotificationDelivery $delivery,
        string $dispatchMode = 'queued'
    ): void {
        switch ($channel) {
            case 'in_app':
                $this->inAppDriver->send($notification, $delivery);
                break;

            case 'email':
                if ($dispatchMode === 'sync') {
                    $sentSuccessfully = false;
                    $startTime = microtime(true);

                    try {
                        // Attempt immediate send within current request using an 8-second timeout
                        $result = $this->emailDriver->sendWithTimeout($notification, $delivery, 8);
                        $elapsed = microtime(true) - $startTime;

                        if ($result && $result->success && $elapsed <= 8.5) {
                            $sentSuccessfully = true;
                        }
                    } catch (\Throwable $e) {
                        Log::warning("[NotificationDispatch] Immediate sync email attempt failed or timed out for delivery #{$delivery->id}: {$e->getMessage()}. Falling back to queued dispatch.");
                    }

                    if (!$sentSuccessfully) {
                        // Fall back to normal QUEUED dispatch instead so request never hangs
                        SendEmailNotificationJob::dispatch($delivery->id);
                    }
                } else {
                    // Normal QUEUED dispatch path (unchanged)
                    SendEmailNotificationJob::dispatch($delivery->id);
                }
                break;

            case 'push':
                $this->pushDriver->send($notification, $delivery);
                break;

            default:
                Log::warning("[NotificationDispatch] Unknown channel: {$channel}");
                break;
        }
    }

    /**
     * Map event type to preference category.
     */
    protected function resolveCategory(string $eventType): string
    {
        if (str_starts_with($eventType, 'wallet.')) {
            return 'wallet';
        }

        if (str_starts_with($eventType, 'usage.')) {
            return 'usage_reports';
        }

        return 'billing';
    }
}
