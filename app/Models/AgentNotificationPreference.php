<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentNotificationPreference extends Model
{
    use HasFactory, BelongsToUser;

    protected $table = 'agent_notification_preferences';

    protected $fillable = [
        'user_id',
        'event_category',
        'channel',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    /**
     * User owning this preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
