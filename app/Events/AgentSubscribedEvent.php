<?php

namespace App\Events;

use App\Models\AgentSubscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentSubscribedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AgentSubscription $subscription
    ) {}
}
