<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingCycle extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'mru_id',
        'user_id',
        'cycle_month',
        'cycle_year',
        'consumer_count_at_creation',
        'included_quota_used',
        'extra_consumer_count',
        'extra_consumer_charge',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cycle_month' => 'integer',
            'cycle_year' => 'integer',
            'consumer_count_at_creation' => 'integer',
            'included_quota_used' => 'integer',
            'extra_consumer_count' => 'integer',
            'extra_consumer_charge' => 'decimal:2',
        ];
    }

    /**
     * MRU this cycle belongs to.
     */
    public function mru(): BelongsTo
    {
        return $this->belongsTo(Mru::class);
    }
}
