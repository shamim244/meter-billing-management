<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanDuration extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'duration_months',
        'discount_percent',
        'final_price',
        'extra_mru_rate',
        'extra_consumer_rate',
    ];

    protected function casts(): array
    {
        return [
            'duration_months' => 'integer',
            'discount_percent' => 'decimal:2',
            'final_price' => 'decimal:2',
            'extra_mru_rate' => 'decimal:2',
            'extra_consumer_rate' => 'decimal:2',
        ];
    }

    /**
     * Parent Plan relationship.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
