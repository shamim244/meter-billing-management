<?php

namespace App\Http\Middleware;

use App\Services\Api\ApiConfigurationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceApiFeatureToggles
{
    public function __construct(
        protected ApiConfigurationService $configService
    ) {}

    /**
     * Handle an incoming request and check if master API or specific feature is enabled.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $feature = null): Response
    {
        // 1. Master API switch check
        if (! $this->configService->isFeatureEnabled('api_master_enabled')) {
            return response()->json([
                'success' => false,
                'error' => 'ApiDisabled',
                'message' => 'The REST API is temporarily disabled by system administrator.',
            ], 503);
        }

        // 2. Specific feature check if requested
        if ($feature) {
            $featureMap = [
                'automation' => 'feature_automation_enabled',
                'mobile_sync' => 'feature_mobile_sync_enabled',
                'batch_sync' => 'feature_batch_sync_enabled',
                'consumer_updates' => 'feature_consumer_updates_enabled',
                'docs' => 'public_docs_enabled',
            ];

            $configKey = $featureMap[$feature] ?? $feature;

            if (! $this->configService->isFeatureEnabled($configKey)) {
                return response()->json([
                    'success' => false,
                    'error' => 'FeatureDisabled',
                    'message' => "The '{$feature}' API capability is temporarily disabled by system administrator.",
                ], 503);
            }
        }

        return $next($request);
    }
}
