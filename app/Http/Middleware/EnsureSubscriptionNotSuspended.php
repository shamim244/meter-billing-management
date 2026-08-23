<?php

namespace App\Http\Middleware;

use App\Models\AgentSubscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionNotSuspended
{
    /**
     * Handle an incoming request to verify that the Agent's subscription is not suspended.
     * PRD Section 2.1:
     * ACTIVE / RENEWAL_DUE / GRACE_PERIOD → full access, no blocking
     * SUSPENDED → Read-only. Past data/cycles viewable; write actions blocked.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Admins bypass subscription restrictions
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        /** @var AgentSubscription|null $subscription */
        $subscription = $user->subscriptions()->latest('id')->first();

        if ($subscription && $subscription->isSuspended()) {
            $routeName = $request->route()?->getName() ?? '';
            $method = strtoupper($request->method());

            // Always exempt renewal, wallet, payment, profile, and logout routes
            $exemptRoutePrefixes = [
                'user-panel.subscription',
                'payments.',
                'wallet.',
                'logout',
                'profile.',
            ];

            foreach ($exemptRoutePrefixes as $prefix) {
                if (str_starts_with($routeName, $prefix)) {
                    return $next($request);
                }
            }

            // Safe read-only HTTP methods are permitted for historical review
            if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
                return $next($request);
            }

            // Block all write actions (POST, PUT, PATCH, DELETE)
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'error' => 'subscription_suspended',
                    'message' => 'Your subscription is currently suspended (read-only mode) due to unpaid renewal. Please renew your subscription to create MRUs, cycles, or modify data.',
                    'suspended_at' => $subscription->suspended_at?->toIso8601String(),
                    'is_suspended' => true,
                ], 403);
            }

            return redirect()->route('user-panel.subscription')
                ->with('error', 'Your subscription is currently suspended in read-only mode. Please renew your subscription to perform write operations.');
        }

        return $next($request);
    }
}
