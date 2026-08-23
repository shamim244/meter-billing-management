<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingBasisHistory extends Model
{
    use HasFactory, BelongsToUser;

    protected $table = 'billing_basis_history';

    protected $fillable = [
        'user_id',
        'mru_id',
        'consumer_id',
        'ca_number',
        'billing_cycle_id',
        'billing_month',
        'billing_year',
        'billing_basis',
        'is_consecutive_alert',
        'consecutive_count',
    ];

    protected function casts(): array
    {
        return [
            'billing_month' => 'integer',
            'billing_year' => 'integer',
            'is_consecutive_alert' => 'boolean',
            'consecutive_count' => 'integer',
        ];
    }

    /**
     * MRU this entry belongs to.
     */
    public function mru(): BelongsTo
    {
        return $this->belongsTo(Mru::class);
    }

    /**
     * ConsumerAccount this entry belongs to.
     */
    public function consumerAccount(): BelongsTo
    {
        return $this->belongsTo(ConsumerAccount::class, 'consumer_id');
    }

    /**
     * BillingCycle this entry belongs to.
     */
    public function billingCycle(): BelongsTo
    {
        return $this->belongsTo(BillingCycle::class);
    }

    /**
     * Scope: Filter by period.
     */
    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->where('billing_month', $month)->where('billing_year', $year);
    }

    /**
     * Scope: Active alerts only.
     */
    public function scopeAlertsOnly($query)
    {
        return $query->where('is_consecutive_alert', true);
    }
}
