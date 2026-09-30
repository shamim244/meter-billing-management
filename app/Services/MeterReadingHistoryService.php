<?php

namespace App\Services;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\MeterReadingHistory;
use App\Models\SystemSetting;
use Illuminate\Support\Collection;

class MeterReadingHistoryService
{
    /**
     * Record or update historical entry from an official PDF bill parse.
     * Also captures decoded prior month baseline reading and all 12-month consumption history ([kir fooj.kh).
     */
    /**
     * Resolve spike multiplier, unit buffer floor, and category bypass settings.
     *
     * @return array{category: string, bypassed: bool, multiplier: float, min_buffer: int}
     */
    public function resolveSpikeParameters(?string $tariffCategory): array
    {
        $tariff = strtoupper(trim((string) $tariffCategory));
        $minBuffer = (int) SystemSetting::get('min_spike_unit_buffer', 30);

        if (str_contains($tariff, 'IAS')) {
            $bypass = (bool) SystemSetting::get('agriculture_spike_bypass', true);
            $mult = (float) SystemSetting::get('agriculture_spike_multiplier', 4.0);

            return [
                'category' => 'agriculture',
                'bypassed' => $bypass,
                'multiplier' => $mult,
                'min_buffer' => $minBuffer,
            ];
        }

        if (str_contains($tariff, 'NDS')) {
            return [
                'category' => 'commercial',
                'bypassed' => false,
                'multiplier' => (float) SystemSetting::get('commercial_spike_multiplier', 2.5),
                'min_buffer' => $minBuffer,
            ];
        }

        if (str_contains($tariff, 'DS') || str_contains($tariff, 'KJ') || str_contains($tariff, 'KUTIR') || str_contains($tariff, 'JYOTI')) {
            return [
                'category' => 'domestic',
                'bypassed' => false,
                'multiplier' => (float) SystemSetting::get('domestic_spike_multiplier', 2.0),
                'min_buffer' => $minBuffer,
            ];
        }

        return [
            'category' => 'global',
            'bypassed' => false,
            'multiplier' => (float) SystemSetting::get('global_spike_multiplier', 2.0),
            'min_buffer' => $minBuffer,
        ];
    }

    /**
     * Evaluate ingestion quality gate for a reading: flag spikes, non-OK bases, and ghost rows.
     *
     * @return array{is_ghost: bool, is_spike: bool, active_for_average: bool, meta: array}
     */
    public function evaluateIngestionQuality(
        ?int $units,
        string $basis,
        ?float $medianOkUnits,
        array $spikeParams,
        bool $extractionFilterEnabled,
        bool $filterNonOkBases,
        bool $filterZeroUnits
    ): array {
        $cleanBasis = strtoupper(trim($basis));
        $isOk = in_array($cleanBasis, ['OK', 'NORMAL', 'NORMAL(OK)']);
        $isZeroUnits = ($units === null || $units <= 0);

        if (! $extractionFilterEnabled) {
            return [
                'is_ghost' => false,
                'is_spike' => false,
                'active_for_average' => true,
                'meta' => [
                    'active_for_average' => true,
                    'is_spike' => false,
                    'basis' => $cleanBasis,
                ],
            ];
        }

        // 1. Ghost row check
        $isGhost = $isZeroUnits && $filterZeroUnits;

        // 2. Non-OK basis check (MD, LK, PL, DL, EST, etc.)
        $isNonOkBasis = ! $isOk;

        // 3. Spike check
        $isSpike = false;
        $spikeDetails = null;

        if (! $isZeroUnits && $medianOkUnits !== null && $medianOkUnits > 0 && ! $spikeParams['bypassed']) {
            $multiplier = $spikeParams['multiplier'];
            $buffer = $spikeParams['min_buffer'];

            if (($units > ($multiplier * $medianOkUnits)) && (($units - $medianOkUnits) >= $buffer)) {
                $isSpike = true;
                $spikeDetails = [
                    'units' => $units,
                    'median' => $medianOkUnits,
                    'multiplier' => $multiplier,
                    'buffer' => $buffer,
                ];
            }
        }

        $isMdOrLk = in_array($cleanBasis, ['MD', 'LK']);
        $activeForAverage = ! $isZeroUnits && ! $isSpike && ! ($filterNonOkBases && $isNonOkBasis) && ! $isMdOrLk;

        $filterReasons = [];
        if ($isNonOkBasis && ($filterNonOkBases || $isMdOrLk)) {
            $filterReasons[] = "non_ok_basis_{$cleanBasis}";
        }
        if ($isZeroUnits && $filterZeroUnits) {
            $filterReasons[] = 'zero_units';
        }
        if ($isSpike) {
            $filterReasons[] = 'spike';
        }

        return [
            'is_ghost' => $isGhost,
            'is_spike' => $isSpike,
            'active_for_average' => $activeForAverage,
            'meta' => [
                'is_spike' => $isSpike,
                'active_for_average' => $activeForAverage,
                'basis' => $cleanBasis,
                'spike_details' => $spikeDetails,
                'filter_reasons' => $filterReasons,
            ],
        ];
    }

