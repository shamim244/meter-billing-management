<?php

namespace App\Services\Api;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class ApiConfigurationService
{
    public const CACHE_KEY = 'system_setting_api_configuration';

    public const DEFAULTS = [
        // 1. Master & Feature Toggles
        'api_master_enabled' => true,
        'user_keys_enabled' => true,
        'feature_automation_enabled' => true,
        'feature_mobile_sync_enabled' => true,
        'feature_batch_sync_enabled' => true,
        'feature_consumer_updates_enabled' => true,
        'public_docs_enabled' => true,

        // 2. Key Policies
        'max_keys_per_user' => 5,
        'allow_permanent_keys' => true,
        'default_key_lifetime_days' => 365,

        // 3. Rate Limiting Tiers
        'rate_limiting_enabled' => true,
        'general_per_minute' => 240,
        'review_per_minute' => 120,
        'batch_per_minute' => 30,
        'login_per_minute' => 15,
        'openapi_per_minute' => 60,
    ];

    /**
     * Retrieve all configured API settings with cached fallback to defaults.
     *
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            $saved = SystemSetting::get('api_configuration', []);

            if (! is_array($saved)) {
                $saved = [];
            }

            // Also check legacy api_rate_limits setting for seamless backward compatibility
            $legacyRateLimits = SystemSetting::get('api_rate_limits', []);
            if (is_array($legacyRateLimits) && ! empty($legacyRateLimits)) {
                $saved = array_merge([
                    'rate_limiting_enabled' => $legacyRateLimits['enabled'] ?? true,
                    'general_per_minute' => $legacyRateLimits['general_per_minute'] ?? 240,
                    'review_per_minute' => $legacyRateLimits['review_per_minute'] ?? 120,
                    'batch_per_minute' => $legacyRateLimits['batch_per_minute'] ?? 30,
                    'login_per_minute' => $legacyRateLimits['login_per_minute'] ?? 15,
                    'openapi_per_minute' => $legacyRateLimits['openapi_per_minute'] ?? 60,
                ], $saved);
            }

            return [
                'api_master_enabled' => (bool) ($saved['api_master_enabled'] ?? self::DEFAULTS['api_master_enabled']),
                'user_keys_enabled' => (bool) ($saved['user_keys_enabled'] ?? self::DEFAULTS['user_keys_enabled']),
                'feature_automation_enabled' => (bool) ($saved['feature_automation_enabled'] ?? self::DEFAULTS['feature_automation_enabled']),
                'feature_mobile_sync_enabled' => (bool) ($saved['feature_mobile_sync_enabled'] ?? self::DEFAULTS['feature_mobile_sync_enabled']),
                'feature_batch_sync_enabled' => (bool) ($saved['feature_batch_sync_enabled'] ?? self::DEFAULTS['feature_batch_sync_enabled']),
                'feature_consumer_updates_enabled' => (bool) ($saved['feature_consumer_updates_enabled'] ?? self::DEFAULTS['feature_consumer_updates_enabled']),
                'public_docs_enabled' => (bool) ($saved['public_docs_enabled'] ?? self::DEFAULTS['public_docs_enabled']),

                'max_keys_per_user' => max(1, (int) ($saved['max_keys_per_user'] ?? self::DEFAULTS['max_keys_per_user'])),
                'allow_permanent_keys' => (bool) ($saved['allow_permanent_keys'] ?? self::DEFAULTS['allow_permanent_keys']),
                'default_key_lifetime_days' => max(1, (int) ($saved['default_key_lifetime_days'] ?? self::DEFAULTS['default_key_lifetime_days'])),

                'rate_limiting_enabled' => (bool) ($saved['rate_limiting_enabled'] ?? self::DEFAULTS['rate_limiting_enabled']),
                'general_per_minute' => max(1, (int) ($saved['general_per_minute'] ?? self::DEFAULTS['general_per_minute'])),
                'review_per_minute' => max(1, (int) ($saved['review_per_minute'] ?? self::DEFAULTS['review_per_minute'])),
                'batch_per_minute' => max(1, (int) ($saved['batch_per_minute'] ?? self::DEFAULTS['batch_per_minute'])),
                'login_per_minute' => max(1, (int) ($saved['login_per_minute'] ?? self::DEFAULTS['login_per_minute'])),
                'openapi_per_minute' => max(1, (int) ($saved['openapi_per_minute'] ?? self::DEFAULTS['openapi_per_minute'])),
            ];
        });
    }

    /**
     * Check if a specific feature is enabled.
     */
    public function isFeatureEnabled(string $featureKey): bool
    {
        $settings = $this->getSettings();

        // If master API is disabled, all sub-features are disabled
        if (! ($settings['api_master_enabled'] ?? true)) {
            return false;
        }

        return (bool) ($settings[$featureKey] ?? true);
    }

