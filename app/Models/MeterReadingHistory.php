<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeterReadingHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mru_id',
        'consumer_id',
        'bill_record_id',
        'ca_number',
        'billing_month',
        'billing_year',
        'previous_reading',
        'current_reading',
        'working_reading',
        'units_consumed',
        'billing_basis',
        'reading_source',
        'is_closed',
        'meta',
    ];

    protected $casts = [
        'billing_month' => 'integer',
        'billing_year' => 'integer',
        'units_consumed' => 'integer',
        'is_closed' => 'boolean',
        'meta' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mru(): BelongsTo
    {
        return $this->belongsTo(Mru::class);
    }

    public function consumerAccount(): BelongsTo
    {
        return $this->belongsTo(ConsumerAccount::class, 'consumer_id');
    }

    public function billRecord(): BelongsTo
    {
        return $this->belongsTo(BillRecord::class);
    }

    // Scopes
    public function scopePdf($query)
    {
        return $query->where('reading_source', 'pdf');
    }

    public function scopeWorking($query)
    {
        return $query->where('reading_source', 'working');
    }

    public function scopeClosed($query)
    {
        return $query->where('is_closed', true);
    }

    public function scopePeriod($query, int $month, int $year)
    {
        return $query->where('billing_month', $month)->where('billing_year', $year);
    }

    // Accessors & Helpers
    public function getMonthLabel(): string
    {
        return date('M, Y', mktime(0, 0, 0, $this->billing_month, 1, $this->billing_year));
    }

    public function getShortMonthLabel(): string
    {
        return date('M', mktime(0, 0, 0, $this->billing_month, 1, $this->billing_year));
    }

    public function getEffectiveReading(): ?int
    {
        if ($this->working_reading !== null && is_numeric($this->working_reading)) {
            return (int) $this->working_reading;
        }

        if ($this->current_reading !== null && is_numeric($this->current_reading)) {
            return (int) $this->current_reading;
        }

        return null;
    }

    public function getEffectiveUnits(): int
    {
        if ($this->units_consumed !== null && $this->units_consumed > 0) {
            return (int) $this->units_consumed;
        }

        $eff = $this->getEffectiveReading();
        $prev = is_numeric($this->previous_reading) ? (int) $this->previous_reading : null;

        if ($eff !== null && $prev !== null && $eff >= $prev) {
            return $eff - $prev;
        }

        return 0;
    }
}
