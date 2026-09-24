<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Api\RateLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DocsController extends Controller
{
    /**
     * Display the Interactive Developer Portal & AI Agent Integration Suite.
     */
    public function api(Request $request): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        $activeKey = null;
        $userMrus = collect();
        $latestKeyId = 0;
        $latestKeyUpdated = 0;

        if ($user) {
            $latestKey = $user->apiKeys()
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->latest('id')
                ->first();

            // Masked prefix or session token if newly generated
            $activeKey = $latestKey ? $latestKey->key_prefix.'••••••••' : null;
            $latestKeyId = $latestKey?->id ?? 0;
            $latestKeyUpdated = $latestKey?->updated_at?->timestamp ?? 0;
            $userMrus = $user->mrus()->take(5)->get();
        }

        $baseUrl = url('/api/v1');
        $rateLimits = app(RateLimitService::class)->getLimits();

        $etag = '"'.md5(($user ? "u_{$user->id}_{$latestKeyId}_{$latestKeyUpdated}" : 'guest').'_'.json_encode($rateLimits)).'"';
        $clientEtag = $request->header('If-None-Match');

        if ($clientEtag && trim($clientEtag) === $etag) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => 'private, must-revalidate',
            ]);
        }

        $viewResponse = response()->view('docs.api', [
            'user' => $user,
            'activeKey' => $activeKey,
            'userMrus' => $userMrus,
            'baseUrl' => $baseUrl,
            'openapiUrl' => url('/api/v1/openapi.json'),
            'rateLimits' => $rateLimits,
        ]);

        $viewResponse->headers->set('ETag', $etag);
        $viewResponse->headers->set('Cache-Control', 'private, must-revalidate');

        return $viewResponse;
    }
}
