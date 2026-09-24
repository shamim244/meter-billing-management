<?php

namespace App\Services\Api;

class RateLimitService
{
    public const CACHE_KEY = ApiConfigurationService::CACHE_KEY;

    public const DEFAULTS = [
        'enabled' => true,
        'general_per_minute' => 240,
        'review_per_minute' => 120,
        'batch_per_minute' => 30,
        'login_per_minute' => 15,
        'openapi_per_minute' => 60,
    ];

    public function __construct(
        protected ?ApiConfigurationService $configService = null
    ) {
        $this->configService = $configService ?: app(ApiConfigurationService::class);
    }

    /**
     * Retrieve all configured API rate limits with cached fallback to defaults.
     *
     * @return array{
     *     enabled: bool,
     *     general_per_minute: int,
     *     review_per_minute: int,
     *     batch_per_minute: int,
     *     login_per_minute: int,
     *     openapi_per_minute: int
     * }
     */
    public function getLimits(): array
    {
        $settings = $this->configService->getSettings();

        return [
            'enabled' => (bool) $settings['rate_limiting_enabled'],
            'general_per_minute' => (int) $settings['general_per_minute'],
            'review_per_minute' => (int) $settings['review_per_minute'],
            'batch_per_minute' => (int) $settings['batch_per_minute'],
            'login_per_minute' => (int) $settings['login_per_minute'],
            'openapi_per_minute' => (int) $settings['openapi_per_minute'],
        ];
    }

    /**
     * Get a single rate limit threshold by key.
     */
    public function getLimit(string $key): int
    {
        $limits = $this->getLimits();

        return $limits[$key] ?? (self::DEFAULTS[$key] ?? 60);
    }

    /**
     * Determine if API rate limiting is globally active.
     */
    public function isEnabled(): bool
    {
        return $this->configService->isRateLimitingEnabled();
    }

    /**
     * Save new rate limit configurations and clear cache.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateLimits(array $data): array
    {
        $payload = [
            'rate_limiting_enabled' => $data['enabled'] ?? ($data['rate_limiting_enabled'] ?? true),
            'general_per_minute' => $data['general_per_minute'] ?? 240,
            'review_per_minute' => $data['review_per_minute'] ?? 120,
            'batch_per_minute' => $data['batch_per_minute'] ?? 30,
            'login_per_minute' => $data['login_per_minute'] ?? 15,
            'openapi_per_minute' => $data['openapi_per_minute'] ?? 60,
        ];

        $res = $this->configService->updateSettings($payload);

        return [
            'enabled' => $res['rate_limiting_enabled'],
            'general_per_minute' => $res['general_per_minute'],
            'review_per_minute' => $res['review_per_minute'],
            'batch_per_minute' => $res['batch_per_minute'],
            'login_per_minute' => $res['login_per_minute'],
            'openapi_per_minute' => $res['openapi_per_minute'],
        ];
    }

    /**
     * Reset rate limit settings back to system defaults.
     *
     * @return array<string, mixed>
     */
    public function resetToDefaults(): array
    {
        $this->configService->resetToDefaults();

        return self::DEFAULTS;
    }
}
