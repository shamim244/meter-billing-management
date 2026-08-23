<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mru extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'full_identifier',
        'status', // 'active' | 'locked'
        'locked_reason',
        'locked_at',
        'unlocked_at',
        'is_over_quota',
    ];

    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
            'unlocked_at' => 'datetime',
            'is_over_quota' => 'boolean',
        ];
    }

    /**
     * Get all consumer accounts in this MRU master list.
     */
    public function consumerAccounts(): HasMany
    {
        return $this->hasMany(ConsumerAccount::class);
    }

    /**
     * Get all bill records belonging to this MRU.
     */
    public function billRecords(): HasMany
    {
        return $this->hasMany(BillRecord::class);
    }

    /**
     * Billing cycles generated for this MRU.
     */
    public function billingCycles(): HasMany
    {
        return $this->hasMany(BillingCycle::class);
    }

    /**
     * Get distinct billing periods (months & years) processed for this MRU.
     */
    public function billingPeriods()
    {
        return $this->billRecords()
            ->select('billing_month', 'billing_year')
            ->distinct()
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get();
    }

    /**
     * Check if MRU is currently locked.
     */
    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    /**
     * Check if MRU is flagged as over-quota.
     */
    public function isOverQuota(): bool
    {
        return (bool) $this->is_over_quota;
    }

    /**
     * Scope: only active MRUs.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: only locked MRUs.
     */
    public function scopeLocked($query)
    {
        return $query->where('status', 'locked');
    }
}
