<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\BillRecord;
use App\Models\IssueReport;
use App\Models\Plan;
use App\Models\User;
use App\Services\Api\ApiConfigurationService;
use App\Services\Wallet\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserPanelController extends Controller
{
    /**
     * Display the User Panel Overview / Account Hub.
     */
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $stats = [
            'mru_count' => $user->mrus()->count(),
            'consumer_count' => $user->consumerAccounts()->count(),
            'bills_count' => BillRecord::where('user_id', $user->id)->count(),
            'created_at' => $user->created_at ? $user->created_at->format('M d, Y') : 'N/A',
            'storage_used_bytes' => $user->getStorageUsedBytes(),
            'storage_limit_bytes' => $user->getStorageLimitBytes(),
            'storage_percent' => $user->getStorageUsagePercent(),
            'pdf_count' => $user->getPdfCount(),
        ];

        $shortcuts = $user->getShortcutMap();
        $shortcutLabels = $user->getShortcutLabels();

        return view('user-panel.index', compact('user', 'stats', 'shortcuts', 'shortcutLabels'));
    }

    /**
     * Display the Subscription & Storage Quota page.
     */
    public function subscription(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $stats = [
            'storage_used_bytes' => $user->getStorageUsedBytes(),
            'storage_limit_bytes' => $user->getStorageLimitBytes(),
            'storage_percent' => $user->getStorageUsagePercent(),
            'pdf_count' => $user->getPdfCount(),
            'mru_count' => $user->mrus()->count(),
            'consumer_count' => $user->consumerAccounts()->count(),
            'bills_count' => BillRecord::where('user_id', $user->id)->count(),
        ];

        $plans = Plan::where('is_active', true)
            ->with(['durations' => function ($q) {
                $q->where('is_active', true)
                    ->orderBy('duration_unit', 'desc')
                    ->orderBy('duration_value');
            }])
            ->orderBy('id')
            ->get();

        $activeSubscription = $user->activeSubscription;

        $subscriptionHistory = $user->subscriptions()
            ->with('plan')
            ->orderByDesc('id')
            ->take(10)
            ->get();

        $walletBalance = (float) app(WalletService::class)->getBalance($user);

        return view('user-panel.subscription', compact('user', 'stats', 'plans', 'activeSubscription', 'subscriptionHistory', 'walletBalance'));
    }

    /**
     * Display the in-panel Keyboard Shortcuts configuration interface.
     */
    public function shortcuts(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $shortcuts = $user->getShortcutMap();
        $labels = $user->getShortcutLabels();
        $defaults = config('shortcuts.default', []);
        $isCustomized = ! empty($user->shortcuts);

        return view('user-panel.shortcuts', compact('user', 'shortcuts', 'labels', 'defaults', 'isCustomized'));
    }

    /**
     * Display General & Workspace Preferences.
     */
    public function preferences(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $preferences = [
            'default_view' => session('pref_default_view', 'card'),
            'default_page_size' => session('pref_page_size', 50),
            'auto_fill_suggestion' => session('pref_auto_fill', true),
            'sound_feedback' => session('pref_sound', true),
            'theme' => session('pref_theme', 'system'),
            'card_density' => session('pref_card_density', 'compact'),
            'amount_size' => session('pref_amount_size', 'standard'),
            'show_remark_presets' => session('pref_remark_presets', false),
        ];

        return view('user-panel.preferences', compact('user', 'preferences'));
    }

    /**
     * Save updated General Preferences.
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_view' => 'required|in:card,table',
            'default_page_size' => 'required|integer|in:25,50,100',
            'auto_fill_suggestion' => 'nullable|boolean',
            'sound_feedback' => 'nullable|boolean',
            'theme' => 'required|in:light,dark,system',
            'card_density' => 'required|in:compact,comfortable',
            'amount_size' => 'required|in:standard,large',
            'show_remark_presets' => 'nullable|boolean',
        ]);

        session([
            'pref_default_view' => $validated['default_view'],
            'pref_page_size' => (int) $validated['default_page_size'],
            'pref_auto_fill' => (bool) ($validated['auto_fill_suggestion'] ?? false),
            'pref_sound' => (bool) ($validated['sound_feedback'] ?? false),
            'pref_theme' => $validated['theme'],
            'pref_card_density' => $validated['card_density'],
            'pref_amount_size' => $validated['amount_size'],
            'pref_remark_presets' => (bool) ($validated['show_remark_presets'] ?? false),
        ]);

        return redirect()->route('user-panel.preferences')
            ->with('success', 'Workspace preferences saved successfully!');
    }

    /**
     * Display Profile & Security settings.
     */
    public function profile(): View
    {
        /** @var User $user */
        $user = Auth::user();

        return view('user-panel.profile', compact('user'));
    }

    /**
     * Update user profile information from User Panel.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('user-panel.profile')
            ->with('success', 'Profile information updated successfully!');
    }

    /**
     * Update user password from User Panel.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('user-panel.profile')
            ->with('success', 'Password updated successfully!');
    }

    /**
     * Display User's Bug Reports and Support Ticket Center.
     */
    public function issues(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        $status = $request->get('status', 'all');
        $search = trim($request->get('q', ''));

        $query = IssueReport::with(['mru'])
            ->where('user_id', $user->id);

        if (! empty($search)) {
            $escaped = addcslashes($search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('issue_code', 'like', "%{$escaped}%")
                    ->orWhere('title', 'like', "%{$escaped}%")
                    ->orWhere('ca_number', 'like', "%{$escaped}%");
            });
        }

        if ($status === 'active') {
            $query->whereIn('status', ['pending', 'verified', 'in_progress']);
        } elseif ($status === 'resolved') {
            $query->where('status', 'resolved');
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $issues = $query->latest('id')->paginate(15)->withQueryString();

        $stats = [
            'total' => IssueReport::where('user_id', $user->id)->count(),
            'active' => IssueReport::where('user_id', $user->id)->whereIn('status', ['pending', 'verified', 'in_progress'])->count(),
            'resolved' => IssueReport::where('user_id', $user->id)->where('status', 'resolved')->count(),
        ];

        return view('user-panel.issues', compact('user', 'issues', 'stats', 'status', 'search'));
    }

    /**
     * Display the API Keys & Field Automation Tokens management interface.
     */
    public function apiKeys(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        $apiKeys = $user->apiKeys()
            ->orderByDesc('created_at')
            ->get();

        $configService = app(ApiConfigurationService::class);

        $stats = [
            'total' => $apiKeys->count(),
            'active' => $apiKeys->filter(fn ($k) => ! $k->expires_at || $k->expires_at->isFuture())->count(),
            'expired' => $apiKeys->filter(fn ($k) => $k->expires_at && $k->expires_at->isPast())->count(),
            'last_used' => $apiKeys->whereNotNull('last_used_at')->sortByDesc('last_used_at')->first(),
            'max_allowed' => (int) $configService->get('max_keys_per_user', 5),
            'user_keys_enabled' => $configService->isFeatureEnabled('user_keys_enabled'),
            'allow_permanent' => (bool) $configService->get('allow_permanent_keys', true),
        ];

        return view('user-panel.api-keys', compact('user', 'apiKeys', 'stats'));
    }

    /**
     * Generate a new secure API key with customizable duration and permissions.
     */
    public function storeApiKey(Request $request): RedirectResponse
    {
        $configService = app(ApiConfigurationService::class);

        // 1. Check if user key generation is enabled
        if (! $configService->isFeatureEnabled('user_keys_enabled')) {
            return redirect()->route('user-panel.api-keys')
                ->with('error', 'API key generation is currently disabled by administrator.');
        }

        /** @var User $user */
        $user = Auth::user();

        // 2. Check max active keys quota
        $maxKeys = (int) $configService->get('max_keys_per_user', 5);
        $activeCount = $user->apiKeys()
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count();

        if ($activeCount >= $maxKeys) {
            return redirect()->route('user-panel.api-keys')
                ->with('error', "You have reached the maximum allowed active API keys ({$maxKeys}). Please revoke an existing key first.");
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'duration' => 'required|string|in:1_day,7_days,30_days,90_days,365_days,never',
            'abilities' => 'nullable|array',
        ]);

        // 3. Check permanent key policy
        if ($validated['duration'] === 'never' && ! (bool) $configService->get('allow_permanent_keys', true)) {
            return redirect()->route('user-panel.api-keys')
                ->with('error', 'Permanent non-expiring API keys are restricted by administrator policy. Please select an expiration period.');
        }

        $expiresAt = match ($validated['duration']) {
            '1_day' => now()->addDay(),
            '7_days' => now()->addDays(7),
            '30_days' => now()->addDays(30),
            '90_days' => now()->addDays(90),
            '365_days' => now()->addYear(),
            'never' => null,
            default => now()->addDays(30),
        };

        $abilities = $request->filled('abilities') ? $validated['abilities'] : ['*'];

        $result = ApiKey::generate($user, trim($validated['name']), $abilities, $expiresAt);

        return redirect()->route('user-panel.api-keys')
            ->with('new_api_key', [
                'plain_text_token' => $result['plainTextToken'],
                'name' => $result['apiKey']->name,
                'key_prefix' => $result['apiKey']->key_prefix,
                'expires_at' => $result['apiKey']->expires_at ? $result['apiKey']->expires_at->format('M d, Y h:i A') : 'Never Expires',
            ])
            ->with('success', 'Secret API key generated successfully! Make sure to copy it now.');
    }

    /**
     * Revoke (permanently delete) an API key.
     */
    public function revokeApiKey(Request $request, int $id): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $apiKey = $user->apiKeys()->where('id', $id)->first();

        if (! $apiKey) {
            return redirect()->route('user-panel.api-keys')
                ->with('error', 'API Key not found or does not belong to your account.');
        }

        $keyName = $apiKey->name;
        $apiKey->delete();

        return redirect()->route('user-panel.api-keys')
            ->with('success', "API Key '{$keyName}' has been revoked successfully and can no longer be used.");
    }
}
