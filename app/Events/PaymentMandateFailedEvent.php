<?php

namespace App\Events;

use App\Models\PaymentMandate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentMandateFailedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PaymentMandate $mandate,
        public string $failureReason = 'Auto-debit mandate execution failed'
    ) {}
}