    /**
     * Record or update historical entry from an official PDF bill parse.
     * Also captures decoded prior month baseline reading and all 12-month consumption history ([kir fooj.kh).
     */
    public function recordFromPdf(BillRecord $bill, array $extractedHistory = []): MeterReadingHistory
    {
        $prevMonth = $bill->billing_month == 1 ? 12 : ((int) $bill->billing_month - 1);
        $prevYear = $bill->billing_month == 1 ? ((int) $bill->billing_year - 1) : (int) $bill->billing_year;

        $extractionFilterEnabled = (bool) SystemSetting::get('extraction_filter_enabled', true);
        $filterNonOkBases = (bool) SystemSetting::get('filter_non_ok_bases', true);
        $filterZeroUnits = (bool) SystemSetting::get('filter_zero_unit_months', true);

        $tariffCategory = $bill->tariff_category
            ?? $bill->consumerAccount?->tariff_category
            ?? ConsumerAccount::where('ca_number', $bill->ca_number)->value('tariff_category');
        $spikeParams = $this->resolveSpikeParameters($tariffCategory);

        // Precompute baseline median from OK historical months for ingestion spike detection
        $okUnits = collect();
        $currUnits = $bill->units_consumed ? (int) $bill->units_consumed : null;
        $currBasis = strtoupper(trim((string) ($bill->billing_basis ?: 'OK')));
        if ($currUnits !== null && $currUnits > 0 && in_array($currBasis, ['OK', 'NORMAL', 'NORMAL(OK)'])) {
            $okUnits->push($currUnits);
        }
        foreach ($extractedHistory as $hRow) {
            $hU = (int) ($hRow['units'] ?? 0);
            $hB = strtoupper(trim((string) ($hRow['basis'] ?? 'OK')));
            if ($hU > 0 && in_array($hB, ['OK', 'NORMAL', 'NORMAL(OK)'])) {
                $okUnits->push($hU);
            }
        }
        $existingOkHist = MeterReadingHistory::where('ca_number', $bill->ca_number)
            ->where('user_id', $bill->user_id)
            ->where('reading_source', 'pdf')
            ->whereNotNull('units_consumed')
            ->where('units_consumed', '>', 0)
            ->whereIn('billing_basis', ['OK', 'NORMAL', 'NORMAL(OK)'])
            ->pluck('units_consumed');
        foreach ($existingOkHist as $eUnits) {
            $okUnits->push((int) $eUnits);
        }
        $medianOkUnits = $okUnits->isNotEmpty() ? (float) $okUnits->median() : null;

        // 1. If previous_reading is decoded in PDF, ensure prior month has a baseline entry in history
        if (! empty($bill->previous_reading) && is_numeric($bill->previous_reading)) {
            $hasPrev = MeterReadingHistory::where('user_id', $bill->user_id)
                ->where('ca_number', $bill->ca_number)
                ->where('billing_month', $prevMonth)
                ->where('billing_year', $prevYear)
                ->exists();

            if (! $hasPrev) {
                MeterReadingHistory::create([
                    'user_id' => $bill->user_id,
                    'ca_number' => $bill->ca_number,
                    'mru_id' => $bill->mru_id,
                    'consumer_id' => $bill->consumer_account_id ?: $bill->consumerAccount?->id,
                    'billing_month' => $prevMonth,
                    'billing_year' => $prevYear,
                    'reading_source' => 'pdf',
                    'current_reading' => (string) $bill->previous_reading,
                    'billing_basis' => 'OK',
                    'is_closed' => true,
                    'meta' => [
                        'active_for_average' => true,
                        'is_spike' => false,
                        'basis' => 'OK',
                    ],
                ]);
            }
        }

        $evalCurrent = $this->evaluateIngestionQuality(
            $currUnits,
            $bill->billing_basis ?: 'OK',
            $medianOkUnits,
            $spikeParams,
            $extractionFilterEnabled,
            $filterNonOkBases,
            $filterZeroUnits
        );

        // 2. Record the current bill's month
        $currentHistory = MeterReadingHistory::updateOrCreate(
            [
                'user_id' => $bill->user_id,
                'ca_number' => $bill->ca_number,
                'billing_month' => (int) $bill->billing_month,
                'billing_year' => (int) $bill->billing_year,
                'reading_source' => 'pdf',
            ],
            [
                'mru_id' => $bill->mru_id,
                'consumer_id' => $bill->consumer_account_id ?: $bill->consumerAccount?->id,
                'bill_record_id' => $bill->id,
                'previous_reading' => $bill->previous_reading,
                'current_reading' => $bill->current_reading,
                'units_consumed' => $bill->units_consumed ? (int) $bill->units_consumed : null,
                'billing_basis' => strtoupper(trim((string) ($bill->billing_basis ?: 'OK'))),
                'is_closed' => false,
                'meta' => $evalCurrent['meta'],
            ]
        );

        // 3. Process decoded past consumption history table ([kir fooj.kh) if available
        if (! empty($extractedHistory)) {
            $runningReading = is_numeric($bill->previous_reading) ? (int) $bill->previous_reading : null;

            foreach ($extractedHistory as $h) {
                $hMonth = (int) $h['month'];
                $hYear = (int) $h['year'];
                $hUnits = (int) $h['units'];
                $hBasis = $h['basis'] ?? 'OK';

                // Skip the current bill month since it's already saved above
                if ($hMonth === (int) $bill->billing_month && $hYear === (int) $bill->billing_year) {
                    continue;
                }

                $currR = $runningReading;
                $prevR = ($currR !== null && $currR >= $hUnits) ? ($currR - $hUnits) : null;

                $evalRow = $this->evaluateIngestionQuality(
                    $hUnits,
                    $hBasis,
                    $medianOkUnits,
                    $spikeParams,
                    $extractionFilterEnabled,
                    $filterNonOkBases,
                    $filterZeroUnits
                );

                // Advance running reading before potentially skipping ghost row
                $runningReading = $prevR;

                // Eliminate empty ghost rows (0 units, null readings) from entering meter_reading_histories
                if ($extractionFilterEnabled) {
                    $isZeroUnits = ($hUnits <= 0);
                    $hasNoReadings = ($currR === null && $prevR === null && empty($bill->previous_reading));
                    if (($filterZeroUnits && $isZeroUnits) || ($isZeroUnits && $hasNoReadings)) {
                        continue;
                    }
                }

                $existing = MeterReadingHistory::where('user_id', $bill->user_id)
                    ->where('ca_number', $bill->ca_number)
                    ->where('billing_month', $hMonth)
                    ->where('billing_year', $hYear)
                    ->first();

                if (! $existing) {
                    MeterReadingHistory::create([
                        'user_id' => $bill->user_id,
                        'ca_number' => $bill->ca_number,
                        'mru_id' => $bill->mru_id,
                        'consumer_id' => $bill->consumer_account_id ?: $bill->consumerAccount?->id,
                        'billing_month' => $hMonth,
                        'billing_year' => $hYear,
                        'reading_source' => 'pdf',
                        'previous_reading' => $prevR !== null ? (string) $prevR : null,
                        'current_reading' => $currR !== null ? (string) $currR : null,
                        'units_consumed' => $hUnits,
                        'billing_basis' => $hBasis,
                        'is_closed' => true,
                        'meta' => $evalRow['meta'],
                    ]);
                } else {
                    // Update units or basis if empty
                    if ($existing->reading_source === 'pdf') {
                        if (empty($existing->units_consumed) && $hUnits > 0) {
                            $existing->units_consumed = $hUnits;
                        }
                        if (empty($existing->current_reading) && $currR !== null) {
                            $existing->current_reading = (string) $currR;
                        }
                        if (empty($existing->previous_reading) && $prevR !== null) {
                            $existing->previous_reading = (string) $prevR;
                        }
                        $existing->meta = array_merge($existing->meta ?? [], $evalRow['meta']);
                        $existing->save();
                    }
                }
            }
        }

        return $currentHistory;
    }

