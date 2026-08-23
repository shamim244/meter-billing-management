<?php

namespace App\Models;

use App\Enums\PaymentMode;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory, BelongsToUser;

    protected $table = 'payments';

    protected $fillable = [
        'user_id',
        'mode',
        'purpose',
        'amount',
        'currency',
        'status',
        'gateway_order_id',
        'gateway_payment_id',
        'utr_number',
        'screenshot_url',
        'bank_reference',
        'verified_by',
        'verified_at',
        'rejection_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => PaymentMode::class,
            'purpose' => PaymentPurpose::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * The billing agent / user who initiated this payment.
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

    /**
     * Backward-compatible alias for user relationship.
     */
    public function tenant(): BelongsTo
    {
        return $this->user();
    }

    /**
     * The admin who manually verified/rejected this payment.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Audit logs for admin actions on this payment.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(PaymentAuditLog::class, 'payment_id')->latest('id');
    }

    /**
     * Scope to only payments pending manual admin verification.
     */
    public function scopePendingVerification(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::PENDING_VERIFICATION->value);
    }

    /**
     * Scope to successful payments.
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::SUCCESS->value);
    }

    /**
     * Scope to manual payment modes (Manual UPI / Bank Transfer).
     */
    public function scopeManualModes(Builder $query): Builder
    {
        return $query->whereIn('mode', [PaymentMode::MANUAL_UPI->value, PaymentMode::BANK_TRANSFER->value]);
    }
}
