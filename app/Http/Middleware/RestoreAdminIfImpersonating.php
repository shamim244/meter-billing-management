<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RestoreAdminIfImpersonating
{
    /**
     * Handle an incoming request.
     * If an admin is currently impersonating a user and attempts to navigate to any /admin/* route,
     * seamlessly restore the original administrator session and proceed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('impersonated_by')) {
            $adminId = session()->pull('impersonated_by');
            $admin = User::find($adminId);

            if ($admin && $admin->hasRole('admin')) {
                Auth::login($admin);
                session()->flash('success', 'Exited impersonation and returned to Administrator account.');
            }
        }

        return $next($request);
    }
}
