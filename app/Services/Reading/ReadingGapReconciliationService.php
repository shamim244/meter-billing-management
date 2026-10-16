<?php

namespace App\Services\Reading;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\MeterInspection;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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

        $this->syncConsumerInspectionCache($consumer);

        return $inspection;
    }

    /**
     * Synchronize a consumer's cached physical inspection data with the most recent inspection entry.
     */
    public function syncConsumerInspectionCache(ConsumerAccount $consumer): void
    {
        $latest = MeterInspection::withoutGlobalScopes()
            ->where('consumer_account_id', $consumer->id)
            ->orderByDesc('inspected_at')
            ->orderByDesc('id')
            ->first();

        if ($latest) {
            $consumer->update([
                'last_physical_reading' => (string) $latest->physical_reading,
                'last_inspected_at' => $latest->inspected_at,
                'inspected_by_user_id' => $latest->user_id,
            ]);
        } else {
            $consumer->update([
                'last_physical_reading' => null,
                'last_inspected_at' => null,
                'inspected_by_user_id' => null,
            ]);
        }
    }

    /**
     * Retrieve all historical inspections for a consumer.
     */
    public function getInspectionHistory(ConsumerAccount $consumer): Collection
    {
        return MeterInspection::withoutGlobalScopes()
            ->where('consumer_account_id', $consumer->id)
            ->with('user:id,name,email')
            ->orderByDesc('inspected_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (MeterInspection $item) {
                return [
                    'id' => $item->id,
                    'ca_number' => $item->ca_number,
                    'physical_reading' => $item->physical_reading,
                    'posted_reading_at_inspection' => $item->posted_reading_at_inspection,
                    'gap_at_inspection' => $item->gap_at_inspection,
                    'notes' => $item->notes,
                    'inspected_at' => $item->inspected_at?->format('Y-m-d'),
                    'inspected_at_human' => $item->inspected_at?->diffForHumans(),
                    'inspected_by_name' => $item->user?->name ?? 'System',
                    'created_at' => $item->created_at?->toIso8601String(),
                ];
            });
    }

    /**
     * Update an existing physical inspection and re-sync consumer cache.
     */
    public function updatePhysicalInspection(MeterInspection $inspection, array $data): MeterInspection
    {
        $updateData = [];

        if (array_key_exists('physical_reading', $data)) {
            $updateData['physical_reading'] = (string) $data['physical_reading'];
        }

        if (array_key_exists('inspected_at', $data)) {
            $updateData['inspected_at'] = ! empty($data['inspected_at']) ? Carbon::parse($data['inspected_at']) : now();
        }

        if (array_key_exists('notes', $data)) {
            $updateData['notes'] = $data['notes'];
        }

        $inspection->update($updateData);

        /** @var ConsumerAccount|null $consumer */
        $consumer = $inspection->consumerAccount ?: ConsumerAccount::withoutGlobalScopes()->find($inspection->consumer_account_id);
        if ($consumer) {
            $this->syncConsumerInspectionCache($consumer);
        }

        return $inspection;
    }

    /**
     * Delete an inspection and re-sync consumer cache to previous or null.
     */
    public function deletePhysicalInspection(MeterInspection $inspection): void
    {
        /** @var ConsumerAccount|null $consumer */
        $consumer = $inspection->consumerAccount ?: ConsumerAccount::withoutGlobalScopes()->find($inspection->consumer_account_id);
        $inspection->delete();

        if ($consumer) {
            $this->syncConsumerInspectionCache($consumer);
        }
    }

    /**
     * Delete multiple inspections by ID and re-sync consumer cache.
     */
    public function deleteMultipleInspections(array $inspectionIds, ?ConsumerAccount $consumer = null): int
    {
        if (empty($inspectionIds)) {
            return 0;
        }

        $inspections = MeterInspection::withoutGlobalScopes()->whereIn('id', $inspectionIds)->get();
        if ($inspections->isEmpty()) {
            return 0;
        }

        $consumerId = $consumer?->id ?? $inspections->first()->consumer_account_id;
        $deleted = MeterInspection::withoutGlobalScopes()->whereIn('id', $inspectionIds)->delete();

        $targetConsumer = $consumer ?: ConsumerAccount::withoutGlobalScopes()->find($consumerId);
        if ($targetConsumer) {
            $this->syncConsumerInspectionCache($targetConsumer);
        }

        return $deleted;
    }

    /**
     * Clear all physical inspections for a consumer and reset cache to uninspected.
     */
    public function clearAllInspections(ConsumerAccount $consumer): int
    {
        $deleted = MeterInspection::withoutGlobalScopes()
            ->where('consumer_account_id', $consumer->id)
            ->delete();

        $this->syncConsumerInspectionCache($consumer);

        return $deleted;
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
        $rolloverCheck = $this->detectMeterRollover($physicalReading, $postedReading);
        $netGap = $rolloverCheck['adjusted_gap'];

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
    public function getGapSummary(ConsumerAccount $consumer, ?BillRecord $latestBill = null, ?int $smartAvg = null): array
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
            'last_inspected_human' => $consumer->last_inspected_at ? Carbon::parse($consumer->last_inspected_at)->diffForHumans() : null,
            'days_since_inspection' => $daysSince,
            'is_stale' => $daysSince !== null && $daysSince > $staleWarningDays,
            'safe_ceiling' => $safeCeiling,
            'gap_recovery_mode' => $consumer->gap_recovery_mode ?: 'auto',
            'custom_safe_ceiling' => $consumer->custom_safe_ceiling,
            'gap_status' => 'uninspected',
            'net_gap' => 0,
            'has_gap' => false,
            'badge_color' => 'slate',
            'badge_label' => 'No Inspection',
        ];

        if ($latestBill) {
            $recommendation = $this->calculateRecommendation($latestBill, $consumer, $smartAvg);
            $summary = array_merge($summary, $recommendation);

            $status = $summary['gap_status'];
            $gap = (int) ($summary['net_gap'] ?? 0);
            $summary['has_gap'] = ($status === 'over_posted' || $status === 'under_posted');

            $summary['badge_color'] = match ($status) {
                'over_posted' => 'amber',
                'under_posted' => 'rose',
                'in_sync' => 'emerald',
                default => 'slate',
            };

            $summary['badge_label'] = match ($status) {
                'over_posted' => 'Over-Posted ('.abs($gap).' kWh)',
                'under_posted' => 'Gap +'.$gap.' kWh',
                'in_sync' => 'In-Sync',
                default => 'No Inspection',
            };
        }

        return $summary;
    }

    /**
     * Detect mechanical or digital dial rollover (e.g. 99,950 -> 00,050).
     *
     * @return array{is_rollover: bool, adjusted_gap: int}
     */
    public function detectMeterRollover(float|int $physical, float|int $posted, int $dialCapacity = 100000): array
    {
        $physical = (int) $physical;
        $posted = (int) $posted;
        $rawDelta = $physical - $posted;

        // If posted reading is near top of capacity (> 80%) and physical is near bottom (< 20%)
        if ($rawDelta < 0 && $posted >= ($dialCapacity * 0.8) && $physical <= ($dialCapacity * 0.2)) {
            $adjustedGap = ($dialCapacity - $posted) + $physical;

            return [
                'is_rollover' => true,
                'adjusted_gap' => $adjustedGap,
            ];
        }

        return [
            'is_rollover' => false,
            'adjusted_gap' => $rawDelta,
        ];
    }

    /**
     * Record a meter replacement / change-out event.
     */
    public function recordMeterReplacement(
        ConsumerAccount $consumer,
        string|float|int $initialReading,
        ?Carbon $date = null,
        ?string $oldMeterFinalReading = null,
        ?string $newMeterNo = null,
        ?string $notes = null,
        ?User $user = null
    ): MeterInspection {
        $readingStr = (string) $initialReading;
        $eventDate = $date ?? now();
        $userId = $user?->id ?? $consumer->user_id;

        $auditNote = 'Meter Replacement: ';
        if ($newMeterNo) {
            $auditNote .= "New Meter: {$newMeterNo}. ";
        }
        if ($oldMeterFinalReading !== null) {
            $auditNote .= "Old Final Reading: {$oldMeterFinalReading}. ";
        }
        if ($notes) {
            $auditNote .= "Notes: {$notes}";
        }

        $inspection = MeterInspection::create([
            'consumer_account_id' => $consumer->id,
            'user_id' => $userId,
            'ca_number' => $consumer->ca_number,
            'physical_reading' => $readingStr,
            'inspected_at' => $eventDate,
            'notes' => trim($auditNote),
        ]);

        $updateData = [
            'last_physical_reading' => $readingStr,
            'last_inspected_at' => $eventDate,
            'inspected_by_user_id' => $userId,
        ];

        if (! empty($newMeterNo)) {
            $updateData['meter_no'] = $newMeterNo;
        }

        $consumer->update($updateData);

        return $inspection;
    }

    /**
     * Aggregate reading gap analytics across an MRU or user workspace.
     *
     * @return array{
     *     total_consumers: int,
     *     inspected_count: int,
     *     uninspected_count: int,
     *     over_posted_count: int,
     *     under_posted_count: int,
     *     in_sync_count: int,
     *     stale_inspections_count: int,
     *     total_unbilled_kwh: int,
     *     total_over_posted_kwh: int,
     *     estimated_financial_gap_rs: float
     * }
     */
    public function getMruGapAnalytics(?int $userId = null, ?int $mruId = null): array
    {
        $consumersQuery = ConsumerAccount::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($mruId, fn ($q) => $q->where('mru_id', $mruId));

        $consumers = $consumersQuery->get();
        if ($consumers->isEmpty()) {
            return [
                'total_consumers' => 0,
                'inspected_count' => 0,
                'uninspected_count' => 0,
                'over_posted_count' => 0,
                'under_posted_count' => 0,
                'in_sync_count' => 0,
                'stale_inspections_count' => 0,
                'total_unbilled_kwh' => 0,
                'total_over_posted_kwh' => 0,
                'estimated_financial_gap_rs' => 0.0,
            ];
        }

        $caNumbers = $consumers->pluck('ca_number')->filter()->unique();

        $latestBills = BillRecord::whereIn('ca_number', $caNumbers)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get()
            ->groupBy('ca_number')
            ->map(fn ($group) => $group->first());

        $staleWarningDays = (int) SystemSetting::get('inspection_stale_warning_days', 90);
        $totalConsumers = $consumers->count();
        $inspectedCount = 0;
        $uninspectedCount = 0;
        $overPostedCount = 0;
        $underPostedCount = 0;
        $inSyncCount = 0;
        $staleInspectionsCount = 0;
        $totalUnbilledKwh = 0;
        $totalOverPostedKwh = 0;
        $estimatedFinancialGapRs = 0.0;

        foreach ($consumers as $consumer) {
            if ($consumer->last_physical_reading === null || ! is_numeric($consumer->last_physical_reading)) {
                $uninspectedCount++;

                continue;
            }

            $inspectedCount++;

            $daysSince = $consumer->last_inspected_at
                ? (int) Carbon::parse($consumer->last_inspected_at)->diffInDays(now())
                : null;
            if ($daysSince !== null && $daysSince > $staleWarningDays) {
                $staleInspectionsCount++;
            }

            $latestBill = $latestBills->get($consumer->ca_number);
            $postedReading = 0;
            if ($latestBill) {
                if ($latestBill->previous_reading !== null && is_numeric($latestBill->previous_reading)) {
                    $postedReading = (int) $latestBill->previous_reading;
                } elseif ($latestBill->current_reading !== null && is_numeric($latestBill->current_reading)) {
                    $postedReading = (int) $latestBill->current_reading;
                }
            }

            $physicalReading = (int) $consumer->last_physical_reading;
            $rolloverCheck = $this->detectMeterRollover($physicalReading, $postedReading);
            $netGap = $rolloverCheck['adjusted_gap'];

            $tariff = strtoupper(trim((string) $consumer->tariff_category));
            $ratePerKwh = (str_contains($tariff, 'NDS') || str_contains($tariff, 'COM')) ? 8.5 : 6.5;

            if ($netGap > 0) {
                $underPostedCount++;
                $totalUnbilledKwh += $netGap;
                $estimatedFinancialGapRs += ($netGap * $ratePerKwh);
            } elseif ($netGap < 0) {
                $overPostedCount++;
                $totalOverPostedKwh += abs($netGap);
            } else {
                $inSyncCount++;
            }
        }

        return [
            'total_consumers' => $totalConsumers,
            'inspected_count' => $inspectedCount,
            'uninspected_count' => $uninspectedCount,
            'over_posted_count' => $overPostedCount,
            'under_posted_count' => $underPostedCount,
            'in_sync_count' => $inSyncCount,
            'stale_inspections_count' => $staleInspectionsCount,
            'total_unbilled_kwh' => $totalUnbilledKwh,
            'total_over_posted_kwh' => $totalOverPostedKwh,
            'estimated_financial_gap_rs' => round($estimatedFinancialGapRs, 2),
        ];
    }
}
