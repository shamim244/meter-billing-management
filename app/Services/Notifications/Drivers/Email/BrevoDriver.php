<?php

namespace App\Services\Notifications\Drivers\Email;

use App\Services\Notifications\Contracts\EmailProviderDriverInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BrevoDriver implements EmailProviderDriverInterface
{
    /**
     * Send transactional email using Brevo (Sendinblue) API.
     */
    public function send(string $to, string $subject, string $htmlBody, array $config): bool
    {
        $apiKey = $config['api_key'] ?? '';
        if (empty($apiKey)) {
            throw new RuntimeException("Brevo API key is missing from provider instance configuration.");
        }

        $fromAddress = $config['from_address'] ?? config('mail.from.address', 'notifications@nexgenhub.site');
        $fromName = $config['from_name'] ?? config('mail.from.name', 'NBPDCL Billing Platform');

        $response = Http::withHeaders([
            'api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
        ->timeout(15)
        ->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => $fromName,
                'email' => $fromAddress,
            ],
            'to' => [
                ['email' => $to],
            ],
            'subject' => $subject,
            'htmlContent' => $htmlBody,
        ]);

        if (!$response->successful()) {
            $err = $response->json('message') ?? $response->body();
            throw new RuntimeException("Brevo API failed ({$response->status()}): {$err}");
        }

        return true;
    }
}
