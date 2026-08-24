<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanDuration extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'duration_unit',
        'duration_value',
        'name',
        'duration_months',
        'discount_percent',
        'final_price',
        'extra_mru_rate',
        'extra_consumer_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'duration_value' => 'integer',
            'duration_months' => 'integer',
            'discount_percent' => 'decimal:2',
            'final_price' => 'decimal:2',
            'extra_mru_rate' => 'decimal:2',
            'extra_consumer_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Parent Plan relationship.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Human-readable formatted duration string (e.g. "7 Days", "1 Month", "3 Months").
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->name) {
            return $this->name;
        }

        $val = $this->duration_value ?: $this->duration_months ?: 1;
        $unit = $this->duration_unit === 'day' ? 'Day' : 'Month';

        return "{$val} {$unit}" . ($val > 1 ? 's' : '');
    }

    /**
     * Short pill badge label (e.g. "7d", "1m", "3m -10%").
     */
    public function getShortLabelAttribute(): string
    {
        $val = $this->duration_value ?: $this->duration_months ?: 1;
        $unit = $this->duration_unit === 'day' ? 'd' : 'm';
        $label = "{$val}{$unit}";

        if ($this->discount_percent > 0) {
            $label .= ' -' . rtrim(rtrim(number_format((float)$this->discount_percent, 2), '0'), '.') . '%';
        }

        return $label;
    }

    /**
     * Calculate new billing end timestamp from a start timestamp.
     */
    public function calculateBillingEnd(?Carbon $startDate = null): Carbon
    {
        $start = ($startDate ?? now())->copy();
        $val = $this->duration_value ?: $this->duration_months ?: 1;

        if ($this->duration_unit === 'day') {
            return $start->addDays($val);
        }

        return $start->addMonths($val);
    }

    public function isDayBased(): bool
    {
        return $this->duration_unit === 'day';
    }

    public function isMonthBased(): bool
    {
        return $this->duration_unit !== 'day';
    }
}
