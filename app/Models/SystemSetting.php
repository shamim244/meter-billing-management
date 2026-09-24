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
     * Retrieve a setting by key with optional fallback default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$runtimeCache)) {
            return static::$runtimeCache[$key];
        }

        try {
            $value = Cache::remember("system_setting_{$key}", 3600, function () use ($key) {
                $setting = static::where('key', $key)->first();

                return $setting ? $setting->value : null;
            });

            $result = $value !== null ? $value : $default;
            static::$runtimeCache[$key] = $result;

            return $result;
        } catch (\Throwable $e) {
            return $default;
        }
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
    }
}
