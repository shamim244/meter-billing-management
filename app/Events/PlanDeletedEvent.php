<?php

namespace App\Events;

use App\Models\Plan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlanDeletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Plan $plan,
        public bool $isForceDeleted = false
    ) {}
}
