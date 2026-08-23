<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request and verify the user is active.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->status !== 'active') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Your account has been suspended. Please contact administrator.',
                    ], 403);
                }

                return redirect()->route('login')->withErrors([
                    'email' => 'Your account has been suspended. Please contact administrator.',
                ]);
            }
        }

        return $next($request);
    }
}