    /**
     * Record or update historical entry from a live working month entered by an operator.
     */
    public function recordFromWorkingReading(
        BillRecord $bill,
        string $workingReading,
        ?int $unitsConsumed = null,
        bool $isSubmitted = false
    ): MeterReadingHistory {
        $units = $unitsConsumed;
        if ($units === null && is_numeric($workingReading)) {
            $prev = is_numeric($bill->previous_reading) ? (int) $bill->previous_reading : 0;
            if ($prev > 0 && (int) $workingReading >= $prev) {
                $units = (int) $workingReading - $prev;
            }
        }

        return MeterReadingHistory::updateOrCreate(
            [
                'user_id' => $bill->user_id,
                'ca_number' => $bill->ca_number,
                'billing_month' => (int) $bill->billing_month,
                'billing_year' => (int) $bill->billing_year,
                'reading_source' => 'working',
            ],
            [
                'mru_id' => $bill->mru_id,
                'consumer_id' => $bill->consumer_account_id ?: $bill->consumerAccount?->id,
                'bill_record_id' => $bill->id,
                'previous_reading' => $bill->previous_reading,
                'current_reading' => $bill->current_reading,
                'working_reading' => $workingReading,
                'units_consumed' => $units,
                'billing_basis' => strtoupper(trim((string) ($bill->billing_basis ?: 'OK'))),
                'is_closed' => $isSubmitted,
            ]
        );
    }

