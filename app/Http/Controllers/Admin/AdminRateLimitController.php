<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Services\Api\RateLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class AdminRateLimitController extends Controller
{
    public function __construct(
        protected RateLimitService $rateLimitService
    ) {}

    /**
     * Display the API rate limiting management console.
     */
    public function index(): View
    {
        $currentLimits = $this->rateLimitService->getLimits();
        $defaults = RateLimitService::DEFAULTS;

        $stats = [
            'total_api_keys' => ApiKey::count(),
            'active_api_keys' => ApiKey::where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count(),
            'total_tokens' => (class_exists(PersonalAccessToken::class) && Schema::hasTable('personal_access_tokens'))
                ? PersonalAccessToken::count()
                : 0,
            'is_customized' => ($currentLimits != $defaults),
        ];

        return view('admin.rate-limits.index', compact('currentLimits', 'defaults', 'stats'));
    }

    /**
     * Update the API rate limiting thresholds.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'general_per_minute' => 'required|integer|min:1|max:60000',
            'review_per_minute' => 'required|integer|min:1|max:30000',
            'batch_per_minute' => 'required|integer|min:1|max:5000',
            'login_per_minute' => 'required|integer|min:1|max:1000',
            'openapi_per_minute' => 'required|integer|min:1|max:10000',
        ]);

        $validated['enabled'] = $request->has('enabled');

        $this->rateLimitService->updateLimits($validated);

        return redirect()
            ->route('admin.rate_limits.index')
            ->with('status', 'API Rate Limiting configuration updated successfully.');
    }

    /**
     * Reset rate limiting settings back to default factory thresholds.
     */
    public function reset(): RedirectResponse
    {
        $this->rateLimitService->resetToDefaults();

        return redirect()
            ->route('admin.rate_limits.index')
            ->with('status', 'API Rate Limiting thresholds reset to recommended factory defaults.');
    }
}
