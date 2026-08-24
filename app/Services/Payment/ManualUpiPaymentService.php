<?php

namespace App\Services\Payment;

use App\Enums\PaymentMode;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Events\ManualPaymentSubmittedEvent;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ManualUpiPaymentService
{
    public function __construct(
        protected PaymentSettingsService $settings
    ) {}

    /**
     * Submit a Manual UPI payment for admin verification.
     */
    public function submitPayment(
        User $user,
        float $amount,
        PaymentPurpose $purpose,
        string $utrNumber,
        ?UploadedFile $screenshot = null,
        array $meta = []
    ): Payment {
        if (!$this->settings->isModeEnabled(PaymentMode::MANUAL_UPI)) {
            throw new \InvalidArgumentException('Manual UPI payment mode is currently disabled.');
        }

        $minAmount = $this->settings->getMinAmount();
        if ($amount < $minAmount) {
            throw new \InvalidArgumentException("Payment amount must be at least ₹{$minAmount}.");
        }

        $cleanUtr = trim($utrNumber);
        if (empty($cleanUtr)) {
            throw new \InvalidArgumentException('UTR number is mandatory for manual UPI payments.');
        }

        // Check for duplicate pending UTR to prevent accidental double-submission
        $existingDuplicate = Payment::where('utr_number', $cleanUtr)
            ->whereIn('status', [PaymentStatus::PENDING_VERIFICATION->value, PaymentStatus::SUCCESS->value])
            ->first();

        if ($existingDuplicate) {
            throw new \InvalidArgumentException('A payment with this UTR number has already been submitted.');
        }

        $screenshotUrl = null;
        if ($screenshot && $screenshot->isValid()) {
            $path = $screenshot->store("payments/proofs/upi/{$user->id}", 'public');
            $screenshotUrl = Storage::disk('public')->url($path);
        }

        $payment = Payment::create([
            'user_id' => $user->id,
            'mode' => PaymentMode::MANUAL_UPI,
            'purpose' => $purpose,
            'amount' => $amount,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING_VERIFICATION,
            'utr_number' => $cleanUtr,
            'screenshot_url' => $screenshotUrl,
            'meta' => $meta,
        ]);

        event(new ManualPaymentSubmittedEvent($payment));

        return $payment;
    }
}
