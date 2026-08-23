<?php

namespace App\Events;

use App\Models\AgentSubscription;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionReactivatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AgentSubscription $subscription,
        public ?string $reason = null,
        public ?User $admin = null
    ) {}
}
