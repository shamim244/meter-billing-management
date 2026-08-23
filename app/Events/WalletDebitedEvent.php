<?php

namespace App\Events;

use App\Models\User;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletDebitedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public Transaction $transaction
    ) {}
}
