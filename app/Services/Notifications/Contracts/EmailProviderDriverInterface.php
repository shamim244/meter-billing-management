<?php

namespace App\Services\Notifications\Contracts;

interface EmailProviderDriverInterface
{
    /**
     * Send email via this specific provider.
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $htmlBody Rendered HTML content
     * @param array $config Decrypted credentials and provider settings
     * @return bool True on success, false on failure (or throws exception caught by registry)
     */
    public function send(string $to, string $subject, string $htmlBody, array $config): bool;
}