    /**
     * Backfill/sync all existing bills for a user into meter_reading_histories without modifying original bills.
     */
    public function syncAllFromExistingBills(int $userId): int
    {
        $bills = BillRecord::where('user_id', $userId)->get();
        $synced = 0;

        foreach ($bills as $bill) {
            // 1. Sync PDF entry if readings exist
            if (! empty($bill->current_reading) || ! empty($bill->previous_reading)) {
                $this->recordFromPdf($bill);
                $synced++;
            }

            // 2. Sync Working reading if present
            if (! empty($bill->working_reading)) {
                $isSubmitted = (strtolower(trim($bill->review_status ?? '')) === 'submitted');
                $this->recordFromWorkingReading($bill, $bill->working_reading, $bill->units_consumed, $isSubmitted);
                $synced++;
            }
        }

        return $synced;
    }

    /**
     * Get two-dimensional consumption history matrix for a consumer across periods.
     * Combines both PDF official records and working month live entries.
     */
    public function getConsumerMonthlyMatrix(?int $userId, string $caNumber, ?int $year = null): array
    {
        $query = MeterReadingHistory::with('mru')
            ->when($userId !== null, fn ($q) => $q->where('user_id', $userId))
            ->where('ca_number', $caNumber);

        if ($year !== null) {
            $query->where('billing_year', $year);
        }

        $records = $query->orderBy('billing_year', 'asc')
            ->orderBy('billing_month', 'asc')
            ->get();

        // Group by period key: YYYY-MM
        $periodsMap = [];
        foreach ($records as $rec) {
            $key = sprintf('%04d-%02d', $rec->billing_year, $rec->billing_month);
            if (! isset($periodsMap[$key])) {
                $periodsMap[$key] = [
                    'year' => $rec->billing_year,
                    'month' => $rec->billing_month,
                    'month_name' => $rec->getMonthLabel(),
                    'short_month' => $rec->getShortMonthLabel(),
                    'pdf_previous' => null,
                    'pdf_reading' => null,
                    'pdf_units' => null,
                    'working_reading' => null,
                    'working_units' => null,
                    'effective_reading' => null,
                    'effective_units' => 0,
                    'billing_basis' => $rec->billing_basis,
                    'is_closed' => false,
                    'has_pdf' => false,
                    'has_working' => false,
                ];
            }

            if ($rec->reading_source === 'pdf') {
                $periodsMap[$key]['has_pdf'] = true;
                $periodsMap[$key]['pdf_previous'] = $rec->previous_reading;
                $periodsMap[$key]['pdf_reading'] = $rec->current_reading;
                $periodsMap[$key]['pdf_units'] = $rec->units_consumed;
                if ($periodsMap[$key]['effective_reading'] === null) {
                    $periodsMap[$key]['effective_reading'] = is_numeric($rec->current_reading) ? (int) $rec->current_reading : null;
                    $periodsMap[$key]['effective_units'] = $rec->units_consumed ?: 0;
                }
            } elseif ($rec->reading_source === 'working') {
                $periodsMap[$key]['has_working'] = true;
                $periodsMap[$key]['working_reading'] = $rec->working_reading;
                $periodsMap[$key]['working_units'] = $rec->units_consumed;
                $periodsMap[$key]['is_closed'] = $rec->is_closed;
                // Working reading takes precedence for effective calculation
                $periodsMap[$key]['effective_reading'] = is_numeric($rec->working_reading) ? (int) $rec->working_reading : null;
                $periodsMap[$key]['effective_units'] = $rec->units_consumed ?: 0;
            }
        }

        $periods = array_values($periodsMap);

        // Compute consecutive monthly reading subtraction (Month M - Month M-1) and formula
        for ($i = 0; $i < count($periods); $i++) {
            $periods[$i]['delta_formula'] = null;
            $currReading = $periods[$i]['effective_reading'];

            if ($currReading !== null) {
                $prevReading = null;
                if ($i > 0 && $periods[$i - 1]['effective_reading'] !== null) {
                    $prevReading = $periods[$i - 1]['effective_reading'];
                } elseif (! empty($periods[$i]['pdf_previous']) && is_numeric($periods[$i]['pdf_previous'])) {
                    $prevReading = (int) $periods[$i]['pdf_previous'];
                }

                if ($prevReading !== null && $currReading >= $prevReading) {
                    $computedDelta = $currReading - $prevReading;
                    if ($periods[$i]['effective_units'] <= 0) {
                        $periods[$i]['effective_units'] = $computedDelta;
                    }
                    $periods[$i]['delta_formula'] = "{$currReading} - {$prevReading} = {$periods[$i]['effective_units']} kWh";
                }
            }
        }

        // Compute summary metrics
        $allUnits = array_filter(array_column($periods, 'effective_units'), fn ($u) => $u > 0);
        $avgUnits = count($allUnits) > 0 ? (int) round(array_sum($allUnits) / count($allUnits)) : 50;

        return [
            'ca_number' => $caNumber,
            'periods_count' => count($periods),
            'periods' => $periods,
            'average_units' => $avgUnits,
            'historical_units' => array_values($allUnits),
        ];
    }

