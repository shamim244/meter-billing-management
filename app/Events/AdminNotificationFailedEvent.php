<?php

namespace App\Events;

use App\Models\Notification;
use App\Models\NotificationDelivery;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminNotificationFailedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Notification $notification,
        public NotificationDelivery $delivery,
        public string $reason
    ) {}
}
