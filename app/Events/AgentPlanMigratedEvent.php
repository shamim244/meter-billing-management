<?php

namespace App\Events;

use App\Models\AgentSubscription;
use App\Models\Plan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentPlanMigratedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AgentSubscription $subscription,
        public ?Plan $oldPlan = null
    ) {}
}
