<?php

namespace App\Services;

use App\Models\BillRecord;
use App\Models\BillingBasisHistory;
use App\Models\BillingCycle;
use App\Models\ConsumerAccount;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BillingBasisTrackingService
{
    /**
     * Estimated billing basis codes that trigger consecutive tracking.
     */
    public const ESTIMATE_BASES = ['LK', 'MD'];

    /**
     * Record billing basis for a consumer cycle and compute consecutive estimate alert.
     */
    public function recordBillingBasis(
        int $userId,
        string $caNumber,
        ?int $mruId,
        int $month,
        int $year,
        string $basis,
        ?int $consumerId = null,
        ?int $billingCycleId = null
    ): BillingBasisHistory {
        $cleanBasis = strtoupper(trim($basis ?: 'OK'));

        // Resolve consumer_id if missing
        if (!$consumerId) {
            $consumer = ConsumerAccount::where('user_id', $userId)
                ->where('ca_number', $caNumber)
                ->first();
            $consumerId = $consumer?->id;
            if (!$mruId && $consumer) {
                $mruId = $consumer->mru_id;
            }
        }

        // Resolve billing_cycle_id if missing
        if (!$billingCycleId && $mruId) {
            $cycle = BillingCycle::where('user_id', $userId)
                ->where('mru_id', $mruId)
                ->where('cycle_month', $month)
                ->where('cycle_year', $year)
                ->first();
            $billingCycleId = $cycle?->id;
        }

        // Calculate consecutive count and alert
        $stat = $this->calculateConsecutiveCount($userId, $caNumber, $month, $year, $cleanBasis);

        return BillingBasisHistory::updateOrCreate(
            [
                'user_id' => $userId,
                'ca_number' => $caNumber,
                'billing_month' => $month,
                'billing_year' => $year,
            ],
            [
                'mru_id' => $mruId,
                'consumer_id' => $consumerId,
                'billing_cycle_id' => $billingCycleId,
                'billing_basis' => $cleanBasis,
                'is_consecutive_alert' => $stat['is_alert'],
                'consecutive_count' => $stat['count'],
            ]
        );
    }

    /**
     * Calculate consecutive estimate count and alert status.
     * Walks history in reverse chronological order and resets to 0 on first 'OK'.
     *
     * @return array{count: int, is_alert: bool}
     */
    public function calculateConsecutiveCount(
        int $userId,
        string $caNumber,
        int $currentMonth,
        int $currentYear,
        string $currentBasis
    ): array {
        $cleanBasis = strtoupper(trim($currentBasis ?: 'OK'));

        // If current basis is NOT an estimate (e.g. 'OK', 'PL', 'RN'), count resets to 0
        if (!in_array($cleanBasis, self::ESTIMATE_BASES, true)) {
            return [
                'count' => 0,
                'is_alert' => false,
            ];
        }

        // Current basis is LK or MD -> initial count is 1
        $count = 1;

        // Query prior cycles in reverse chronological order
        $priorEntries = BillingBasisHistory::where('user_id', $userId)
            ->where('ca_number', $caNumber)
            ->where(function ($q) use ($currentYear, $currentMonth) {
                $q->where('billing_year', '<', $currentYear)
                  ->orWhere(function ($sub) use ($currentYear, $currentMonth) {
                      $sub->where('billing_year', '=', $currentYear)
                          ->where('billing_month', '<', $currentMonth);
                  });
            })
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get();

        foreach ($priorEntries as $entry) {
            $priorBasis = strtoupper(trim($entry->billing_basis ?: 'OK'));
            if (in_array($priorBasis, self::ESTIMATE_BASES, true)) {
                $count++;
            } else {
                // Encountered 'OK' or non-estimate, stop walking
                break;
            }
        }

        return [
            'count' => $count,
            'is_alert' => ($count >= 2),
        ];
    }

    /**
     * Helper to record directly from a BillRecord model.
     */
    public function recordFromBillRecord(BillRecord $bill): BillingBasisHistory
    {
        return $this->recordBillingBasis(
            userId: $bill->user_id,
            caNumber: $bill->ca_number,
            mruId: $bill->mru_id,
            month: (int) $bill->billing_month,
            year: (int) $bill->billing_year,
            basis: $bill->billing_basis ?: 'OK'
        );
    }

    /**
     * Get flagged consumers with consecutive estimate alert.
     */
    public function getFlaggedConsumers(
        int $userId,
        ?int $mruId = null,
        ?int $month = null,
        ?int $year = null
    ): Collection {
        $query = BillingBasisHistory::with(['mru', 'consumerAccount'])
            ->where('user_id', $userId)
            ->where('is_consecutive_alert', true);

        if ($mruId) {
            $query->where('mru_id', $mruId);
        }

        if ($month && $year) {
            $query->where('billing_month', $month)->where('billing_year', $year);
        } else {
            // Default to newest cycle entries
            $latest = BillingBasisHistory::where('user_id', $userId)->max('created_at');
            if ($latest) {
                $query->where('created_at', '>=', now()->subDays(60));
            }
        }

        return $query->orderBy('consecutive_count', 'desc')->get();
    }

    /**
     * Get count of flagged consumers.
     */
    public function getFlaggedConsumerCount(int $userId, ?int $month = null, ?int $year = null): int
    {
        $query = BillingBasisHistory::where('user_id', $userId)
            ->where('is_consecutive_alert', true);

        if ($month && $year) {
            $query->where('billing_month', $month)->where('billing_year', $year);
        }

        return $query->count();
    }
}