    /**
     * Determine if rate limiting is globally active.
     */
    public function isRateLimitingEnabled(): bool
    {
        $settings = $this->getSettings();

        return (bool) ($settings['rate_limiting_enabled'] ?? true);
    }

    /**
     * Get a specific configuration value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->getSettings();

        return $settings[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    /**
     * Update configuration settings and flush memory cache.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateSettings(array $data): array
    {
        $current = $this->getSettings();

        $merged = [
            'api_master_enabled' => array_key_exists('api_master_enabled', $data) ? (bool) $data['api_master_enabled'] : $current['api_master_enabled'],
            'user_keys_enabled' => array_key_exists('user_keys_enabled', $data) ? (bool) $data['user_keys_enabled'] : $current['user_keys_enabled'],
            'feature_automation_enabled' => array_key_exists('feature_automation_enabled', $data) ? (bool) $data['feature_automation_enabled'] : $current['feature_automation_enabled'],
            'feature_mobile_sync_enabled' => array_key_exists('feature_mobile_sync_enabled', $data) ? (bool) $data['feature_mobile_sync_enabled'] : $current['feature_mobile_sync_enabled'],
            'feature_batch_sync_enabled' => array_key_exists('feature_batch_sync_enabled', $data) ? (bool) $data['feature_batch_sync_enabled'] : $current['feature_batch_sync_enabled'],
            'feature_consumer_updates_enabled' => array_key_exists('feature_consumer_updates_enabled', $data) ? (bool) $data['feature_consumer_updates_enabled'] : $current['feature_consumer_updates_enabled'],
            'public_docs_enabled' => array_key_exists('public_docs_enabled', $data) ? (bool) $data['public_docs_enabled'] : $current['public_docs_enabled'],

            'max_keys_per_user' => max(1, (int) ($data['max_keys_per_user'] ?? $current['max_keys_per_user'])),
            'allow_permanent_keys' => array_key_exists('allow_permanent_keys', $data) ? (bool) $data['allow_permanent_keys'] : $current['allow_permanent_keys'],
            'default_key_lifetime_days' => max(1, (int) ($data['default_key_lifetime_days'] ?? $current['default_key_lifetime_days'])),

            'rate_limiting_enabled' => array_key_exists('rate_limiting_enabled', $data) ? (bool) $data['rate_limiting_enabled'] : $current['rate_limiting_enabled'],
            'general_per_minute' => max(1, (int) ($data['general_per_minute'] ?? $current['general_per_minute'])),
            'review_per_minute' => max(1, (int) ($data['review_per_minute'] ?? $current['review_per_minute'])),
            'batch_per_minute' => max(1, (int) ($data['batch_per_minute'] ?? $current['batch_per_minute'])),
            'login_per_minute' => max(1, (int) ($data['login_per_minute'] ?? $current['login_per_minute'])),
            'openapi_per_minute' => max(1, (int) ($data['openapi_per_minute'] ?? $current['openapi_per_minute'])),
        ];

        SystemSetting::set('api_configuration', $merged);
        Cache::forget(self::CACHE_KEY);
        Cache::forget(RateLimitService::CACHE_KEY);

        return $merged;
    }

    /**
     * Reset all configurations back to system defaults.
     *
     * @return array<string, mixed>
     */
    public function resetToDefaults(): array
    {
        SystemSetting::set('api_configuration', self::DEFAULTS);
        Cache::forget(self::CACHE_KEY);
        Cache::forget(RateLimitService::CACHE_KEY);

        return self::DEFAULTS;
    }
}
