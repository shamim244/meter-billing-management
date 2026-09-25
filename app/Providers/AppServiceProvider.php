<?php

namespace App\Providers;

use App\Models\ApiKey;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Api\RateLimitService;
use App\Services\Notifications\EmailProviderRegistryService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestTerminated;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EmailProviderRegistryService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Octane Request Lifecycle Guard: Flush runtime in-memory caches between requests
        if (class_exists(RequestTerminated::class)) {
            Event::listen(RequestTerminated::class, function () {
                SystemSetting::clearRuntimeCache();
            });
        }

        // Authorization Gate for Laravel Pulse Monitoring Dashboard
        Gate::define('viewPulse', function (?User $user = null): bool {
            return (bool) ($user?->hasRole('admin'));
        });

        // 1. General API Rate Limiter (Reads, lookups, queue)
        RateLimiter::for('api.general', function (Request $request) {
            $service = app(RateLimitService::class);
            if (! $service->isEnabled()) {
                return Limit::none();
            }

            $key = $this->resolveRateLimitKey($request);
            $limit = $service->getLimit('general_per_minute');

            return Limit::perMinute($limit)->by($key)->response(function (Request $request, array $headers) use ($limit) {
                return response()->json([
                    'success' => false,
                    'error' => 'RateLimitExceeded',
                    'message' => "Rate limit exceeded (max {$limit} requests/minute). To protect server stability, please pause for a few seconds.",
                    'retry_after_seconds' => (int) ($headers['Retry-After'] ?? 60),
                ], 429, $headers);
            });
        });

        // 2. Review Submissions Rate Limiter (Ledger updates & reviews)
        RateLimiter::for('api.review', function (Request $request) {
            $service = app(RateLimitService::class);
            if (! $service->isEnabled()) {
                return Limit::none();
            }

            $key = $this->resolveRateLimitKey($request);
            $limit = $service->getLimit('review_per_minute');

            return Limit::perMinute($limit)->by($key)->response(function (Request $request, array $headers) use ($limit) {
                return response()->json([
                    'success' => false,
                    'error' => 'RateLimitExceeded',
                    'message' => "Review submission rate limit reached (max {$limit} reviews/minute). Please slow down your automation interval.",
                    'retry_after_seconds' => (int) ($headers['Retry-After'] ?? 30),
                ], 429, $headers);
            });
        });

        // 3. Batch Offline Sync Rate Limiter (Batch payload pushes)
        RateLimiter::for('api.batch', function (Request $request) {
            $service = app(RateLimitService::class);
            if (! $service->isEnabled()) {
                return Limit::none();
            }

            $key = $this->resolveRateLimitKey($request);
            $limit = $service->getLimit('batch_per_minute');

            return Limit::perMinute($limit)->by($key)->response(function (Request $request, array $headers) use ($limit) {
                return response()->json([
                    'success' => false,
                    'error' => 'RateLimitExceeded',
                    'message' => "Batch sync rate limit reached (max {$limit} batch pushes/minute).",
                    'retry_after_seconds' => (int) ($headers['Retry-After'] ?? 30),
                ], 429, $headers);
            });
        });

        // 4. Mobile / Token Login Brute-Force Protection
        RateLimiter::for('api.login', function (Request $request) {
            $service = app(RateLimitService::class);
            if (! $service->isEnabled()) {
                return Limit::none();
            }

            $key = $request->ip();
            $limit = $service->getLimit('login_per_minute');

            return Limit::perMinute($limit)->by($key)->response(function (Request $request, array $headers) use ($limit) {
                return response()->json([
                    'success' => false,
                    'error' => 'RateLimitExceeded',
                    'message' => "Too many login attempts (max {$limit} attempts/minute). Please wait before trying again.",
                    'retry_after_seconds' => (int) ($headers['Retry-After'] ?? 60),
                ], 429, $headers);
            });
        });

        // 5. Machine-Readable OpenAPI Schema Limiter
        RateLimiter::for('api.openapi', function (Request $request) {
            $service = app(RateLimitService::class);
            if (! $service->isEnabled()) {
                return Limit::none();
            }

            $key = $request->ip();
            $limit = $service->getLimit('openapi_per_minute');

            return Limit::perMinute($limit)->by($key)->response(function (Request $request, array $headers) use ($limit) {
                return response()->json([
                    'success' => false,
                    'error' => 'RateLimitExceeded',
                    'message' => "OpenAPI schema rate limit reached (max {$limit} requests/minute).",
                    'retry_after_seconds' => (int) ($headers['Retry-After'] ?? 60),
                ], 429, $headers);
            });
        });
    }

    /**
     * Resolve unique rate limit identifier per device / API key / user.
     * Prevents multiple field workers on the same Wi-Fi/hotspot from sharing quotas.
     */
    protected function resolveRateLimitKey(Request $request): string
    {
        // 1. Direct API Key header or Bearer token (consistent before or after auth middleware)
        $rawKey = $request->header('X-API-Key') ?: $request->bearerToken() ?: $request->query('api_key');
        if ($rawKey) {
            return 'key_'.hash('sha256', trim($rawKey));
        }

        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('apiKey');
        if ($apiKey) {
            return 'apikey_'.$apiKey->id;
        }

        if ($user = $request->user()) {
            return 'user_'.$user->id;
        }

        return 'ip_'.$request->ip();
    }
}
