<?php

namespace App\Services\Reading;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\MeterInspection;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Carbon;

class ReadingGapReconciliationService
{
    /**
     * Record a new physical meter inspection, update consumer cache, and log audit trail.
     */
    public function recordPhysicalInspection(
        ConsumerAccount $consumer,
        string|float|int $reading,
        ?Carbon $date = null,
        ?string $notes = null,
        ?User $user = null
    ): MeterInspection {
        $readingStr = (string) $reading;
        $inspectionDate = $date ?? now();
        $userId = $user?->id ?? $consumer->user_id;

        $inspection = MeterInspection::create([
            'consumer_account_id' => $consumer->id,
            'user_id' => $userId,
            'ca_number' => $consumer->ca_number,
            'physical_reading' => $readingStr,
            'inspected_at' => $inspectionDate,
            'notes' => $notes,
        ]);

        $consumer->update([
            'last_physical_reading' => $readingStr,
            'last_inspected_at' => $inspectionDate,
            'inspected_by_user_id' => $userId,
        ]);

        return $inspection;
    }

    /**
     * Determine the safe monthly unit ceiling for a consumer based on tariff and overrides.
     */
    public function getSafeCeiling(ConsumerAccount $consumer): int
    {
        if (! empty($consumer->custom_safe_ceiling) && (int) $consumer->custom_safe_ceiling > 0) {
            return (int) $consumer->custom_safe_ceiling;
        }

        $tariff = strtoupper(trim((string) $consumer->tariff_category));

        if (str_contains($tariff, 'IAS') || str_contains($tariff, 'AGR')) {
            return (int) SystemSetting::get('gap_safe_ceiling_agriculture', 800);
        }

        if (str_contains($tariff, 'NDS') || str_contains($tariff, 'COM')) {
            return (int) SystemSetting::get('gap_safe_ceiling_commercial', 400);
        }

        return (int) SystemSetting::get('gap_safe_ceiling_domestic', 220);
    }

