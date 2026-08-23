<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentSubscription extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'plan_id',
        'duration_months',
        'base_price_paid',
        'included_mrus_locked',
        'included_consumers_locked',
        'extra_mru_rate_locked',
        'extra_consumer_rate_locked',
        'billing_start',
        'billing_end',
        'status',
        'lifecycle_status',
        'grace_period_days',
        'grace_period_ends_at',
        'auto_renewal_enabled',
        'suspended_at',
        'last_state_change_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_months' => 'integer',
            'base_price_paid' => 'decimal:2',
            'included_mrus_locked' => 'integer',
            'included_consumers_locked' => 'integer',
            'extra_mru_rate_locked' => 'decimal:2',
            'extra_consumer_rate_locked' => 'decimal:2',
            'billing_start' => 'datetime',
            'billing_end' => 'datetime',
            'grace_period_days' => 'integer',
            'grace_period_ends_at' => 'datetime',
            'auto_renewal_enabled' => 'boolean',
            'suspended_at' => 'datetime',
            'last_state_change_at' => 'datetime',
        ];
    }

    /**
     * Plan for this subscription (for reference only).
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Renewal attempts history for this subscription.
     */
    public function renewalAttempts(): HasMany
    {
        return $this->hasMany(RenewalAttempt::class)->orderByDesc('id');
    }

    /**
     * Plan upgrade/downgrade proration logs.
     */
    public function planUpgradeLogs(): HasMany
    {
        return $this->hasMany(PlanUpgradeLog::class)->orderByDesc('id');
    }

    /**
     * Check if subscription is currently active.
     */
    public function isActive(): bool
    {
        return ($this->lifecycle_status === 'active' || $this->status === 'active')
            && $this->billing_end > now()
            && !$this->isSuspended();
    }

    /**
     * Check if subscription has expired.
     */
    public function isExpired(): bool
    {
        return $this->billing_end <= now();
    }

    /**
     * Check if subscription is in RENEWAL_DUE state.
     */
    public function isRenewalDue(): bool
    {
        return $this->lifecycle_status === 'renewal_due';
    }

    /**
     * Check if subscription is in GRACE_PERIOD state.
     */
    public function isInGracePeriod(): bool
    {
        return $this->lifecycle_status === 'grace_period';
    }

    /**
     * Check if subscription is SUSPENDED (read-only mode).
     */
    public function isSuspended(): bool
    {
        return $this->lifecycle_status === 'suspended' || $this->suspended_at !== null;
    }

    /**
     * Check if the Agent is permitted to perform write actions (non-suspended).
     * PRD: ACTIVE, RENEWAL_DUE, GRACE_PERIOD allow full access; SUSPENDED blocks write.
     */
    public function canWrite(): bool
    {
        return !$this->isSuspended();
    }
}
