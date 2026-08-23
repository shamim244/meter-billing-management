<?php

namespace App\Events;

use App\Models\AgentSubscription;
use App\Models\Plan;
use App\Models\PlanUpgradeLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlanDowngradedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AgentSubscription $subscription,
        public Plan $fromPlan,
        public Plan $toPlan,
        public PlanUpgradeLog $log
    ) {}
}
