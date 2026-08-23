<?php

namespace App\Services\Notifications\Drivers\Channels;

use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Services\Notifications\Contracts\ChannelDriverInterface;
use App\Services\Notifications\Contracts\DeliveryResult;
use App\Services\Notifications\EmailProviderRegistryService;

class EmailChannelDriver implements ChannelDriverInterface
{
    protected EmailProviderRegistryService $registry;

    public function __construct(EmailProviderRegistryService $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Send email notification by walking the registry fallback chain.
     */
    public function send(Notification $notification, NotificationDelivery $delivery): DeliveryResult
    {
        return $this->registry->sendViaChain($notification, $delivery);
    }

    /**
     * Send email notification with explicit execution timeout in seconds.
     */
    public function sendWithTimeout(Notification $notification, NotificationDelivery $delivery, int $timeoutSeconds = 8): DeliveryResult
    {
        $prevSocketTimeout = ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', (string) $timeoutSeconds);

        try {
            return $this->registry->sendViaChain($notification, $delivery);
        } finally {
            if ($prevSocketTimeout !== false) {
                ini_set('default_socket_timeout', $prevSocketTimeout);
            }
        }
    }
}
