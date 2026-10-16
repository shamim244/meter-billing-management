<?php

namespace App\Services;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\MeterReadingHistory;
use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class SmartAverageCalculationService
{
    /**
     * Resolve the prior reading for a bill period from clean DB history or Master ledger.
     *
     * @return array{reading: int|null, reading_str: string, label: string, source: string}
     */
    public function resolvePreviousReading(
        string $caNumber,
        int $month,
        int $year,
        ?BillRecord $currentBill = null,
        ?ConsumerAccount $consumer = null,
        ?Collection $history = null,
        ?Collection $meterHistory = null
    ): array {
        // 1. Look for strictly preceding record in local DB history
        $prevRecord = null;
        if ($history && $history->isNotEmpty()) {
            $prevRecord = $history->first(function ($h) use ($month, $year) {
                return ($h->billing_year < $year) || ($h->billing_year == $year && $h->billing_month < $month);
            });
        }

        if ($prevRecord) {
            $priorMonthName = $prevRecord->bill_month_label ?: date('M, Y', mktime(0, 0, 0, $prevRecord->billing_month, 1, $prevRecord->billing_year));

            if (! empty($prevRecord->working_reading) && is_numeric($prevRecord->working_reading)) {
                $r = (int) $prevRecord->working_reading;

                return [
                    'reading' => $r,
                    'reading_str' => (string) $r,
                    'label' => "From {$priorMonthName} (Working)",
                    'source' => 'working',
                ];
            }

            if (! empty($prevRecord->current_reading) && is_numeric($prevRecord->current_reading)) {
                $r = (int) $prevRecord->current_reading;

                return [
                    'reading' => $r,
                    'reading_str' => (string) $r,
                    'label' => "From {$priorMonthName} (PDF)",
                    'source' => 'pdf',
                ];
            }

            if (! empty($prevRecord->previous_reading) && is_numeric($prevRecord->previous_reading)) {
                $r = (int) $prevRecord->previous_reading;

                return [
                    'reading' => $r,
                    'reading_str' => (string) $r,
                    'label' => "From {$priorMonthName} (Baseline)",
                    'source' => 'baseline_bill',
                ];
            }
        }

        // 2. Check dedicated MeterReadingHistory table for strictly preceding month
        $userId = $currentBill?->user_id ?? ($consumer?->user_id ?? Auth::id());
        if ($userId) {
            $prevHist = null;
            if ($meterHistory !== null) {
                $prevHist = $meterHistory->first(function ($h) use ($month, $year) {
                    return ($h->billing_year < $year) || ($h->billing_year == $year && $h->billing_month < $month);
                });
            } else {
                $prevHist = MeterReadingHistory::where('user_id', $userId)
                    ->where('ca_number', $caNumber)
                    ->where(function ($q) use ($month, $year) {
                        $q->where('billing_year', '<', $year)
                            ->orWhere(function ($q2) use ($month, $year) {
                                $q2->where('billing_year', $year)
                                    ->where('billing_month', '<', $month);
                            });
                    })
                    ->orderBy('billing_year', 'desc')
                    ->orderBy('billing_month', 'desc')
                    ->first();
            }

            if ($prevHist) {
                $effectiveR = $prevHist->getEffectiveReading();
                if ($effectiveR !== null && $effectiveR > 0) {
                    $priorMonthName = $prevHist->getMonthLabel();

                    return [
                        'reading' => $effectiveR,
                        'reading_str' => (string) $effectiveR,
                        'label' => "From {$priorMonthName} (".ucfirst($prevHist->reading_source).')',
                        'source' => "history_{$prevHist->reading_source}",
                    ];
                }
            }
        }

        // 3. First cycle in DB or no prior bill: check current bill's previous_reading or Master Ledger
        if ($currentBill && ! empty($currentBill->previous_reading) && is_numeric($currentBill->previous_reading)) {
            $r = (int) $currentBill->previous_reading;
            $priorMonthNum = $month == 1 ? 12 : $month - 1;
            $priorYearNum = $month == 1 ? $year - 1 : $year;
            $priorMonthName = date('M, Y', mktime(0, 0, 0, $priorMonthNum, 1, $priorYearNum));

            return [
                'reading' => $r,
                'reading_str' => (string) $r,
                'label' => "From {$priorMonthName} (PDF Baseline)",
                'source' => 'baseline_bill',
            ];
        }

        if ($consumer && ! empty($consumer->last_working_reading) && is_numeric($consumer->last_working_reading)) {
            $r = (int) $consumer->last_working_reading;
            $m = $consumer->last_working_month ?: ($month == 1 ? 12 : $month - 1);
            $y = $consumer->last_working_year ?: ($month == 1 ? $year - 1 : $year);

            return [
                'reading' => $r,
                'reading_str' => (string) $r,
                'label' => "From Ledger ({$m}/{$y})",
                'source' => 'ledger_working',
            ];
        }

        if ($consumer && ! empty($consumer->baseline_previous_reading) && is_numeric($consumer->baseline_previous_reading)) {
            $r = (int) $consumer->baseline_previous_reading;

            return [
                'reading' => $r,
                'reading_str' => (string) $r,
                'label' => 'From Ledger Baseline',
                'source' => 'ledger_baseline',
            ];
        }

        return [
            'reading' => null,
            'reading_str' => '—',
            'label' => 'Initial Cycle Baseline',
            'source' => 'unresolved',
        ];
    }

    /**
     * Compute clean historical delta consumption units and smart average.
     *
     * @return array{avg_units: int, label: string, range: string, basis: string}
     */
    public function calculateSmartAverage(
        string $caNumber,
        int $month,
        int $year,
        string $basis = 'OK',
        ?BillRecord $currentBill = null,
        ?ConsumerAccount $consumer = null,
        ?Collection $history = null,
        float|int $tuningPercent = 0,
        array $tuningSteps = [],
        ?Collection $meterHistory = null,
        array $guardrails = []
    ): array {
        // Collect strictly historical months (strictly prior to current cycle)
        $priorHistory = collect();
        if ($history && $history->isNotEmpty()) {
            $priorHistory = $history->filter(function ($h) use ($month, $year) {
                return ($h->billing_year < $year) || ($h->billing_year == $year && $h->billing_month < $month);
            })->values();
        }

        $periodMap = []; // key: "YYYY_MM" => ['units' => int, 'basis' => string]

        // 1. Seed from bill_records history
        for ($i = 0; $i < $priorHistory->count(); $i++) {
            $h = $priorHistory[$i];
            $hBasis = strtoupper(trim((string) ($h->billing_basis ?: 'OK')));
            $pKey = sprintf('%04d_%02d', $h->billing_year, $h->billing_month);

            $units = 0;
            if ($h->units_consumed && $h->units_consumed > 0) {
                $units = (int) $h->units_consumed;
            } else {
                $rCurr = is_numeric($h->working_reading) ? (int) $h->working_reading : (is_numeric($h->current_reading) ? (int) $h->current_reading : null);
                $rPrev = is_numeric($h->previous_reading) ? (int) $h->previous_reading : null;

                if ($rCurr !== null && $rPrev !== null && $rCurr >= $rPrev) {
                    $units = $rCurr - $rPrev;
                } elseif ($rCurr !== null && isset($priorHistory[$i + 1])) {
                    $nextH = $priorHistory[$i + 1];
                    $rNext = is_numeric($nextH->working_reading) ? (int) $nextH->working_reading : (is_numeric($nextH->current_reading) ? (int) $nextH->current_reading : null);
                    if ($rNext !== null && $rCurr >= $rNext) {
                        $units = $rCurr - $rNext;
                    }
                } elseif ($rCurr !== null && $consumer && ! empty($consumer->baseline_previous_reading) && is_numeric($consumer->baseline_previous_reading)) {
                    $rBase = (int) $consumer->baseline_previous_reading;
                    if ($rCurr >= $rBase) {
                        $units = $rCurr - $rBase;
                    }
                }
            }

            $hasUnitsData = ($h->units_consumed !== null) || ($rCurr !== null && $rPrev !== null);
            if ($hasUnitsData && $units >= 0) {
                $periodMap[$pKey] = ['units' => $units, 'basis' => $hBasis];
            }
        }

        // 2. Primary source of truth: overlay from dedicated meter_reading_histories database table
        $userId = $currentBill?->user_id ?: ($consumer?->user_id ?: null);
        if ($userId) {
            if ($meterHistory !== null) {
                $mrhRecords = $meterHistory->filter(function ($h) use ($month, $year) {
                    return ($h->billing_year < $year) || ($h->billing_year == $year && $h->billing_month < $month);
                })->groupBy(fn ($r) => sprintf('%04d_%02d', $r->billing_year, $r->billing_month));
            } else {
                $mrhRecords = MeterReadingHistory::where('user_id', $userId)
                    ->where('ca_number', $caNumber)
                    ->where(function ($q) use ($month, $year) {
                        $q->where('billing_year', '<', $year)
                            ->orWhere(function ($q2) use ($month, $year) {
                                $q2->where('billing_year', $year)
                                    ->where('billing_month', '<', $month);
                            });
                    })
                    ->orderBy('billing_year', 'desc')
                    ->orderBy('billing_month', 'desc')
                    ->get()
                    ->groupBy(fn ($r) => sprintf('%04d_%02d', $r->billing_year, $r->billing_month));
            }

            if ($mrhRecords->isNotEmpty()) {
                foreach ($mrhRecords as $pKey => $periodGroup) {
                    $working = $periodGroup->firstWhere('reading_source', 'working');
                    $pdf = $periodGroup->firstWhere('reading_source', 'pdf');
                    $chosen = $working ?: $pdf;
                    if ($chosen) {
                        $u = $chosen->getEffectiveUnits();
                        $b = strtoupper(trim((string) ($chosen->billing_basis ?: 'OK')));
                        // Dedicated ledger takes priority for the period
                        $periodMap[$pKey] = ['units' => $u, 'basis' => $b];
                    }
                }
            }
        }

        // Check system filter toggles and thresholds
        $calculationFilterEnabled = (bool) SystemSetting::get('calculation_filter_enabled', true);
        $filterNonOkBases = (bool) SystemSetting::get('filter_non_ok_bases', true);
        $filterZeroUnits = (bool) SystemSetting::get('filter_zero_unit_months', true);

        // Resolve consumer tariff category
        $tariffCategory = $consumer?->tariff_category
            ?? $currentBill?->tariff_category
            ?? ConsumerAccount::where('ca_number', $caNumber)->value('tariff_category')
            ?? 'DS1D';
        $tariffCategory = strtoupper(trim((string) $tariffCategory));

        // Determine category-specific spike multiplier & agricultural bypass
        $isAgriculture = str_contains($tariffCategory, 'IAS');
        $isCommercial = str_contains($tariffCategory, 'NDS');
        $isDomestic = ! $isCommercial && (str_contains($tariffCategory, 'DS') || str_contains($tariffCategory, 'KJ') || str_contains($tariffCategory, 'KUTIR') || str_contains($tariffCategory, 'JYOTI'));

        $minBuffer = (int) SystemSetting::get('min_spike_unit_buffer', 30);
        $bypassSpikeFilter = false;
        $multiplier = (float) SystemSetting::get('global_spike_multiplier', 2.0);

        if ($isAgriculture) {
            $agriBypass = (bool) SystemSetting::get('agriculture_spike_bypass', true);
            if ($agriBypass) {
                $bypassSpikeFilter = true;
            }
            $multiplier = (float) SystemSetting::get('agriculture_spike_multiplier', 4.0);
        } elseif ($isCommercial) {
            $multiplier = (float) SystemSetting::get('commercial_spike_multiplier', 2.5);
        } elseif ($isDomestic) {
            $multiplier = (float) SystemSetting::get('domestic_spike_multiplier', 2.0);
        }

        // Separate deduplicated database historical periods into basis streams
        $candidateOkUnits = collect();
        $lkUnits = collect();
        $filteredBasesCount = 0;
        $spikesFilteredCount = 0;

        foreach ($periodMap as $pKey => $pData) {
            $u = $pData['units'];
            $b = strtoupper(trim((string) $pData['basis']));
            $isOk = in_array($b, ['OK', 'NORMAL', 'NORMAL(OK)']);

            if ($u <= 0 && $calculationFilterEnabled && $filterZeroUnits) {
                continue;
            }

            if (! $isOk) {
                if ($calculationFilterEnabled && $filterNonOkBases) {
                    $filteredBasesCount++;
                    if ($b === 'LK') {
                        $lkUnits->push($u);
                    }
                } else {
                    $candidateOkUnits->push($u);
                }
            } else {
                $candidateOkUnits->push($u);
            }
        }

        $activeRecord = $currentBill ?: $history?->first(fn ($h) => (int) ($h->billing_month ?? 0) === $month && (int) ($h->billing_year ?? 0) === $year);
        $cleanBasis = strtoupper(trim((string) ($basis ?: 'OK')));

        if ($activeRecord && $activeRecord->units_consumed && (int) $activeRecord->units_consumed > 0 && ! empty($activeRecord->current_reading)) {
            $currUnits = (int) $activeRecord->units_consumed;
            $isOk = in_array($cleanBasis, ['OK', 'NORMAL', 'NORMAL(OK)']);
            if ($isOk) {
                $candidateOkUnits->push($currUnits);
            } else {
                if ($calculationFilterEnabled && $filterNonOkBases) {
                    $filteredBasesCount++;
                    if ($cleanBasis === 'LK') {
                        $lkUnits->push($currUnits);
                    }
                } else {
                    $candidateOkUnits->push($currUnits);
                }
            }
        }

        // Compute clean median from OK months and filter out extreme spikes
        $cleanOkUnits = collect();
        $rawMedian = null;

        if ($candidateOkUnits->isNotEmpty()) {
            $rawMedian = (float) $candidateOkUnits->median();

            if ($calculationFilterEnabled && ! $bypassSpikeFilter) {
                foreach ($candidateOkUnits as $u) {
                    $isSpike = ($rawMedian > 0) && ($u > ($multiplier * $rawMedian)) && (($u - $rawMedian) >= $minBuffer);
                    if ($isSpike) {
                        $spikesFilteredCount++;
                    } else {
                        $cleanOkUnits->push($u);
                    }
                }

                // Fallback if all values were flagged as spikes
                if ($cleanOkUnits->isEmpty()) {
                    $cleanOkUnits = $candidateOkUnits;
                }
            } else {
                $cleanOkUnits = $candidateOkUnits;
            }
        }

        // Units from current bill if present
        $currentBillUnits = 0;
        if ($activeRecord) {
            if ($activeRecord->units_consumed > 0) {
                $currentBillUnits = (int) $activeRecord->units_consumed;
            } else {
                $w = is_numeric($activeRecord->working_reading) ? (int) $activeRecord->working_reading : (is_numeric($activeRecord->current_reading) ? (int) $activeRecord->current_reading : 0);
                $p = is_numeric($activeRecord->previous_reading) ? (int) $activeRecord->previous_reading : 0;
                if ($w > 0 && $p > 0 && $w >= $p) {
                    $currentBillUnits = $w - $p;
                }
            }
        }
        // Guardrails & Exclusion Resolution
        $customAvg = isset($guardrails['custom_avg_units']) && is_numeric($guardrails['custom_avg_units'])
            ? (int) $guardrails['custom_avg_units']
            : ($consumer?->custom_avg_units ?? $currentBill?->custom_avg_units ?? null);

        $isCustomOverride = ($customAvg !== null && $customAvg > 0);

        $minFloor = isset($guardrails['min_units_floor']) && is_numeric($guardrails['min_units_floor'])
            ? (int) $guardrails['min_units_floor']
            : (int) SystemSetting::get('smart_avg_min_floor', 0);

        $maxCeiling = isset($guardrails['max_units_ceiling']) && is_numeric($guardrails['max_units_ceiling'])
            ? (int) $guardrails['max_units_ceiling']
            : (int) SystemSetting::get('smart_avg_max_ceiling', 0);

        $isOverPosted = (bool) ($guardrails['is_over_posted'] ?? false);
        $autoExcludeOverposted = array_key_exists('auto_exclude_overposted', $guardrails)
            ? (bool) $guardrails['auto_exclude_overposted']
            : (bool) SystemSetting::get('smart_avg_auto_exclude_overposted', true);

        $isCriticalDoubt = (bool) ($guardrails['is_critical_doubt'] ?? false);
        $autoExcludeCriticalDoubt = array_key_exists('auto_exclude_critical_doubt', $guardrails)
            ? (bool) $guardrails['auto_exclude_critical_doubt']
            : (bool) SystemSetting::get('smart_avg_auto_exclude_critical_doubt', false);

        $isExplicitlyExcluded = (bool) ($guardrails['is_avg_excluded'] ?? $consumer?->is_avg_excluded ?? $currentBill?->is_avg_excluded ?? false);
        $explicitReason = $guardrails['avg_exclude_reason'] ?? $consumer?->avg_exclude_reason ?? $currentBill?->avg_exclude_reason ?? null;

        $isExcluded = false;
        $excludeReason = null;

        if ($isExplicitlyExcluded) {
            $isExcluded = true;
            $excludeReason = $explicitReason ?: 'Manually Excluded';
        } elseif ($isOverPosted && $autoExcludeOverposted) {
            $isExcluded = true;
            $excludeReason = 'Over-Posted (Physical < Posted)';
        } elseif ($isCriticalDoubt && $autoExcludeCriticalDoubt) {
            $isExcluded = true;
            $excludeReason = 'Critical / Doubt Review Status';
        }

        $avgUnits = 50;
        $avgLabel = '50 kWh (Initial)';
        $avgRange = '42–58 kWh';

        if ($isCustomOverride) {
            $avgUnits = (int) $customAvg;
            $avgLabel = "{$avgUnits} kWh (Custom Override)";
            $minR = max(1, (int) round($avgUnits * 0.85));
            $maxR = (int) round($avgUnits * 1.15);
            $avgRange = "{$minR}–{$maxR} kWh";
        } elseif ($cleanBasis === 'MD') {
            $avgUnits = $currentBillUnits ?: ($currentBill?->units_consumed ?: 76);
            $avgLabel = "{$avgUnits} kWh (MD Assessed)";
            $avgRange = 'Flat Assessed';
        } else {
            if ($cleanOkUnits->isNotEmpty()) {
                $sortedOk = $cleanOkUnits->sort()->values();
                $avgUnits = (int) round($sortedOk->median());
                $avgLabel = "{$avgUnits} kWh (From OK History)";
                $minR = max(1, (int) round($avgUnits * 0.85));
                $maxR = (int) round($avgUnits * 1.15);
                $avgRange = "{$minR}–{$maxR} kWh";
            } elseif ($lkUnits->isNotEmpty()) {
                $avgUnits = (int) round($lkUnits->median());
                $avgLabel = "~{$avgUnits} kWh (LK Approx)";
                $avgRange = 'Provisional';
            } elseif ($currentBillUnits > 0) {
                $avgUnits = (int) $currentBillUnits;
                $avgLabel = "{$avgUnits} kWh (This Bill)";
                $minR = max(1, (int) round($avgUnits * 0.85));
                $maxR = (int) round($avgUnits * 1.15);
                $avgRange = "{$minR}–{$maxR} kWh";
            }
        }

        $baseUnits = $avgUnits;
        $effectiveTuningPercent = 0.0;
        $effectiveSteps = [];
        $isClampedMin = false;
        $isClampedMax = false;

        // Tweaking is an optional add-on: only active when explicitly enabled or tuning steps/percentage provided
        $tuningEnabledFlag = $guardrails['tuning_enabled'] ?? null;
        if ($tuningEnabledFlag === false) {
            $isTuningActive = false;
        } else {
            $isTuningActive = (! empty($tuningSteps) || (float) $tuningPercent != 0.0 || ! empty($tuningEnabledFlag));
        }

        // Apply tweaking overlay (percentage tuning / compounding / min-max clamps) ONLY when tuning is active
        if ($isTuningActive && ! $isCustomOverride && ! $isExcluded) {
            if (! empty($tuningSteps)) {
                $compounded = $this->compoundAdjustAverageUnits($baseUnits, $tuningSteps);
                $avgUnits = $compounded['tuned_units'];
                $effectiveTuningPercent = $compounded['net_percent'];
                $effectiveSteps = $compounded['steps'];
                $pctSign = $effectiveTuningPercent >= 0 ? "+{$effectiveTuningPercent}%" : "{$effectiveTuningPercent}%";
                $avgLabel = "{$avgUnits} kWh ({$baseUnits} {$pctSign})";
                $minR = max(1, (int) round($avgUnits * 0.85));
                $maxR = (int) round($avgUnits * 1.15);
                $avgRange = "{$minR}–{$maxR} kWh";
            } elseif ($tuningPercent != 0) {
                $avgUnits = $this->adjustAverageUnits($baseUnits, (float) $tuningPercent);
                $effectiveTuningPercent = (float) $tuningPercent;
                $pctSign = $effectiveTuningPercent >= 0 ? "+{$effectiveTuningPercent}%" : "{$effectiveTuningPercent}%";
                $avgLabel = "{$avgUnits} kWh ({$baseUnits} {$pctSign})";
                $minR = max(1, (int) round($avgUnits * 0.85));
                $maxR = (int) round($avgUnits * 1.15);
                $avgRange = "{$minR}–{$maxR} kWh";
            }

            // Apply Minimum Floor & Maximum Ceiling Guardrails ONLY within active tweaking
            if ($minFloor > 0 && $avgUnits < $minFloor) {
                $avgUnits = $minFloor;
                $isClampedMin = true;
                $avgLabel = "{$avgUnits} kWh (Min Floor Clamped)";
            }

            if ($maxCeiling > 0 && $avgUnits > $maxCeiling) {
                $avgUnits = $maxCeiling;
                $isClampedMax = true;
                $avgLabel = "{$avgUnits} kWh (Max Ceiling Clamped)";
            }
        }

        if ($isExcluded) {
            $avgLabel = "{$avgUnits} kWh ({$excludeReason})";
        }

        return [
            'avg_units' => $avgUnits,
            'base_units' => $baseUnits,
            'tuning_percent' => $effectiveTuningPercent,
            'tuning_steps' => $effectiveSteps,
            'label' => $avgLabel,
            'range' => $avgRange,
            'basis' => $cleanBasis,
            'spikes_filtered_count' => $spikesFilteredCount,
            'filtered_bases_count' => $filteredBasesCount,
            'clean_units_count' => $cleanOkUnits->count(),
            'raw_median' => $rawMedian,
            'tariff_category' => $tariffCategory,
            'multiplier_used' => $multiplier,
            'min_buffer_applied' => $minBuffer,
            'is_agriculture_bypass' => $bypassSpikeFilter,
            'is_excluded' => $isExcluded,
            'exclude_reason' => $excludeReason,
            'is_custom_override' => $isCustomOverride,
            'is_clamped_min' => $isClampedMin,
            'is_clamped_max' => $isClampedMax,
            'min_floor_applied' => $minFloor,
            'max_ceiling_applied' => $maxCeiling,
        ];
    }

    /**
     * Adjust average units by a percentage (+% or -%).
     * Example: 50 kWh with +20% = 60 kWh, or with -10% = 45 kWh.
     */
    public function adjustAverageUnits(int $avgUnits, float|int $adjustmentPercent = 0): int
    {
        if ($adjustmentPercent == 0) {
            return $avgUnits;
        }

        $adjusted = (int) round($avgUnits * (1.0 + ($adjustmentPercent / 100.0)));

        return max(1, $adjusted);
    }

    /**
     * Compute sequentially compounded percentage adjustments.
     * Example: Base 50 with [-20, +10] => Step 1: 50 * 0.80 = 40, Step 2: 40 * 1.10 = 44 kWh.
     *
     * @param  array<int, float|int>  $percentageSteps
     * @return array{base_units: int, tuned_units: int, net_percent: float, steps: array}
     */
    public function compoundAdjustAverageUnits(int $baseUnits, array $percentageSteps = []): array
    {
        $current = (float) max(1, $baseUnits);
        $stepDetails = [];

        foreach ($percentageSteps as $pct) {
            $pctVal = (float) $pct;
            if ($pctVal == 0.0) {
                continue;
            }

            $before = $current;
            $current = $current * (1.0 + ($pctVal / 100.0));
            $stepDetails[] = [
                'percent' => $pctVal,
                'before' => round($before, 1),
                'after' => round($current, 1),
            ];
        }

        $tuned = max(1, (int) round($current));
        $netPercent = $baseUnits > 0 ? round((($tuned - $baseUnits) / $baseUnits) * 100.0, 1) : 0.0;

        return [
            'base_units' => $baseUnits,
            'tuned_units' => $tuned,
            'net_percent' => $netPercent,
            'steps' => $stepDetails,
        ];
    }

    /**
     * Compute projected reading using dual-anchor arbitrage:
     * Base Anchor = max(Previous Reading, Official PDF Reading)
     * Working Reading = Base Anchor + Tuned Average Units.
     */
    public function calculateProjectedReading(?int $prevReading, int $avgUnits, ?int $officialPdfReading = null, float|int $adjustmentPercent = 0): int
    {
        $effectiveUnits = $this->adjustAverageUnits($avgUnits, $adjustmentPercent);

        $prev = ($prevReading !== null && $prevReading > 0) ? $prevReading : 0;
        $pdf = ($officialPdfReading !== null && $officialPdfReading > 0) ? $officialPdfReading : 0;

        $anchor = max($prev, $pdf);

        return ($anchor > 0) ? ($anchor + $effectiveUnits) : $effectiveUnits;
    }

    /**
     * Resolve previous reading, smart average, projected cumulative reading, and effective working reading.
     * Guaranteed: Parity with Web Dashboard, PDF arbitrage, protection against delta-units in working reading.
     *
     * @return array{
     *     previous_reading: int,
     *     smart_average_kwh: int,
     *     projected_reading: int,
     *     working_reading: int|null,
     *     suggested_reading: int
     * }
     */
    public function resolveWorkingAndSuggestedReading(
        BillRecord $bill,
        ?ConsumerAccount $consumer = null,
        ?Collection $history = null,
        ?Collection $meterHistory = null,
        float|int $adjPercent = 0,
        array $tuningSteps = []
    ): array {
        $month = (int) ($bill->billing_month ?? now()->month);
        $year = (int) ($bill->billing_year ?? now()->year);

        // 1. Resolve previous reading using dashboard's exact resolution ladder:
        // Preceding bill working/pdf/prev reading -> MeterReadingHistory -> current bill previous_reading -> Consumer ledger
        $resolvedPrev = $this->resolvePreviousReading(
            $bill->ca_number,
            $month,
            $year,
            $bill,
            $consumer,
            $history,
            $meterHistory
        );

        $prev = ($resolvedPrev['reading'] !== null && $resolvedPrev['reading'] > 0)
            ? (int) $resolvedPrev['reading']
            : (is_numeric($bill->previous_reading) && (int) $bill->previous_reading > 0 ? (int) $bill->previous_reading : 0);

        // 2. Resolve Smart Average (monthly delta units) using dashboard's exact outlier-proof calculation
        $basis = $bill->billing_basis ?: ($consumer?->billing_basis ?: 'OK');
        $avgCalc = $this->calculateSmartAverage(
            $bill->ca_number,
            $month,
            $year,
            $basis,
            $bill,
            $consumer,
            $history,
            $adjPercent,
            $tuningSteps,
            $meterHistory
        );
        $avgUnits = max(1, (int) ($avgCalc['avg_units'] ?? 50));

        // 3. Projected cumulative meter dial reading (Arbitrage: max(Prev, PDF) + Tuned Avg)
        // Pass 0 as adjustmentPercent because calculateSmartAverage() already applied $adjPercent / $tuningSteps!
        $pdfNum = (! empty($bill->current_reading) && is_numeric($bill->current_reading))
            ? (int) $bill->current_reading
            : null;

        $hasAnchor = ($prev > 0) || ($pdfNum !== null && $pdfNum > 0);
        $projectedReading = $hasAnchor
            ? $this->calculateProjectedReading($prev, $avgUnits, $pdfNum, 0)
            : 0;

        // 4. Effective working reading & suggested reading
        $dbWorking = (is_numeric($bill->working_reading) && $bill->working_reading !== '')
            ? (int) $bill->working_reading
            : null;

        $readingSource = $bill->reading_source ?: 'auto';
        $isManual = ($readingSource === 'manual');

        if ($isManual && $dbWorking !== null && ($prev <= 0 || $dbWorking >= $prev)) {
            $effectiveWorking = $dbWorking;
            $suggested = $dbWorking;
        } else {
            // Auto-projected or corrupt delta: if empty, 0, less than projected, or <= previous reading (units entered instead of cumulative dial)
            if ($dbWorking === null || $dbWorking <= 0 || ($projectedReading > 0 && $dbWorking < $projectedReading) || ($prev > 0 && $dbWorking <= $prev)) {
                $effectiveWorking = ($projectedReading > 0) ? $projectedReading : $dbWorking;
            } else {
                $effectiveWorking = $dbWorking;
            }

            $suggested = ($projectedReading > 0)
                ? $projectedReading
                : ($effectiveWorking ?? ($prev > 0 ? $prev + $avgUnits : 0));
        }

        return [
            'previous_reading' => $prev,
            'smart_average_kwh' => $avgUnits,
            'projected_reading' => $projectedReading,
            'working_reading' => $effectiveWorking,
            'suggested_reading' => $suggested,
        ];
    }
}
