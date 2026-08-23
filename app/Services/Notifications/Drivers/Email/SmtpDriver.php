<?php

namespace App\Services\Notifications\Drivers\Email;

use App\Services\Notifications\Contracts\EmailProviderDriverInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class SmtpDriver implements EmailProviderDriverInterface
{
    /**
     * Send email using native Symfony/Laravel SMTP transport.
     */
    public function send(string $to, string $subject, string $htmlBody, array $config): bool
    {
        $host = $config['host'] ?? config('mail.mailers.smtp.host', '127.0.0.1');
        $port = (int) ($config['port'] ?? config('mail.mailers.smtp.port', 587));
        $encryption = strtolower($config['encryption'] ?? config('mail.mailers.smtp.encryption', 'tls'));
        $username = $config['username'] ?? config('mail.mailers.smtp.username');
        $password = $config['password'] ?? config('mail.mailers.smtp.password');

        // Port 465 or 'ssl' requires direct SMTPS (true).
        // Port 587 or 'tls' requires plain connect + STARTTLS negotiation (null).
        // Port 25 or 'null' disables TLS (false).
        $isTls = ($port === 465 || $encryption === 'ssl') ? true : (($encryption === 'null' || $encryption === 'none') ? false : null);

        $transport = new EsmtpTransport($host, $port, $isTls);

        if (!empty($username)) {
            $transport->setUsername($username);
        }
        if (!empty($password)) {
            $transport->setPassword($password);
        }

        $mailer = new Mailer($transport);

        $fromAddress = $config['from_address'] ?? config('mail.from.address', 'notifications@nexgenhub.site');
        $fromName = $config['from_name'] ?? config('mail.from.name', 'NBPDCL Billing Platform');

        $email = (new Email())
            ->from(new Address($fromAddress, $fromName))
            ->to($to)
            ->subject($subject)
            ->html($htmlBody);

        try {
            @$mailer->send($email);
            return true;
        } catch (\Throwable $e) {
            throw new \RuntimeException("SMTP connection failed: " . $e->getMessage(), 0, $e);
        }
    }
}