    /**
     * Retrieve clean historical units for smart average calculation prior to a target cycle.
     * Pulls from meter_reading_histories table (prioritizing working entries over pdf entries).
     */
    public function getPriorHistoricalUnits(int $userId, string $caNumber, int $month, int $year): Collection
    {
        $records = MeterReadingHistory::where('user_id', $userId)
            ->where('ca_number', $caNumber)
            ->where(function ($q) use ($month, $year) {
                $q->where('billing_year', '<', $year)
                    ->orWhere(function ($q2) use ($month, $year) {
                        $q2->where('billing_year', $year)
                            ->where('billing_month', '<', $month);
                    });
            })
            ->orderBy('billing_year', 'asc')
            ->orderBy('billing_month', 'asc')
            ->get();

        // Group by month/year and pick 'working' if present, else 'pdf'
        $grouped = $records->groupBy(fn ($r) => "{$r->billing_year}_{$r->billing_month}");
        $unitsCollection = collect();

        foreach ($grouped as $periodRecords) {
            $working = $periodRecords->firstWhere('reading_source', 'working');
            $pdf = $periodRecords->firstWhere('reading_source', 'pdf');

            $selected = $working ?: $pdf;
            if ($selected) {
                $units = $selected->getEffectiveUnits();
                if ($units > 0) {
                    $unitsCollection->push([
                        'units' => $units,
                        'basis' => $selected->billing_basis ?: 'OK',
                        'source' => $selected->reading_source,
                        'month' => $selected->billing_month,
                        'year' => $selected->billing_year,
                        'is_spike' => $selected->isSpike(),
                        'active_for_average' => $selected->isActiveForAverage(),
                        'meta' => $selected->meta,
                    ]);
                }
            }
        }

        return $unitsCollection;
    }
}
