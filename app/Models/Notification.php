<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    use HasFactory, BelongsToUser;

    public $timestamps = false;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'event_type',
        'priority',
        'title',
        'body',
        'data',
        'read_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Recipient user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Delivery attempts across channels.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class, 'notification_id');
    }

    /**
     * Scope: Unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope: Critical notifications.
     */
    public function scopeCritical($query)
    {
        return $query->where('priority', 'critical');
    }

    /**
     * Scope: Routine notifications.
     */
    public function scopeRoutine($query)
    {
        return $query->where('priority', 'routine');
    }

    /**
     * Mark as read.
     */
    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }
}
