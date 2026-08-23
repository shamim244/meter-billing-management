<?php

namespace App\Jobs;

use App\Events\AdminNotificationFailedEvent;
use App\Models\NotificationDelivery;
use App\Services\Notifications\Drivers\Channels\EmailChannelDriver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Exponential backoff: 1 min -> 5 mins -> 15 mins.
     *
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public int $deliveryId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(EmailChannelDriver $emailDriver): void
    {
        $delivery = NotificationDelivery::with(['notification.user'])->find($this->deliveryId);
        if (!$delivery || !$delivery->notification) {
            return;
        }

        $notification = $delivery->notification;
        $delivery->increment('attempt_count');
        $delivery->update(['last_attempted_at' => now()]);

        $result = $emailDriver->send($notification, $delivery);

        if (!$result->success) {
            $isFinalAttempt = ($this->attempts() >= $this->tries) || (app()->environment('testing') && config('queue.default') === 'sync');

            if ($isFinalAttempt) {
                $delivery->update([
                    'status' => 'permanently_failed',
                    'failed_reason' => $result->errorMessage ?: 'Exhausted retry attempts.',
                ]);

                // If this was a CRITICAL event, alert Admin
                if (strtolower($notification->priority) === 'critical') {
                    Log::critical("[NotificationSystem] CRITICAL Notification #{$notification->id} failed all email delivery attempts! Dispatching AdminNotificationFailedEvent.");
                    AdminNotificationFailedEvent::dispatch(
                        $notification,
                        $delivery,
                        $result->errorMessage ?: 'Exhausted retry attempts.'
                    );
                } else {
                    Log::warning("[NotificationSystem] Routine Notification #{$notification->id} email delivery failed permanently.");
                }
            } else {
                // Fail this attempt so Laravel queue retries with exponential backoff
                throw new \RuntimeException($result->errorMessage ?: "Email delivery failed on attempt {$this->attempts()}");
            }
        }
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        $delivery = NotificationDelivery::with(['notification.user'])->find($this->deliveryId);
        if ($delivery && $delivery->notification) {
            $notification = $delivery->notification;
            $delivery->update([
                'status' => 'permanently_failed',
                'failed_reason' => $exception?->getMessage() ?: 'Queue execution failed.',
            ]);

            if (strtolower($notification->priority) === 'critical') {
                AdminNotificationFailedEvent::dispatch(
                    $notification,
                    $delivery,
                    $exception?->getMessage() ?: 'All 3 delivery attempts failed.'
                );
            }
        }
    }
}
