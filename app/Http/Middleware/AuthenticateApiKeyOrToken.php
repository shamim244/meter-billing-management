<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKeyOrToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $rawKey = $this->extractKey($request);

        if (! $rawKey) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated',
                'message' => "A valid API key must be provided via the 'X-API-Key' header or 'Authorization: Bearer <key>' header.",
            ], 401);
        }

        $apiKey = ApiKey::findAndValidate($rawKey);

        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'InvalidApiKey',
                'message' => 'The provided API key is invalid, revoked, or has expired.',
            ], 401);
        }

        if ($ability && ! $apiKey->can($ability)) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => "This API key does not have the required ability: '{$ability}'.",
            ], 403);
        }

        // Authenticate the user for the current request lifecycle
        Auth::setUser($apiKey->user);
        $request->setUserResolver(fn () => $apiKey->user);
        $request->attributes->set('apiKey', $apiKey);

        // Record usage asynchronously / quietly
        $apiKey->recordUsage($request->ip());

        return $next($request);
    }

    /**
     * Extract key from X-API-Key, Authorization header, or query param.
     */
    protected function extractKey(Request $request): ?string
    {
        // 1. Check X-API-Key header
        if ($key = $request->header('X-API-Key')) {
            return trim($key);
        }

        // 2. Check Authorization: Bearer <key>
        if ($bearer = $request->bearerToken()) {
            return trim($bearer);
        }

        // 3. Fallback query parameter
        if ($key = $request->query('api_key')) {
            return trim($key);
        }

        return null;
    }
}
