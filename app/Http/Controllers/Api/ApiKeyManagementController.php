<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiKeyManagementController extends Controller
{
    /**
     * List all API keys issued for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $keys = $user->apiKeys()
            ->select(['id', 'name', 'key_prefix', 'abilities', 'last_used_at', 'last_ip', 'expires_at', 'created_at'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'api_keys' => $keys,
        ]);
    }

    /**
     * Generate a new API key.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'abilities' => 'nullable|array',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        /** @var User $user */
        $user = $request->user();

        $expiresAt = $request->filled('expires_in_days')
            ? now()->addDays((int) $request->input('expires_in_days'))
            : null;

        $abilities = $request->input('abilities', ['*']);

        $result = ApiKey::generate($user, $request->input('name'), $abilities, $expiresAt);

        return response()->json([
            'success' => true,
            'message' => 'API key generated successfully.',
            'api_key' => [
                'id' => $result['apiKey']->id,
                'name' => $result['apiKey']->name,
                'key_prefix' => $result['apiKey']->key_prefix,
                'abilities' => $result['apiKey']->abilities,
                'expires_at' => $result['apiKey']->expires_at,
                'created_at' => $result['apiKey']->created_at,
            ],
            'plain_text_key' => $result['plainTextToken'],
            'warning' => 'Make sure to copy your API key now. For security reasons, it will never be displayed again.',
        ], 201);
    }

    /**
     * Revoke an API key.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $apiKey = $user->apiKeys()->where('id', $id)->first();

        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key not found or does not belong to your account.',
            ], 404);
        }

        $apiKey->delete();

        return response()->json([
            'success' => true,
            'message' => "API key '{$apiKey->name}' has been revoked successfully.",
        ]);
    }
}
