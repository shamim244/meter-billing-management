<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'included_mrus',
        'included_consumers',
        'extra_mru_rate',
        'extra_consumer_rate',
        'grace_period_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'included_mrus' => 'integer',
            'included_consumers' => 'integer',
            'extra_mru_rate' => 'decimal:2',
            'extra_consumer_rate' => 'decimal:2',
            'grace_period_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Duration-based pricing entries for this plan.
     */
    public function durations(): HasMany
    {
        return $this->hasMany(PlanDuration::class);
    }

    /**
     * Active Duration-based pricing entries for this plan.
     */
    public function activeDurations(): HasMany
    {
        return $this->hasMany(PlanDuration::class)
            ->where('is_active', true)
            ->orderBy('duration_unit', 'desc')
            ->orderBy('duration_value');
    }

    /**
     * Agent subscriptions associated with this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(AgentSubscription::class);
    }

    /**
     * Active plan scope.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get base price (1-month duration final_price, or lowest duration price).
     */
    public function getBasePriceAttribute(): float
    {
        if ($this->relationLoaded('durations') || $this->durations()->exists()) {
            $monthly = $this->durations->firstWhere('duration_value', 1);
            if (!$monthly) {
                $monthly = $this->durations->firstWhere('duration_months', 1);
            }
            if ($monthly) {
                return (float) $monthly->final_price;
            }
            $minPrice = $this->durations->min('final_price');
            if ($minPrice !== null) {
                return (float) $minPrice;
            }
        }

        return 0.0;
    }
}
