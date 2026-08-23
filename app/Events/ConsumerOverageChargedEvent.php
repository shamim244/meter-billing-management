<?php

namespace App\Events;

use App\Models\BillingCycle;
use App\Models\PlanOverageCharge;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsumerOverageChargedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public BillingCycle $cycle,
        public int $extraConsumers,
        public float $amount,
        public PlanOverageCharge $charge
    ) {}
}
