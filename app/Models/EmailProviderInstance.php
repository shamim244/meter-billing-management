<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected function casts(): array
    {
        return [
            'config' => 'encrypted:array',
            'priority' => 'integer',
            'is_enabled' => 'boolean',
            'last_used_at' => 'datetime',
            'last_failure_at' => 'datetime',
        ];
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
