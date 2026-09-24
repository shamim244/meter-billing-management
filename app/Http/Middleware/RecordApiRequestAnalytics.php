<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\ApiRequestLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordApiRequestAnalytics
{
    /**
     * Handle an incoming request and mark start time.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('request_start_time', microtime(true));

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser/client.
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            $startTime = (float) $request->attributes->get('request_start_time', microtime(true));
            $durationMs = (int) max(1, round((microtime(true) - $startTime) * 1000));

            /** @var ApiKey|null $apiKey */
            $apiKey = $request->attributes->get('apiKey');
            $user = $request->user() ?: $apiKey?->user;

            $path = '/'.ltrim($request->path(), '/');
            $endpointGroup = $this->determineEndpointGroup($path, $request->method());

            ApiRequestLog::create([
                'api_key_id' => $apiKey?->id,
                'user_id' => $user?->id,
                'endpoint_group' => $endpointGroup,
                'method' => substr($request->method(), 0, 10),
                'path' => substr($path, 0, 255),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $durationMs,
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Passive telemetry failure must never throw or affect callers
        }
    }

    /**
     * Classify API endpoint path into high-level analytical categories.
     */
    protected function determineEndpointGroup(string $path, string $method): string
    {
        if (str_contains($path, '/automation')) {
            return 'automation';
        }

        if (str_contains($path, '/sync')) {
            return 'sync';
        }

        if (str_contains($path, '/batch-sync')) {
            return 'batch';
        }

        if (str_contains($path, '/bills/review')) {
            return 'reviews';
        }

        if (str_contains($path, '/auth/')) {
            return 'auth';
        }

        if (str_contains($path, '/openapi.json')) {
            return 'docs';
        }

        return 'reads';
    }
}
