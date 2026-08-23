<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillStatus extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'ca_number',
        'billing_month',
        'billing_year',
        'status',
        'remark',
        'tag',
    ];

    protected $casts = [
        'billing_month' => 'integer',
        'billing_year' => 'integer',
    ];

    /**
     * Get the consumer account associated with this bill status.
     */
    public function consumerAccount(): BelongsTo
    {
        return $this->belongsTo(ConsumerAccount::class, 'ca_number', 'ca_number')
                    ->where('consumer_accounts.user_id', $this->user_id);
    }

    /**
     * Scope: filter by billing period.
     */
    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->where('billing_month', $month)->where('billing_year', $year);
    }

    /**
     * Scope: filter by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
