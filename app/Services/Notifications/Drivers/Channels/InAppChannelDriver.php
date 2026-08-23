<?php

namespace App\Services\Notifications\Drivers\Channels;

use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Services\Notifications\Contracts\ChannelDriverInterface;
use App\Services\Notifications\Contracts\DeliveryResult;

class InAppChannelDriver implements ChannelDriverInterface
{
    /**
     * In-App delivery is inherently persistent via the notifications table.
     */
    public function send(Notification $notification, NotificationDelivery $delivery): DeliveryResult
    {
        $delivery->update([
            'status' => 'sent',
            'attempt_count' => 1,
            'last_attempted_at' => now(),
            'failed_reason' => null,
        ]);

        return DeliveryResult::success();
    }
}
