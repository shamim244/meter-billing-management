<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CouponCode;
use App\Models\ReferralPayout;
use App\Models\ReferralSignup;
use App\Models\User;
use App\Services\Referral\ReferralService;
use App\Services\Referral\ReferralSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminReferralController extends Controller
{
    public function __construct(
        protected ReferralSettingsService $settingsService,
        protected ReferralService $referralService
    ) {}

    /**
     * Display Platform-Wide Referral Settings Page.
     */
    public function settings(): View
    {
        $settings = $this->settingsService->getSettings();
        
        $totalReferrals = ReferralSignup::count();
        $totalPaidPayouts = ReferralPayout::where('status', 'paid')->sum('reward_amount');
        $pendingPayouts = ReferralPayout::where('status', 'pending')->sum('reward_amount');
        $activeReferrers = ReferralSignup::distinct('referrer_user_id')->count('referrer_user_id');

        return view('admin.referrals.settings', compact(
            'settings',
            'totalReferrals',
            'totalPaidPayouts',
            'pendingPayouts',
            'activeReferrers'
        ));
    }

    /**
     * Update Platform-Wide Referral Configuration Settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_enabled' => 'nullable|boolean',
            'reward_trigger' => 'required|in:subscription,topup',
            'reward_kind' => 'required|in:percentage,flat',
            'reward_value' => 'required|numeric|min:0',
            'minimum_qualifying_amount' => 'required|numeric|min:0',
            'hold_period_days' => 'required|integer|min:0|max:90',
            'referee_discount_kind' => 'nullable|in:percentage,flat',
            'referee_discount_value' => 'nullable|numeric|min:0',
        ]);

        $validated['is_enabled'] = $request->has('is_enabled');

        $this->settingsService->updateSettings($validated);

        return back()->with('success', 'Referral & Earn platform settings updated successfully.');
    }

    /**
     * Display Filterable Referral Activity Log.
     */
    public function activity(Request $request): View
    {
        $query = ReferralPayout::with(['referrer', 'referee', 'couponCode', 'walletTransaction'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('referrer_id')) {
            $query->where('referrer_user_id', $request->referrer_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('referrer', fn($r) => $r->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                  ->orWhereHas('referee', fn($r) => $r->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                  ->orWhereHas('couponCode', fn($c) => $c->where('code', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $payouts = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => ReferralPayout::count(),
            'pending' => ReferralPayout::where('status', 'pending')->count(),
            'paid' => ReferralPayout::where('status', 'paid')->count(),
            'cancelled' => ReferralPayout::where('status', 'cancelled')->count(),
            'clawed_back' => ReferralPayout::where('status', 'clawed_back')->count(),
            'total_disbursed' => (float) ReferralPayout::where('status', 'paid')->sum('reward_amount'),
        ];

        return view('admin.referrals.activity', compact('payouts', 'stats'));
    }

    /**
     * Display Top Referrers Leaderboard.
     */
    public function topReferrers(): View
    {
        $leaderboard = User::role('user')
            ->select('users.*')
            ->selectSub(function ($q) {
                $q->from('referral_signups')
                    ->whereColumn('referral_signups.referrer_user_id', 'users.id')
                    ->selectRaw('count(*)');
            }, 'total_signups')
            ->selectSub(function ($q) {
                $q->from('referral_payouts')
                    ->whereColumn('referral_payouts.referrer_user_id', 'users.id')
                    ->where('status', 'paid')
                    ->selectRaw('count(*)');
            }, 'paid_payouts_count')
            ->selectSub(function ($q) {
                $q->from('referral_payouts')
                    ->whereColumn('referral_payouts.referrer_user_id', 'users.id')
                    ->where('status', 'paid')
                    ->selectRaw('COALESCE(sum(reward_amount), 0)');
            }, 'total_earnings')
            ->selectSub(function ($q) {
                $q->from('referral_payouts')
                    ->whereColumn('referral_payouts.referrer_user_id', 'users.id')
                    ->where('status', 'pending')
                    ->selectRaw('COALESCE(sum(reward_amount), 0)');
            }, 'pending_earnings')
            ->having('total_signups', '>', 0)
            ->orderByDesc('paid_payouts_count')
            ->orderByDesc('total_earnings')
            ->paginate(20);

        return view('admin.referrals.top_referrers', compact('leaderboard'));
    }

    /**
     * Toggle active state of a specific Agent's referral code.
     */
    public function toggleCoupon(CouponCode $coupon): RedirectResponse
    {
        if ($coupon->type !== 'referral') {
            return back()->with('error', 'Only referral coupon codes can be toggled via this endpoint.');
        }

        $coupon->update(['is_active' => !$coupon->is_active]);

        $statusStr = $coupon->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Referral code '{$coupon->code}' has been {$statusStr}.");
    }

    /**
     * Update Admin Per-Agent Referral Reward Override.
     */
    public function updateAgentOverride(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'override_kind' => 'nullable|in:percentage,flat',
            'override_value' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $kind = $validated['override_kind'] ?? null;
        $value = $validated['override_value'] ?? null;
        $isActive = $request->has('is_active');

        $this->referralService->setAdminOverride($user, $kind, $value, $isActive);

        return back()->with('success', "Referral reward override updated for Agent '{$user->name}'.");
    }
}
