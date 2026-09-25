<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppDiscoveryController extends Controller
{
    /**
     * Mobile App Endpoint Discovery & Migration Handshake.
     * Allows Flutter apps in the field to automatically discover new server URLs
     * and check maintenance status without requiring an app store update.
     */
    public function config(Request $request): JsonResponse
    {
        $currentBaseUrl = config('app.url', $request->getSchemeAndHttpHost());
        $fallbackUrls = array_filter(array_map('trim', explode(',', (string) env('APP_FALLBACK_URLS', ''))));

        $minVersion = SystemSetting::get('mobile_min_app_version', '1.0.0');
        $latestVersion = SystemSetting::get('mobile_latest_app_version', '1.0.0');
        $maintenance = app()->isDownForMaintenance();

        $supportedCompression = ['gzip'];
        if (function_exists('zstd_compress')) {
            array_unshift($supportedCompression, 'zstd');
        }
        if (function_exists('brotli_compress')) {
            array_splice($supportedCompression, 1, 0, 'brotli');
        }

        return response()->json([
            'success' => true,
            'app_name' => config('app.name', 'NBPDCL Meter Billing'),
            'api_version' => 'v1',
            'active_server_url' => $currentBaseUrl,
            'api_base_url' => rtrim($currentBaseUrl, '/').'/api/v1',
            'fallback_server_urls' => $fallbackUrls,
            'min_app_version' => $minVersion,
            'latest_app_version' => $latestVersion,
            'maintenance_mode' => $maintenance,
            'qr_connect_code' => rtrim($currentBaseUrl, '/').'/api/v1',
            'compression_supported' => $supportedCompression,
            'timestamp' => now()->timestamp,
        ], 200, [
            'X-Server-Endpoint' => rtrim($currentBaseUrl, '/').'/api/v1',
            'Cache-Control' => 'no-cache, private',
        ]);
    }
}
