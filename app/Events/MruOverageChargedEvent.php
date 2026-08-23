<?php

namespace App\Events;

use App\Models\Mru;
use App\Models\PlanOverageCharge;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MruOverageChargedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public Mru $mru,
        public float $amount,
        public PlanOverageCharge $charge
    ) {}
}
