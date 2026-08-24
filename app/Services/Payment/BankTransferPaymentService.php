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

class BankTransferPaymentService
{
    public function __construct(
        protected PaymentSettingsService $settings
    ) {}

    /**
     * Submit a Bank Transfer (NEFT/IMPS) payment for admin verification.
     */
    public function submitPayment(
        User $user,
        float $amount,
        PaymentPurpose $purpose,
        string $bankReference,
        ?UploadedFile $screenshot = null,
        array $meta = []
    ): Payment {
        if (!$this->settings->isModeEnabled(PaymentMode::BANK_TRANSFER)) {
            throw new \InvalidArgumentException('Bank Transfer payment mode is currently disabled.');
        }

        $minAmount = $this->settings->getMinAmount();
        if ($amount < $minAmount) {
            throw new \InvalidArgumentException("Payment amount must be at least ₹{$minAmount}.");
        }

        $cleanRef = trim($bankReference);
        if (empty($cleanRef)) {
            throw new \InvalidArgumentException('Transaction reference number is mandatory for bank transfers.');
        }

        // Check for duplicate pending reference
        $existingDuplicate = Payment::where('bank_reference', $cleanRef)
            ->whereIn('status', [PaymentStatus::PENDING_VERIFICATION->value, PaymentStatus::SUCCESS->value])
            ->first();

        if ($existingDuplicate) {
            throw new \InvalidArgumentException('A payment with this bank reference number has already been submitted.');
        }

        $screenshotUrl = null;
        if ($screenshot && $screenshot->isValid()) {
            $path = $screenshot->store("payments/proofs/bank/{$user->id}", 'public');
            $screenshotUrl = Storage::disk('public')->url($path);
        }

        $payment = Payment::create([
            'user_id' => $user->id,
            'mode' => PaymentMode::BANK_TRANSFER,
            'purpose' => $purpose,
            'amount' => $amount,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING_VERIFICATION,
            'bank_reference' => $cleanRef,
            'screenshot_url' => $screenshotUrl,
            'meta' => $meta,
        ]);

        event(new ManualPaymentSubmittedEvent($payment));

        return $payment;
    }
}
