<?php

namespace App\Http\Controllers;

use App\Services\Referral\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AgentReferralController extends Controller
{
    public function __construct(
        protected ReferralService $referralService
    ) {}

    /**
     * Display the Agent's "My Referrals" Dashboard.
     */
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $stats = $this->referralService->getAgentReferralStats($user);

        return view('user-panel.referrals', compact('stats'));
    }

    /**
     * Regenerate the Agent's referral code.
     */
    public function regenerate(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $newCoupon = $this->referralService->regenerateCode($user);

        return back()->with('success', "✨ Your new referral code is '{$newCoupon->code}'. The previous link will no longer accept new signups (existing pending payouts remain protected).");
    }
}
