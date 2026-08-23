<?php

namespace App\Models;

use App\Enums\PaymentAuditAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAuditLog extends Model
{
    use HasFactory;

    protected $table = 'payment_audit_log';

    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'admin_id',
        'action',
        'notes',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => PaymentAuditAction::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * The payment this audit log entry belongs to.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    /**
     * The admin user who performed the audited action.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
