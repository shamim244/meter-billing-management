<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldDeskActivity extends Model
{
    use BelongsToUser, HasFactory;

    protected $table = 'field_desk_activities';

    public $timestamps = false;

    protected $fillable = [
        'action_id',
        'user_id',
        'action_type',
        'note',
        'old_date',
        'new_date',
        'amount_recorded',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'old_date' => 'date',
        'new_date' => 'date',
        'amount_recorded' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Boot model to set created_at timestamp if not provided.
     */
    protected static function booted(): void
    {
        static::creating(function ($activity) {
            if (empty($activity->created_at)) {
                $activity->created_at = now();
            }
        });
    }

    /**
     * Parent action this activity belongs to.
     */
    public function action(): BelongsTo
    {
        return $this->belongsTo(FieldDeskAction::class, 'action_id');
    }

    /**
     * User who logged or triggered this activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
