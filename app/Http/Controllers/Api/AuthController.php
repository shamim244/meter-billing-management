<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Authenticate an agent or mobile device with email and password, returning an API token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'error' => 'InvalidCredentials',
                'message' => 'The provided email or password is incorrect.',
            ], 401);
        }

        if ($user->status === 'inactive' || $user->status === 'suspended') {
            return response()->json([
                'success' => false,
                'error' => 'AccountSuspended',
                'message' => 'Your account is currently inactive or suspended. Please contact administrator.',
            ], 403);
        }

        $deviceName = $request->input('device_name', 'Mobile App - '.now()->format('Y-m-d'));
        $tokenData = ApiKey::generate($user, $deviceName, ['*']);

        return response()->json([
            'success' => true,
            'message' => 'Authentication successful',
            'token' => $tokenData['plainTextToken'],
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'plan' => $user->getCurrentPlanName(),
                'status' => $user->status,
                'mru_count' => $user->mrus()->count(),
            ],
        ]);
    }

    /**
     * Get the authenticated agent's profile and active plan.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'plan' => $user->getCurrentPlanName(),
                'status' => $user->status,
                'shortcuts' => $user->getShortcutMap(),
                'stats' => [
                    'mrus' => $user->mrus()->count(),
                    'consumers' => $user->consumerAccounts()->count(),
                    'bills' => $user->billRecords()->count(),
                ],
            ],
        ]);
    }

    /**
     * Revoke the current API token.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('apiKey');

        if ($apiKey) {
            $apiKey->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Token revoked successfully',
        ]);
    }
}
