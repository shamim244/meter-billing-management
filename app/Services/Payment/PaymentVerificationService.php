<?php

namespace App\Services\Payment;

use App\Enums\PaymentAuditAction;
use App\Enums\PaymentStatus;
use App\Events\ManualPaymentApprovedEvent;
use App\Events\ManualPaymentRejectedEvent;
use App\Events\PaymentSuccessEvent;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentVerificationService
{
    /**
     * Admin manually approves a pending manual payment.
     */
    public function approve(Payment $payment, User $admin, ?string $notes = null): Payment
    {
        if ($payment->status !== PaymentStatus::PENDING_VERIFICATION) {
            throw new \InvalidArgumentException("Payment cannot be approved from '{$payment->status->value}' status.");
        }

        return DB::transaction(function () use ($payment, $admin, $notes) {
            $payment->update([
                'status' => PaymentStatus::SUCCESS,
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);

            PaymentAuditLog::create([
                'payment_id' => $payment->id,
                'admin_id' => $admin->id,
                'action' => PaymentAuditAction::APPROVED,
                'notes' => $notes ?: 'Approved by admin after bank/UPI verification.',
                'created_at' => now(),
            ]);

            // Fire events: 1. Success event for wallet/subscription activation, 2. Admin approval event
            event(new PaymentSuccessEvent($payment));
            event(new ManualPaymentApprovedEvent($payment, $admin));

            return $payment->fresh();
        });
    }

    /**
     * Admin manually rejects a pending manual payment with mandatory rejection reason.
     */
    public function reject(Payment $payment, User $admin, string $rejectionReason, ?string $notes = null): Payment
    {
        if ($payment->status !== PaymentStatus::PENDING_VERIFICATION) {
            throw new \InvalidArgumentException("Payment cannot be rejected from '{$payment->status->value}' status.");
        }

        $cleanReason = trim($rejectionReason);
        if (empty($cleanReason)) {
            throw new \InvalidArgumentException('A rejection reason is mandatory when rejecting a payment.');
        }

        return DB::transaction(function () use ($payment, $admin, $cleanReason, $notes) {
            $payment->update([
                'status' => PaymentStatus::REJECTED,
                'verified_by' => $admin->id,
                'verified_at' => now(),
                'rejection_reason' => $cleanReason,
            ]);

            PaymentAuditLog::create([
                'payment_id' => $payment->id,
                'admin_id' => $admin->id,
                'action' => PaymentAuditAction::REJECTED,
                'notes' => $notes ?: "Rejected: {$cleanReason}",
                'created_at' => now(),
            ]);

            event(new ManualPaymentRejectedEvent($payment, $admin, $cleanReason));

            return $payment->fresh();
        });
    }

    /**
     * Admin manually logs a refund for a successful payment.
     */
    public function refund(Payment $payment, User $admin, string $reason): Payment
    {
        if ($payment->status !== PaymentStatus::SUCCESS) {
            throw new \InvalidArgumentException("Only successful payments can be refunded.");
        }

        $cleanReason = trim($reason);
        if (empty($cleanReason)) {
            throw new \InvalidArgumentException('A refund reason/notes is mandatory.');
        }

        return DB::transaction(function () use ($payment, $admin, $cleanReason) {
            PaymentAuditLog::create([
                'payment_id' => $payment->id,
                'admin_id' => $admin->id,
                'action' => PaymentAuditAction::REFUNDED,
                'notes' => $cleanReason,
                'created_at' => now(),
            ]);

            return $payment->fresh();
        });
    }
}
