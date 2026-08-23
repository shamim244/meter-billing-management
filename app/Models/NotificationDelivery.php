<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDelivery extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'notification_deliveries';

    protected $fillable = [
        'notification_id',
        'channel',
        'email_provider_instance_id',
        'status',
        'attempt_count',
        'last_attempted_at',
        'failed_reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_count' => 'integer',
            'last_attempted_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Parent notification.
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Email provider instance that performed the delivery.
     */
    public function emailProviderInstance(): BelongsTo
    {
        return $this->belongsTo(EmailProviderInstance::class, 'email_provider_instance_id');
    }
}
