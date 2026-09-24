<?php

namespace App\Services;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\MeterReadingHistory;
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
        ?Collection $meterHistory = null
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

            if ($units > 0) {
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
                        if ($u > 0) {
                            $b = strtoupper(trim((string) ($chosen->billing_basis ?: 'OK')));
                            // Dedicated ledger takes priority for the period
                            $periodMap[$pKey] = ['units' => $u, 'basis' => $b];
                        }
                    }
                }
            }
        }

        // Separate deduplicated database historical periods into basis streams
        $okUnits = collect();
        $lkUnits = collect();
        foreach ($periodMap as $pData) {
            if ($pData['basis'] === 'OK') {
                $okUnits->push($pData['units']);
            } elseif ($pData['basis'] === 'LK') {
                $lkUnits->push($pData['units']);
            }
        }

        $activeRecord = $currentBill ?: $history?->first(fn ($h) => (int) ($h->billing_month ?? 0) === $month && (int) ($h->billing_year ?? 0) === $year);
        $cleanBasis = strtoupper(trim((string) ($basis ?: 'OK')));

        if ($activeRecord && $activeRecord->units_consumed && (int) $activeRecord->units_consumed > 0 && ! empty($activeRecord->current_reading)) {
            $currUnits = (int) $activeRecord->units_consumed;
            if ($cleanBasis === 'OK') {
                $okUnits->push($currUnits);
            } elseif ($cleanBasis === 'LK') {
                $lkUnits->push($currUnits);
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
        $avgUnits = 50;
        $avgLabel = '50 kWh (Initial)';
        $avgRange = '42–58 kWh';

        if ($cleanBasis === 'MD') {
            $avgUnits = $currentBillUnits ?: ($currentBill?->units_consumed ?: 76);
            $avgLabel = "{$avgUnits} kWh (MD Assessed)";
            $avgRange = 'Flat Assessed';
        } else {
            if ($okUnits->isNotEmpty()) {
                $sortedOk = $okUnits->sort()->values();
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

        // Apply percentage tuning / compounding yield if requested
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

        return [
            'avg_units' => $avgUnits,
            'base_units' => $baseUnits,
            'tuning_percent' => $effectiveTuningPercent,
            'tuning_steps' => $effectiveSteps,
            'label' => $avgLabel,
            'range' => $avgRange,
            'basis' => $cleanBasis,
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
     * Compute projected reading enforcing optional percentage adjustment and official PDF floor invariant.
     */
    public function calculateProjectedReading(?int $prevReading, int $avgUnits, ?int $officialPdfReading = null, float|int $adjustmentPercent = 0): int
    {
        $effectiveUnits = $this->adjustAverageUnits($avgUnits, $adjustmentPercent);
        $projected = ($prevReading !== null && $prevReading > 0) ? ($prevReading + $effectiveUnits) : $effectiveUnits;

        if ($officialPdfReading !== null && $officialPdfReading > 0 && $projected < $officialPdfReading) {
            $projected = $officialPdfReading;
        }

        return $projected;
    }
}
