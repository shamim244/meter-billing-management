<?php

namespace App\Services\Notifications\Drivers\Channels;

use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Services\Notifications\Contracts\ChannelDriverInterface;
use App\Services\Notifications\Contracts\DeliveryResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushChannelDriver implements ChannelDriverInterface
{
    /**
     * Send push notification via OneSignal API if configured.
     */
    public function send(Notification $notification, NotificationDelivery $delivery): DeliveryResult
    {
        $appId = config('services.onesignal.app_id');
        $apiKey = config('services.onesignal.rest_api_key');

        if (empty($appId) || empty($apiKey)) {
            // Push is optional in V1
            $delivery->update([
                'status' => 'sent',
                'attempt_count' => 1,
                'last_attempted_at' => now(),
            ]);
            return DeliveryResult::success();
        }

        try {
            $response = Http::withToken($apiKey)
                ->post('https://onesignal.com/api/v1/notifications', [
                    'app_id' => $appId,
                    'include_external_user_ids' => [(string) $notification->user_id],
                    'headings' => ['en' => $notification->title],
                    'contents' => ['en' => $notification->body],
                ]);

            if (!$response->successful()) {
                throw new \RuntimeException("OneSignal error: " . $response->body());
            }

            $delivery->update([
                'status' => 'sent',
                'attempt_count' => 1,
                'last_attempted_at' => now(),
            ]);

            return DeliveryResult::success();
        } catch (Throwable $e) {
            Log::warning("[PushChannelDriver] Push delivery failed: " . $e->getMessage());
            $delivery->update([
                'status' => 'failed',
                'failed_reason' => $e->getMessage(),
                'last_attempted_at' => now(),
            ]);

            return DeliveryResult::failure($e->getMessage());
        }
    }
}
