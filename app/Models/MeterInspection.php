<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeterInspection extends Model
{
    use BelongsToUser, HasFactory;

    protected $table = 'meter_inspections';

    protected $fillable = [
        'user_id',
        'consumer_account_id',
        'ca_number',
        'physical_reading',
        'posted_reading_at_inspection',
        'gap_at_inspection',
        'photo_path',
        'notes',
        'inspected_at',
    ];

    protected $casts = [
        'gap_at_inspection' => 'integer',
        'inspected_at' => 'datetime',
    ];

    /**
     * Get consumer account associated with this inspection.
     */
    public function consumerAccount(): BelongsTo
    {
        return $this->belongsTo(ConsumerAccount::class, 'consumer_account_id');
    }

    /**
     * Scope to find latest inspection for a given CA number.
     */
    public function scopeLatestForCa($query, string $caNumber)
    {
        return $query->where('ca_number', $caNumber)->orderByDesc('inspected_at');
    }
}
