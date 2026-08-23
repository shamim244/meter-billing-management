<?php

namespace App\Services\Notifications\Contracts;

use App\Models\Notification;
use App\Models\NotificationDelivery;

interface ChannelDriverInterface
{
    /**
     * Send the notification across this channel.
     */
    public function send(Notification $notification, NotificationDelivery $delivery): DeliveryResult;
}
