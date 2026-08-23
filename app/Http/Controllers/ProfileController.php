<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('admin');

        $stats = [
            'mru_count' => $isAdmin ? \App\Models\Mru::count() : \App\Models\Mru::where('user_id', $user->id)->count(),
            'consumer_count' => $isAdmin ? \App\Models\ConsumerAccount::count() : $user->consumerAccounts()->count(),
            'bills_count' => $isAdmin ? \App\Models\BillRecord::count() : \App\Models\BillRecord::where('user_id', $user->id)->count(),
            'role' => $user->getRoleNames()->first() ?? 'user',
            'created_at' => $user->created_at?->format('M d, Y') ?? 'N/A',
        ];

        return view('profile.edit', [
            'user' => $user,
            'stats' => $stats,
            'shortcuts' => $user->getShortcutMap(),
            'shortcutLabels' => $user->getShortcutLabels(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
