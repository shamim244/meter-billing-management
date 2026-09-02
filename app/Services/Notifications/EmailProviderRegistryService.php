<?php

namespace App\Services\Notifications;

use App\Models\EmailProviderInstance;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Services\Notifications\Contracts\DeliveryResult;
use App\Services\Notifications\Contracts\EmailProviderDriverInterface;
use App\Services\Notifications\Drivers\Email\BrevoDriver;
use App\Services\Notifications\Drivers\Email\HostingerDriver;
use App\Services\Notifications\Drivers\Email\ResendDriver;
use App\Services\Notifications\Drivers\Email\SmtpDriver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class EmailProviderRegistryService
{
    /**
     * Cache or resolve driver instances.
     *
     * @var array<string, EmailProviderDriverInterface>
     */
    protected array $drivers = [];

    public function __construct()
    {
        $this->registerDriver('smtp', new SmtpDriver());
        $this->registerDriver('resend', new ResendDriver());
        $this->registerDriver('brevo', new BrevoDriver());
        $this->registerDriver('hostinger', new HostingerDriver());
    }

    /**
     * Register a driver instance for a driver type.
     */
    public function registerDriver(string $type, EmailProviderDriverInterface $driver): void
    {
        $this->drivers[strtolower($type)] = $driver;
    }

    /**
     * Resolve driver by type.
     */
    public function getDriver(string $type): EmailProviderDriverInterface
    {
        $normalized = strtolower(trim($type));
        if (!isset($this->drivers[$normalized])) {
            throw new InvalidArgumentException("Unsupported email provider driver type: [{$type}]");
        }

        return $this->drivers[$normalized];
    }

    /**
     * Get all active/enabled providers sorted by priority (1 = highest).
     *
     * @return Collection<int, EmailProviderInstance>
     */
    public function getEnabledProvidersInPriorityOrder(): Collection
    {
        return EmailProviderInstance::enabled()->get();
    }

    /**
     * Send email via the fallback chain.
     * Walks enabled providers in priority order, stops on first success, and records the successful instance.
     */
    public function sendViaChain(Notification $notification, NotificationDelivery $delivery): DeliveryResult
    {
        $recipientEmail = $notification->user?->email ?: ($notification->data['email'] ?? null);
        if (empty($recipientEmail)) {
            $delivery->update([
                'status' => 'failed',
                'failed_reason' => 'Recipient user email is empty or missing.',
                'last_attempted_at' => now(),
            ]);
            return DeliveryResult::failure('Recipient email is missing.');
        }

        $subject = $notification->title ?: 'Notification from NBPDCL Platform';
        $body = $this->wrapInHtmlTemplate($notification->title, $notification->body, $notification->user?->name ?? 'Billing Agent');

        $providers = $this->getEnabledProvidersInPriorityOrder();
        if ($providers->isEmpty()) {
            $errMsg = 'No enabled email provider instances found in registry.';
            Log::error("[EmailRegistry] {$errMsg}");
            $delivery->update([
                'status' => 'failed',
                'failed_reason' => $errMsg,
                'last_attempted_at' => now(),
            ]);
            return DeliveryResult::failure($errMsg);
        }

        $lastError = null;

        foreach ($providers as $provider) {
            try {
                $driver = $this->getDriver($provider->driver_type);
                $config = is_array($provider->config) ? $provider->config : [];

                $sent = $driver->send($recipientEmail, $subject, $body, $config);

                if ($sent) {
                    // Succeeded with this provider!
                    $provider->update([
                        'last_used_at' => now(),
                        'last_failure_reason' => null,
                    ]);

                    $delivery->update([
                        'status' => 'sent',
                        'email_provider_instance_id' => $provider->id,
                        'last_attempted_at' => now(),
                        'failed_reason' => null,
                    ]);

                    Log::info("[EmailRegistry] Notification #{$notification->id} delivered successfully via Provider #{$provider->id} [{$provider->label}].");

                    return DeliveryResult::success($provider->id);
                }
            } catch (Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning("[EmailRegistry] Provider #{$provider->id} [{$provider->label}] failed: {$lastError}. Attempting next provider in fallback chain...");

                $provider->update([
                    'last_failure_at' => now(),
                    'last_failure_reason' => $lastError,
                ]);
            }
        }

        // All providers in chain failed
        $delivery->update([
            'status' => 'failed',
            'failed_reason' => "All providers in fallback chain failed. Last error: {$lastError}",
            'last_attempted_at' => now(),
        ]);

        return DeliveryResult::failure($lastError);
    }

    /**
     * Send test email directly through a single provider instance (bypasses fallback chain).
     *
     * @return array{success: bool, message: string}
     */
    public function testSend(int $providerInstanceId, string $testRecipient): array
    {
        $provider = EmailProviderInstance::findOrFail($providerInstanceId);
        $driver = $this->getDriver($provider->driver_type);
        $config = is_array($provider->config) ? $provider->config : [];

        $subject = "Test Email from NBPDCL Platform [{$provider->label}]";
        $body = $this->wrapInHtmlTemplate(
            "Email Provider Test",
            "This is a test email sent directly through provider instance <strong>{$provider->label}</strong> ({$provider->driver_type}) at " . now()->toDateTimeString() . ".",
            "Administrator"
        );

        try {
            $driver->send($testRecipient, $subject, $body, $config);

            $provider->update([
                'last_used_at' => now(),
                'last_failure_reason' => null,
            ]);

            return [
                'success' => true,
                'message' => "Test email successfully delivered to {$testRecipient} via [{$provider->label}].",
            ];
        } catch (Throwable $e) {
            $provider->update([
                'last_failure_at' => now(),
                'last_failure_reason' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => "Test send failed: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Wrap body in clean HTML email layout.
     */
    public function wrapInHtmlTemplate(string $title, string $body, string $name = 'Billing Agent'): string
    {
        $appName = config('app.name', 'NBPDCL SaaS Billing Platform');
        $safeBody = nl2br($body);
        $safeBody = preg_replace('~(https?://[^\s<]+)~i', '<a href="$1" style="color: #38bdf8; text-decoration: underline; word-break: break-all;" target="_blank">$1</a>', $safeBody);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{$title}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0f172a; color: #f8fafc; margin: 0; padding: 30px 15px;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table width="600" style="max-width: 600px; background-color: #1e293b; border-radius: 16px; border: 1px solid #334155; overflow: hidden;" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="padding: 24px 32px; background: linear-gradient(135deg, #1e293b, #0f172a); border-bottom: 1px solid #334155;">
                            <div style="font-size: 18px; font-weight: 800; color: #38bdf8; letter-spacing: -0.5px;">⚡ {$appName}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px; color: #e2e8f0; font-size: 14px; line-height: 1.6;">
                            <h2 style="font-size: 18px; font-weight: 700; color: #ffffff; margin-top: 0; margin-bottom: 16px;">{$title}</h2>
                            <p style="color: #94a3b8; margin-bottom: 20px;">Hello <strong>{$name}</strong>,</p>
                            <div style="background-color: #0f172a; padding: 20px; border-radius: 12px; border: 1px solid #334155; color: #cbd5e1; font-size: 14px;">
                                {$safeBody}
                            </div>
                            <p style="color: #64748b; font-size: 12px; margin-top: 24px; margin-bottom: 0;">
                                This is an automated notification from your NBPDCL Billing Platform. You can manage your notification preferences in your dashboard.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
