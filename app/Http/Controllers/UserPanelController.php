<?php

namespace App\Http\Controllers;

use App\Models\BillRecord;
use App\Models\SystemSetting;
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
        /** @var \App\Models\User $user */
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
        /** @var \App\Models\User $user */
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

        $plans = \App\Models\Plan::where('is_active', true)
            ->with(['durations' => function ($q) {
                $q->where('is_active', true)
                  ->orderBy('duration_unit', 'desc')
                  ->orderBy('duration_value');
            }])
            ->orderBy('id')
            ->get();

        $activeSubscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('billing_end', '>', now())
            ->latest('id')
            ->first();

        $walletBalance = (float) app(\App\Services\Wallet\WalletService::class)->getBalance($user);

        return view('user-panel.subscription', compact('user', 'stats', 'plans', 'activeSubscription', 'walletBalance'));
    }

    /**
     * Display the in-panel Keyboard Shortcuts configuration interface.
     */
    public function shortcuts(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $shortcuts = $user->getShortcutMap();
        $labels = $user->getShortcutLabels();
        $defaults = config('shortcuts.default', []);
        $isCustomized = !empty($user->shortcuts);

        return view('user-panel.shortcuts', compact('user', 'shortcuts', 'labels', 'defaults', 'isCustomized'));
    }

    /**
     * Display General & Workspace Preferences.
     */
    public function preferences(): View
    {
        /** @var \App\Models\User $user */
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
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return view('user-panel.profile', compact('user'));
    }

    /**
     * Update user profile information from User Panel.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
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

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('user-panel.profile')
            ->with('success', 'Password updated successfully!');
    }
}
