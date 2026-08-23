<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $table = 'notification_templates';

    protected $fillable = [
        'event_type',
        'channel',
        'subject',
        'body_template',
        'priority',
        'dispatch_mode',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope: Active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
