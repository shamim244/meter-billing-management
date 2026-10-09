<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    /**
     * In-memory runtime cache for the active request lifecycle.
     *
     * @var array<string, mixed>
     */
    protected static array $runtimeCache = [];

    protected static bool $isPreloaded = false;

    protected $fillable = [
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /**
     * Preload all system settings in a single database query.
     */
    public static function preloadAll(): void
    {
        if (static::$isPreloaded) {
            return;
        }

        try {
            $settings = static::all();
            foreach ($settings as $setting) {
                static::$runtimeCache[$setting->key] = $setting->value;
            }
            static::$isPreloaded = true;
        } catch (\Throwable $e) {
            // Table may not exist yet during migrations
        }
    }

    /**
     * Retrieve a setting by key with optional fallback default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$runtimeCache)) {
            return static::$runtimeCache[$key];
        }

        static::preloadAll();

        if (array_key_exists($key, static::$runtimeCache)) {
            return static::$runtimeCache[$key];
        }

        static::$runtimeCache[$key] = $default;

        return $default;
    }

    /**
     * Store or update a setting value by key.
     */
    public static function set(string $key, mixed $value): static
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        static::$runtimeCache[$key] = $value;
        Cache::forget("system_setting_{$key}");

        return $setting;
    }

    /**
     * Clear runtime in-memory cache.
     */
    public static function clearRuntimeCache(): void
    {
        static::$runtimeCache = [];
        static::$isPreloaded = false;
    }
}
