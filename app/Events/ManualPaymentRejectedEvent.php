<?php

namespace App\Events;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ManualPaymentRejectedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Payment $payment,
        public User $admin,
        public string $rejectionReason
    ) {}
}
