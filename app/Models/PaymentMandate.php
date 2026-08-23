<?php

namespace App\Models;

use App\Enums\MandateStatus;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMandate extends Model
{
    use HasFactory, BelongsToUser;

    protected $table = 'payment_mandates';

    protected $fillable = [
        'user_id',
        'gateway_mandate_id',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MandateStatus::class,
        ];
    }

    /**
     * The billing agent / user associated with this recurring mandate.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for user relationship representing the billing agent.
     */
    public function agent(): BelongsTo
    {
        return $this->user();
    }
}
