<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailProviderInstance;
use App\Models\NotificationDelivery;
use App\Services\Notifications\EmailProviderRegistryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminEmailProviderController extends Controller
{
    protected EmailProviderRegistryService $registry;

    public function __construct(EmailProviderRegistryService $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Display all email provider instances in fallback chain priority order.
     */
    public function index(): View
    {
        $providers = EmailProviderInstance::orderBy('priority', 'asc')->get();

        $recentDeliveries = NotificationDelivery::with(['notification.user', 'emailProviderInstance'])
            ->where('channel', 'email')
            ->orderBy('id', 'desc')
            ->limit(25)
            ->get();

        return view('admin.notifications.email-providers', compact('providers', 'recentDeliveries'));
    }

    /**
     * Store a new provider instance in the registry.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'driver_type' => ['required', 'in:smtp,resend,brevo'],
            'label' => ['required', 'string', 'max:128'],
            'priority' => ['required', 'integer', 'min:1', 'max:100'],
            'is_enabled' => ['nullable', 'boolean'],

            // SMTP Fields
            'smtp_host' => ['nullable', 'string'],
            'smtp_port' => ['nullable', 'integer'],
            'smtp_encryption' => ['nullable', 'string'],
            'smtp_username' => ['nullable', 'string'],
            'smtp_password' => ['nullable', 'string'],
            'from_address' => ['nullable', 'email'],
            'from_name' => ['nullable', 'string'],

            // API Fields (Resend / Brevo)
            'api_key' => ['nullable', 'string'],
        ]);

        $config = $this->extractConfig($validated['driver_type'], $request->all());

        EmailProviderInstance::create([
            'driver_type' => $validated['driver_type'],
            'label' => $validated['label'],
            'config' => $config, // encrypted:array cast
            'priority' => (int) $validated['priority'],
            'is_enabled' => $request->boolean('is_enabled', true),
        ]);

        return redirect()->route('admin.notifications.email_providers.index')
            ->with('success', "Email provider [{$validated['label']}] added to registry.");
    }

    /**
     * Update an existing provider instance.
     */
    public function update(Request $request, EmailProviderInstance $provider): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:128'],
            'priority' => ['required', 'integer', 'min:1', 'max:100'],
            'is_enabled' => ['nullable', 'boolean'],

            // SMTP
            'smtp_host' => ['nullable', 'string'],
            'smtp_port' => ['nullable', 'integer'],
            'smtp_encryption' => ['nullable', 'string'],
            'smtp_username' => ['nullable', 'string'],
            'smtp_password' => ['nullable', 'string'],
            'from_address' => ['nullable', 'email'],
            'from_name' => ['nullable', 'string'],

            // API
            'api_key' => ['nullable', 'string'],
        ]);

        $existingConfig = is_array($provider->config) ? $provider->config : [];
        $newConfig = $this->extractConfig($provider->driver_type, $request->all(), $existingConfig);

        $provider->update([
            'label' => $validated['label'],
            'priority' => (int) $validated['priority'],
            'is_enabled' => $request->boolean('is_enabled', true),
            'config' => $newConfig,
        ]);

        return redirect()->route('admin.notifications.email_providers.index')
            ->with('success', "Email provider [{$provider->label}] updated.");
    }

    /**
     * Toggle enabled / disabled state of a provider.
     */
    public function toggle(EmailProviderInstance $provider): RedirectResponse
    {
        // If attempting to disable the ONLY enabled provider, block it
        if ($provider->is_enabled) {
            $otherEnabledCount = EmailProviderInstance::where('is_enabled', true)
                ->where('id', '!=', $provider->id)
                ->count();

            if ($otherEnabledCount === 0) {
                return redirect()->route('admin.notifications.email_providers.index')
                    ->with('error', 'Cannot disable the only active email provider. At least one provider must remain enabled.');
            }
        }

        $provider->update(['is_enabled' => !$provider->is_enabled]);

        $state = $provider->is_enabled ? 'enabled' : 'disabled';
        return redirect()->route('admin.notifications.email_providers.index')
            ->with('success', "Provider [{$provider->label}] is now {$state}.");
    }

    /**
     * Send test email directly through this specific provider instance.
     */
    public function testSend(Request $request, EmailProviderInstance $provider): RedirectResponse
    {
        $validated = $request->validate([
            'test_recipient' => ['required', 'email'],
        ]);

        $result = $this->registry->testSend($provider->id, $validated['test_recipient']);

        if ($result['success']) {
            return redirect()->route('admin.notifications.email_providers.index')
                ->with('success', $result['message']);
        }

        return redirect()->route('admin.notifications.email_providers.index')
            ->with('error', $result['message']);
    }

    /**
     * Delete a provider instance with safety guard against removing the only enabled provider.
     */
    public function destroy(EmailProviderInstance $provider): RedirectResponse
    {
        if ($provider->is_enabled) {
            $otherEnabledCount = EmailProviderInstance::where('is_enabled', true)
                ->where('id', '!=', $provider->id)
                ->count();

            if ($otherEnabledCount === 0) {
                throw ValidationException::withMessages([
                    'provider' => 'Cannot delete the only enabled email provider instance. The system must always maintain at least one enabled provider.',
                ]);
            }
        }

        $label = $provider->label;
        $provider->delete();

        return redirect()->route('admin.notifications.email_providers.index')
            ->with('success', "Email provider [{$label}] was deleted.");
    }

    /**
     * Build config array based on driver type.
     */
    protected function extractConfig(string $driverType, array $input, array $existing = []): array
    {
        if ($driverType === 'smtp') {
            return [
                'host' => !empty($input['smtp_host']) ? $input['smtp_host'] : ($existing['host'] ?? '127.0.0.1'),
                'port' => !empty($input['smtp_port']) ? (int) $input['smtp_port'] : ($existing['port'] ?? 587),
                'encryption' => !empty($input['smtp_encryption']) ? $input['smtp_encryption'] : ($existing['encryption'] ?? 'tls'),
                'username' => array_key_exists('smtp_username', $input) && $input['smtp_username'] !== null ? $input['smtp_username'] : ($existing['username'] ?? ''),
                'password' => !empty($input['smtp_password']) ? $input['smtp_password'] : ($existing['password'] ?? ''),
                'from_address' => !empty($input['from_address']) ? $input['from_address'] : ($existing['from_address'] ?? 'notifications@nexgenhub.site'),
                'from_name' => !empty($input['from_name']) ? $input['from_name'] : ($existing['from_name'] ?? 'NBPDCL Billing Platform'),
            ];
        }

        // Resend or Brevo
        return [
            'api_key' => !empty($input['api_key']) ? $input['api_key'] : ($existing['api_key'] ?? ''),
            'from_address' => !empty($input['from_address']) ? $input['from_address'] : ($existing['from_address'] ?? 'notifications@nexgenhub.site'),
            'from_name' => !empty($input['from_name']) ? $input['from_name'] : ($existing['from_name'] ?? 'NBPDCL Billing Platform'),
        ];
    }
}
