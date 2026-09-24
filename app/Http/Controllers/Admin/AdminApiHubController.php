<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\ApiRequestLog;
use App\Services\Api\ApiConfigurationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class AdminApiHubController extends Controller
{
    public function __construct(
        protected ApiConfigurationService $configService
    ) {}

    /**
     * Display the Centralized API & Automation Management Hub.
     */
    public function index(Request $request): View
    {
        $settings = $this->configService->getSettings();
        $defaults = ApiConfigurationService::DEFAULTS;

        // 1. Analytics & Request Traffic Metrics
        $totalRequests = ApiRequestLog::count();
        $requestsToday = ApiRequestLog::today()->count();
        $requestsThisWeek = ApiRequestLog::lastDays(7)->count();
        $requestsThisMonth = ApiRequestLog::thisMonth()->count();
        $avgLatencyMs = (int) round((float) (ApiRequestLog::today()->avg('duration_ms') ?: 0));

        // Status code breakdown
        $status2xx = ApiRequestLog::whereBetween('status_code', [200, 299])->count();
        $status4xx = ApiRequestLog::whereBetween('status_code', [400, 499])->where('status_code', '!=', 429)->count();
        $status429 = ApiRequestLog::where('status_code', 429)->count();
        $status5xx = ApiRequestLog::where('status_code', '>=', 500)->count();

        // Endpoint group distribution
        $groupCounts = ApiRequestLog::select('endpoint_group', DB::raw('count(*) as aggregate'))
            ->groupBy('endpoint_group')
            ->pluck('aggregate', 'endpoint_group')
            ->toArray();

        // Daily volume timeline for the past 14 days
        $dailyTimeline = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateString = $date->toDateString();
            $count = ApiRequestLog::whereDate('created_at', $dateString)->count();

            $dailyTimeline[] = [
                'date' => $dateString,
                'label' => $date->format('M d'),
                'count' => $count,
            ];
        }

        // Recent raw request stream
        $recentLogs = ApiRequestLog::with(['user', 'apiKey'])
            ->latest('id')
            ->take(12)
            ->get();

        // 2. All Issued Keys Ledger
        $keysQuery = ApiKey::with('user')->latest('id');

        if ($search = $request->query('q')) {
            $keysQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('key_prefix', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $issuedKeys = $keysQuery->paginate(15)->withQueryString();

        // Summary Statistics
        $stats = [
            'total_keys' => ApiKey::count(),
            'active_keys' => ApiKey::where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count(),
            'total_tokens' => (class_exists(PersonalAccessToken::class) && Schema::hasTable('personal_access_tokens'))
                ? PersonalAccessToken::count()
                : 0,
            'is_customized' => ($settings != $defaults),
        ];

        return view('admin.api-hub.index', compact(
            'settings',
            'defaults',
            'stats',
            'totalRequests',
            'requestsToday',
            'requestsThisWeek',
            'requestsThisMonth',
            'avgLatencyMs',
            'status2xx',
            'status4xx',
            'status429',
            'status5xx',
            'groupCounts',
            'dailyTimeline',
            'recentLogs',
            'issuedKeys'
        ));
    }

    /**
     * Update API configuration settings (Toggles, Policies, Rate limits).
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'api_master_enabled' => 'nullable|boolean',
            'user_keys_enabled' => 'nullable|boolean',
            'feature_automation_enabled' => 'nullable|boolean',
            'feature_mobile_sync_enabled' => 'nullable|boolean',
            'feature_batch_sync_enabled' => 'nullable|boolean',
            'feature_consumer_updates_enabled' => 'nullable|boolean',
            'public_docs_enabled' => 'nullable|boolean',

            'max_keys_per_user' => 'required|integer|min:1|max:100',
            'allow_permanent_keys' => 'nullable|boolean',
            'default_key_lifetime_days' => 'required|integer|min:1|max:3650',

            'rate_limiting_enabled' => 'nullable|boolean',
            'general_per_minute' => 'required|integer|min:1|max:60000',
            'review_per_minute' => 'required|integer|min:1|max:30000',
            'batch_per_minute' => 'required|integer|min:1|max:5000',
            'login_per_minute' => 'required|integer|min:1|max:1000',
            'openapi_per_minute' => 'required|integer|min:1|max:10000',
        ]);

        $payload = [
            'api_master_enabled' => $request->has('api_master_enabled'),
            'user_keys_enabled' => $request->has('user_keys_enabled'),
            'feature_automation_enabled' => $request->has('feature_automation_enabled'),
            'feature_mobile_sync_enabled' => $request->has('feature_mobile_sync_enabled'),
            'feature_batch_sync_enabled' => $request->has('feature_batch_sync_enabled'),
            'feature_consumer_updates_enabled' => $request->has('feature_consumer_updates_enabled'),
            'public_docs_enabled' => $request->has('public_docs_enabled'),

            'max_keys_per_user' => (int) $validated['max_keys_per_user'],
            'allow_permanent_keys' => $request->has('allow_permanent_keys'),
            'default_key_lifetime_days' => (int) $validated['default_key_lifetime_days'],

            'rate_limiting_enabled' => $request->has('rate_limiting_enabled'),
            'general_per_minute' => (int) $validated['general_per_minute'],
            'review_per_minute' => (int) $validated['review_per_minute'],
            'batch_per_minute' => (int) $validated['batch_per_minute'],
            'login_per_minute' => (int) $validated['login_per_minute'],
            'openapi_per_minute' => (int) $validated['openapi_per_minute'],
        ];

        $this->configService->updateSettings($payload);

        return redirect()
            ->route('admin.api_hub.index', ['tab' => $request->input('active_tab', 'settings')])
            ->with('status', 'API Hub configuration saved successfully.');
    }

    /**
     * Reset all configurations back to system defaults.
     */
    public function resetSettings(): RedirectResponse
    {
        $this->configService->resetToDefaults();

        return redirect()
            ->route('admin.api_hub.index')
            ->with('status', 'API Hub settings and rate limits restored to recommended factory defaults.');
    }

    /**
     * Admin emergency revoke for any user's API key.
     */
    public function revokeKey(ApiKey $apiKey): RedirectResponse
    {
        $keyName = $apiKey->name;
        $userName = $apiKey->user ? $apiKey->user->name : 'Unknown User';

        $apiKey->delete();

        return redirect()
            ->route('admin.api_hub.index', ['tab' => 'keys'])
            ->with('status', "API key '{$keyName}' ({$userName}) has been permanently revoked.");
    }

    /**
     * Purge historical API request analytics.
     */
    public function clearAnalytics(): RedirectResponse
    {
        ApiRequestLog::truncate();

        return redirect()
            ->route('admin.api_hub.index', ['tab' => 'analytics'])
            ->with('status', 'API analytics logs have been successfully cleared.');
    }
}
