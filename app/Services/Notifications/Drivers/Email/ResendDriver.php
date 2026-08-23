<?php

namespace App\Services\Notifications\Drivers\Email;

use App\Services\Notifications\Contracts\EmailProviderDriverInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ResendDriver implements EmailProviderDriverInterface
{
    /**
     * Send transactional email using Resend API.
     */
    public function send(string $to, string $subject, string $htmlBody, array $config): bool
    {
        $apiKey = $config['api_key'] ?? '';
        if (empty($apiKey)) {
            throw new RuntimeException("Resend API key is missing from provider instance configuration.");
        }

        $fromAddress = $config['from_address'] ?? config('mail.from.address', 'notifications@nexgenhub.site');
        $fromName = $config['from_name'] ?? config('mail.from.name', 'NBPDCL Billing Platform');
        $from = "{$fromName} <{$fromAddress}>";

        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post('https://api.resend.com/emails', [
                'from' => $from,
                'to' => [$to],
                'subject' => $subject,
                'html' => $htmlBody,
            ]);

        if (!$response->successful()) {
            $err = $response->json('message') ?? $response->body();
            throw new RuntimeException("Resend API failed ({$response->status()}): {$err}");
        }

        return true;
    }
}
