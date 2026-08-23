<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanOverageCharge extends Model
{
    use HasFactory, BelongsToUser;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'charge_type',
        'reference_type',
        'reference_id',
        'amount',
        'wallet_transaction_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }
}
