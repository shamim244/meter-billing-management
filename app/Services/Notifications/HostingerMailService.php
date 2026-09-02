<?php

namespace App\Services\Notifications;

use App\Models\EmailProviderInstance;
use App\Services\Notifications\Drivers\Email\HostingerDriver;
use Illuminate\Support\Facades\Log;

class HostingerMailService
{
    protected HostingerDriver $driver;

    public function __construct(HostingerDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Get the active Hostinger API token from configuration or database.
     */
    public function getActiveToken(): ?string
    {
        $envToken = config('services.hostinger_mail.api_key');
        if (! empty($envToken)) {
            return $envToken;
        }

        $provider = EmailProviderInstance::where('driver_type', 'hostinger')
            ->where('is_enabled', true)
            ->first();

        if ($provider && is_array($provider->config)) {
            return $provider->config['api_key'] ?? null;
        }

        return null;
    }

    /**
     * List all mailboxes on the Hostinger account.
     */
    public function getMailboxes(?string $apiKey = null): array
    {
        $token = $apiKey ?: $this->getActiveToken();
        if (empty($token)) {
            return [];
        }

        return $this->driver->getAvailableMailboxes($token);
    }

    /**
     * Send email via Hostinger Mail API.
     */
    public function send(string $to, string $subject, string $htmlBody, ?string $fromAddress = 'agent@nexgenhub.site', ?string $apiKey = null): bool
    {
        $token = $apiKey ?: $this->getActiveToken();
        if (empty($token)) {
            throw new \RuntimeException('No Hostinger API token found.');
        }

        return $this->driver->send($to, $subject, $htmlBody, [
            'api_key' => $token,
            'from_address' => $fromAddress,
        ]);
    }

    /**
     * List messages in INBOX for an address.
     */
    public function listInboxMessages(string $address = 'agent@nexgenhub.site', int $page = 1, int $perPage = 25, ?string $apiKey = null): array
    {
        $token = $apiKey ?: $this->getActiveToken();
        if (empty($token)) {
            return [];
        }

        $mailboxId = $this->driver->resolveMailboxResourceId($token, $address);
        if (empty($mailboxId)) {
            return [];
        }

        return $this->driver->listInboxMessages($token, $mailboxId, $page, $perPage);
    }

    /**
     * Get message text & HTML for a specific UID in an address.
     */
    public function getMessageText(string $address, int $uid, ?string $apiKey = null): ?array
    {
        $token = $apiKey ?: $this->getActiveToken();
        if (empty($token)) {
            return null;
        }

        $mailboxId = $this->driver->resolveMailboxResourceId($token, $address);
        if (empty($mailboxId)) {
            return null;
        }

        return $this->driver->getMessageText($token, $mailboxId, $uid);
    }
}
