<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RenewalAttempt extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'agent_subscription_id',
        'user_id',
        'attempt_type',
        'amount_charged',
        'wallet_transaction_id',
        'status',
        'failure_reason',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_charged' => 'decimal:2',
            'attempted_at' => 'datetime',
        ];
    }

    /**
     * The subscription this renewal attempt belongs to.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(AgentSubscription::class, 'agent_subscription_id');
    }
}