    /**
     * Calculate reconciliation recommendation for a bill period.
     *
     * @return array{
     *     gap_status: 'uninspected'|'in_sync'|'over_posted'|'under_posted',
     *     net_gap: int,
     *     safe_ceiling: int,
     *     recommended_working_reading: string,
     *     billed_units: int,
     *     gap_slice_applied: int,
     *     estimated_catchup_cycles: int,
     *     estimated_recovery_cycles: int,
     *     is_stale: bool,
     *     days_since_inspection: int|null
     * }
     */
    public function calculateRecommendation(
        BillRecord $bill,
        ConsumerAccount $consumer,
        ?int $smartAvg = null
    ): array {
        $staleWarningDays = (int) SystemSetting::get('inspection_stale_warning_days', 90);
        $daysSinceInspection = $consumer->last_inspected_at
            ? (int) Carbon::parse($consumer->last_inspected_at)->diffInDays(now())
            : null;
        $isStale = $daysSinceInspection !== null && $daysSinceInspection > $staleWarningDays;

        $safeCeiling = $this->getSafeCeiling($consumer);
        $avg = $smartAvg !== null ? max(1, $smartAvg) : max(1, (int) ($bill->units_consumed ?? 50));

        // Baseline posted reading P: prior reading from bill
        $postedReading = null;
        if ($bill->previous_reading !== null && is_numeric($bill->previous_reading)) {
            $postedReading = (int) $bill->previous_reading;
        } elseif ($bill->current_reading !== null && is_numeric($bill->current_reading)) {
            $postedReading = (int) $bill->current_reading;
        } else {
            $postedReading = 0;
        }

        // Physical meter reading M
        if ($consumer->last_physical_reading === null || ! is_numeric($consumer->last_physical_reading)) {
            return [
                'gap_status' => 'uninspected',
                'net_gap' => 0,
                'safe_ceiling' => $safeCeiling,
                'recommended_working_reading' => (string) ($postedReading + $avg),
                'billed_units' => $avg,
                'gap_slice_applied' => 0,
                'estimated_catchup_cycles' => 0,
                'estimated_recovery_cycles' => 0,
                'is_stale' => false,
                'days_since_inspection' => null,
            ];
        }

        $physicalReading = (int) $consumer->last_physical_reading;
        $netGap = $physicalReading - $postedReading;

        // Case 1: Over-posted (M < P, netGap < 0)
        if ($netGap < 0) {
            $holdMode = $consumer->gap_recovery_mode;
            if (empty($holdMode)) {
                $defaultMode = SystemSetting::get('gap_hold_default_mode', 'zero');
                $holdMode = ($defaultMode === 'one') ? 'hold_advance' : 'hold_exact';
            }

            $billedUnits = ($holdMode === 'hold_advance') ? 1 : 0;
            $recommendedReading = (string) ($postedReading + $billedUnits);
            $cycles = (int) ceil(abs($netGap) / $avg);

            return [
                'gap_status' => 'over_posted',
                'net_gap' => $netGap,
                'safe_ceiling' => $safeCeiling,
                'recommended_working_reading' => $recommendedReading,
                'billed_units' => $billedUnits,
                'gap_slice_applied' => 0,
                'estimated_catchup_cycles' => $cycles,
                'estimated_recovery_cycles' => 0,
                'is_stale' => $isStale,
                'days_since_inspection' => $daysSinceInspection,
            ];
        }

        // Case 2: Under-posted (M > P, netGap > 0)
        if ($netGap > 0) {
            // How much extra unit bandwidth is available under the safe ceiling?
            $maxSliceAllowed = max(0, $safeCeiling - $avg);
            $gapSliceApplied = min($netGap, $maxSliceAllowed);
            $billedUnits = $avg + $gapSliceApplied;
            $recommendedReading = (string) ($postedReading + $billedUnits);
            $recoveryCycles = $gapSliceApplied > 0 ? (int) ceil($netGap / $gapSliceApplied) : 1;

            return [
                'gap_status' => 'under_posted',
                'net_gap' => $netGap,
                'safe_ceiling' => $safeCeiling,
                'recommended_working_reading' => $recommendedReading,
                'billed_units' => $billedUnits,
                'gap_slice_applied' => $gapSliceApplied,
                'estimated_catchup_cycles' => 0,
                'estimated_recovery_cycles' => $recoveryCycles,
                'is_stale' => $isStale,
                'days_since_inspection' => $daysSinceInspection,
            ];
        }

        // Case 3: In sync (M == P, netGap == 0)
        return [
            'gap_status' => 'in_sync',
            'net_gap' => 0,
            'safe_ceiling' => $safeCeiling,
            'recommended_working_reading' => (string) ($postedReading + $avg),
            'billed_units' => $avg,
            'gap_slice_applied' => 0,
            'estimated_catchup_cycles' => 0,
            'estimated_recovery_cycles' => 0,
            'is_stale' => $isStale,
            'days_since_inspection' => $daysSinceInspection,
        ];
    }

    /**
     * Get a comprehensive gap summary for consumer account.
     */
    public function getGapSummary(ConsumerAccount $consumer, ?BillRecord $latestBill = null): array
    {
        $safeCeiling = $this->getSafeCeiling($consumer);
        $staleWarningDays = (int) SystemSetting::get('inspection_stale_warning_days', 90);
        $daysSince = $consumer->last_inspected_at
            ? (int) Carbon::parse($consumer->last_inspected_at)->diffInDays(now())
            : null;

        $summary = [
            'ca_number' => $consumer->ca_number,
            'last_physical_reading' => $consumer->last_physical_reading,
            'last_inspected_at' => $consumer->last_inspected_at?->toIso8601String(),
            'days_since_inspection' => $daysSince,
            'is_stale' => $daysSince !== null && $daysSince > $staleWarningDays,
            'safe_ceiling' => $safeCeiling,
            'gap_recovery_mode' => $consumer->gap_recovery_mode,
            'custom_safe_ceiling' => $consumer->custom_safe_ceiling,
        ];

        if ($latestBill) {
            $recommendation = $this->calculateRecommendation($latestBill, $consumer);
            $summary = array_merge($summary, $recommendation);
        }

        return $summary;
    }
}
