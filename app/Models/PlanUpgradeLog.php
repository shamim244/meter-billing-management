<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanUpgradeLog extends Model
{
    use HasFactory, BelongsToUser;

    protected $table = 'plan_upgrade_log';

    protected $fillable = [
        'agent_subscription_id',
        'user_id',
        'from_plan_id',
        'to_plan_id',
        'action_type',
        'old_plan_credit',
        'new_plan_cost',
        'amount_charged',
        'wallet_transaction_id',
        'days_remaining_at_upgrade',
        'total_days_in_cycle',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'old_plan_credit' => 'decimal:2',
            'new_plan_cost' => 'decimal:2',
            'amount_charged' => 'decimal:2',
            'days_remaining_at_upgrade' => 'integer',
            'total_days_in_cycle' => 'integer',
        ];
    }

    /**
     * The subscription this log entry belongs to.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(AgentSubscription::class, 'agent_subscription_id');
    }

    /**
     * The plan being upgraded/downgraded from.
     */
    public function fromPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'from_plan_id')->withTrashed();
    }

    /**
     * The plan being upgraded/downgraded to.
     */
    public function toPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'to_plan_id')->withTrashed();
    }
}
