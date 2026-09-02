<?php

namespace App\Services\Notifications\Drivers\Email;

use App\Services\Notifications\Contracts\EmailProviderDriverInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HostingerDriver implements EmailProviderDriverInterface
{
    protected string $baseUrl = 'https://api.mail.hostinger.com';

    /**
     * Send transactional email using Hostinger Mail REST API.
     */
    public function send(string $to, string $subject, string $htmlBody, array $config): bool
    {
        $apiKey = $config['api_key'] ?? config('services.hostinger_mail.api_key', '');
        if (empty($apiKey)) {
            throw new RuntimeException("Hostinger Mail API token is missing from provider instance configuration.");
        }

        $fromAddress = $config['from_address'] ?? config('mail.from.address', 'agent@nexgenhub.site');
        $fromName = $config['from_name'] ?? config('mail.from.name', 'NBPDCL SaaS Billing');

        // Resolve mailbox resource ID
        $mailboxResourceId = $config['mailbox_resource_id'] ?? $this->resolveMailboxResourceId($apiKey, $fromAddress);

        if (empty($mailboxResourceId)) {
            throw new RuntimeException("Could not find an authorized Hostinger mailbox for address: {$fromAddress}");
        }

        $plainText = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));

        $response = Http::withToken($apiKey)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(20)
            ->post("{$this->baseUrl}/api/v1/mailboxes/{$mailboxResourceId}/send", [
                'to' => [$to],
                'subject' => $subject,
                'html' => $htmlBody,
                'text' => trim($plainText),
            ]);

        // Hostinger returns 204 No Content on successful send
        if ($response->status() !== 204 && ! $response->successful()) {
            $err = $response->json('error') ?? $response->json('message') ?? $response->body();
            throw new RuntimeException("Hostinger Mail API failed ({$response->status()}): {$err}");
        }

        return true;
    }

    /**
     * Resolve the mailbox resource ID for a given email address using /api/v1/me.
     */
    public function resolveMailboxResourceId(string $apiKey, ?string $targetAddress = null): ?string
    {
        $cacheKey = 'hostinger_mailbox_map_' . md5($apiKey);

        $mailboxes = Cache::remember($cacheKey, 3600, function () use ($apiKey) {
            $response = Http::withToken($apiKey)
                ->withHeaders(['Accept' => 'application/json'])
                ->timeout(15)
                ->get("{$this->baseUrl}/api/v1/me");

            if (! $response->successful()) {
                return [];
            }

            return $response->json('data.mailboxes') ?? [];
        });

        if (empty($mailboxes)) {
            return null;
        }

        if ($targetAddress) {
            foreach ($mailboxes as $mb) {
                if (strcasecmp($mb['address'] ?? '', $targetAddress) === 0) {
                    return $mb['resourceId'] ?? null;
                }
            }
        }

        // Default to first available mailbox if exact match not found
        return $mailboxes[0]['resourceId'] ?? null;
    }

    /**
     * List all mailboxes available on this account.
     */
    public function getAvailableMailboxes(string $apiKey): array
    {
        $response = Http::withToken($apiKey)
            ->withHeaders(['Accept' => 'application/json'])
            ->timeout(15)
            ->get("{$this->baseUrl}/api/v1/me");

        if (! $response->successful()) {
            return [];
        }

        return $response->json('data.mailboxes') ?? [];
    }

    /**
     * List inbox messages for a mailbox.
     */
    public function listInboxMessages(string $apiKey, string $mailboxResourceId, int $page = 1, int $perPage = 25): array
    {
        $response = Http::withToken($apiKey)
            ->withHeaders(['Accept' => 'application/json'])
            ->timeout(15)
            ->get("{$this->baseUrl}/api/v1/mailboxes/{$mailboxResourceId}/folders/INBOX/messages", [
                'page' => $page,
                'perPage' => $perPage,
            ]);

        return $response->json() ?? [];
    }

    /**
     * Get rendered text & HTML of a message.
     */
    public function getMessageText(string $apiKey, string $mailboxResourceId, int $uid): ?array
    {
        $response = Http::withToken($apiKey)
            ->withHeaders(['Accept' => 'application/json'])
            ->timeout(15)
            ->get("{$this->baseUrl}/api/v1/mailboxes/{$mailboxResourceId}/folders/INBOX/messages/{$uid}/text");

        return $response->json('data') ?? null;
    }
}
