<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class EmailProviderInstance extends Model
{
    use HasFactory;

    protected $table = 'email_provider_instances';

    protected $fillable = [
        'driver_type',
        'label',
        'config',
        'priority',
        'is_enabled',
        'last_used_at',
        'last_failure_at',
        'last_failure_reason',
    ];

    protected bool $configDecryptionFailed = false;

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_enabled' => 'boolean',
            'last_used_at' => 'datetime',
            'last_failure_at' => 'datetime',
        ];
    }

    /**
     * Determine if configuration decryption failed (e.g. following APP_KEY rotation).
     */
    public function isConfigDecryptionFailed(): bool
    {
        return $this->configDecryptionFailed;
    }

    /**
     * Resilient getter for encrypted config.
     * Prevents fatal 500 crashes if APP_KEY changed or MAC is invalid.
     */
    public function getConfigAttribute($value): array
    {
        if (empty($value)) {
            return [];
        }

        try {
            $decrypted = decrypt($value);

            if (is_array($decrypted)) {
                return $decrypted;
            }

            if (is_string($decrypted)) {
                $decoded = json_decode($decrypted, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }

            return [];
        } catch (DecryptException $e) {
            $this->configDecryptionFailed = true;

            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            Log::warning("EmailProviderInstance #{$this->id} ({$this->label}) config decryption failed (APP_KEY changed or invalid MAC). Returning empty config array.");

            return [];
        } catch (\Throwable $e) {
            $this->configDecryptionFailed = true;

            return [];
        }
    }

    /**
     * Setter for config using Laravel's standard encrypter.
     */
    public function setConfigAttribute($value): void
    {
        if (is_null($value)) {
            $this->attributes['config'] = null;
        } else {
            $this->attributes['config'] = encrypt(is_array($value) ? $value : (json_decode($value, true) ?: $value));
            $this->configDecryptionFailed = false;
        }
    }

    /**
     * Deliveries sent via this provider instance.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class, 'email_provider_instance_id');
    }

    /**
     * Scope: Enabled providers in priority order.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true)->orderBy('priority', 'asc');
    }
}
