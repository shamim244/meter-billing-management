<?php

namespace App\Services;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use Illuminate\Support\Collection;

class SmartAverageCalculationService
{
    /**
     * Resolve the prior reading for a bill period from clean DB history or Master ledger.
     *
     * @param string $caNumber
     * @param int $month
     * @param int $year
     * @param BillRecord|null $currentBill
     * @param ConsumerAccount|null $consumer
     * @param Collection|null $history
     * @return array{reading: int|null, reading_str: string, label: string, source: string}
     */
    public function resolvePreviousReading(
        string $caNumber,
        int $month,
        int $year,
        ?BillRecord $currentBill = null,
        ?ConsumerAccount $consumer = null,
        ?Collection $history = null
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

            if (!empty($prevRecord->working_reading) && is_numeric($prevRecord->working_reading)) {
                $r = (int) $prevRecord->working_reading;
                return [
                    'reading' => $r,
                    'reading_str' => (string) $r,
                    'label' => "From {$priorMonthName} (Working)",
                    'source' => 'working',
                ];
            }

            if (!empty($prevRecord->current_reading) && is_numeric($prevRecord->current_reading)) {
                $r = (int) $prevRecord->current_reading;
                return [
                    'reading' => $r,
                    'reading_str' => (string) $r,
                    'label' => "From {$priorMonthName} (PDF)",
                    'source' => 'pdf',
                ];
            }

            if (!empty($prevRecord->previous_reading) && is_numeric($prevRecord->previous_reading)) {
                $r = (int) $prevRecord->previous_reading;
                return [
                    'reading' => $r,
                    'reading_str' => (string) $r,
                    'label' => "From {$priorMonthName} (Baseline)",
                    'source' => 'baseline_bill',
                ];
            }
        }

        // 2. First cycle in DB or no prior bill: check current bill's previous_reading or Master Ledger
        if ($currentBill && !empty($currentBill->previous_reading) && is_numeric($currentBill->previous_reading)) {
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

        if ($consumer && !empty($consumer->last_working_reading) && is_numeric($consumer->last_working_reading)) {
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

        if ($consumer && !empty($consumer->baseline_previous_reading) && is_numeric($consumer->baseline_previous_reading)) {
            $r = (int) $consumer->baseline_previous_reading;
            return [
                'reading' => $r,
                'reading_str' => (string) $r,
                'label' => "From Ledger Baseline",
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
     * @param string $caNumber
     * @param int $month
     * @param int $year
     * @param string $basis
     * @param BillRecord|null $currentBill
     * @param ConsumerAccount|null $consumer
     * @param Collection|null $history
     * @return array{avg_units: int, label: string, range: string, basis: string}
     */
    public function calculateSmartAverage(
        string $caNumber,
        int $month,
        int $year,
        string $basis = 'OK',
        ?BillRecord $currentBill = null,
        ?ConsumerAccount $consumer = null,
        ?Collection $history = null
    ): array {
        // Collect strictly historical months (strictly prior to current cycle)
        $priorHistory = collect();
        if ($history && $history->isNotEmpty()) {
            $priorHistory = $history->filter(function ($h) use ($month, $year) {
                return ($h->billing_year < $year) || ($h->billing_year == $year && $h->billing_month < $month);
            })->values();
        }

        $okUnits = collect();
        $lkUnits = collect();

        for ($i = 0; $i < $priorHistory->count(); $i++) {
            $h = $priorHistory[$i];
            $hBasis = strtoupper(trim((string)($h->billing_basis ?: 'OK')));

            $units = 0;
            if ($h->units_consumed && $h->units_consumed > 0) {
                $units = (int) $h->units_consumed;
            } else {
                $rCurr = is_numeric($h->working_reading) ? (int)$h->working_reading : (is_numeric($h->current_reading) ? (int)$h->current_reading : null);
                $rPrev = is_numeric($h->previous_reading) ? (int)$h->previous_reading : null;

                if ($rCurr !== null && $rPrev !== null && $rCurr >= $rPrev) {
                    $units = $rCurr - $rPrev;
                } elseif ($rCurr !== null && isset($priorHistory[$i + 1])) {
                    $nextH = $priorHistory[$i + 1];
                    $rNext = is_numeric($nextH->working_reading) ? (int)$nextH->working_reading : (is_numeric($nextH->current_reading) ? (int)$nextH->current_reading : null);
                    if ($rNext !== null && $rCurr >= $rNext) {
                        $units = $rCurr - $rNext;
                    }
                } elseif ($rCurr !== null && $consumer && !empty($consumer->baseline_previous_reading) && is_numeric($consumer->baseline_previous_reading)) {
                    $rBase = (int) $consumer->baseline_previous_reading;
                    if ($rCurr >= $rBase) {
                        $units = $rCurr - $rBase;
                    }
                }
            }

            if ($units > 0) {
                if ($hBasis === 'OK') {
                    $okUnits->push($units);
                } elseif ($hBasis === 'LK') {
                    $lkUnits->push($units);
                }
            }
        }

        $activeRecord = $currentBill ?: $history?->first(fn($h) => (int)($h->billing_month ?? 0) === $month && (int)($h->billing_year ?? 0) === $year);
        $cleanBasis = strtoupper(trim((string)($basis ?: 'OK')));

        if ($activeRecord && $activeRecord->units_consumed && (int)$activeRecord->units_consumed > 0) {
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
                $w = is_numeric($activeRecord->working_reading) ? (int)$activeRecord->working_reading : (is_numeric($activeRecord->current_reading) ? (int)$activeRecord->current_reading : 0);
                $p = is_numeric($activeRecord->previous_reading) ? (int)$activeRecord->previous_reading : 0;
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

        return [
            'avg_units' => $avgUnits,
            'label' => $avgLabel,
            'range' => $avgRange,
            'basis' => $cleanBasis,
        ];
    }

    /**
     * Compute projected reading enforcing official PDF floor invariant.
     */
    public function calculateProjectedReading(?int $prevReading, int $avgUnits, ?int $officialPdfReading = null): int
    {
        $projected = ($prevReading !== null && $prevReading > 0) ? ($prevReading + $avgUnits) : $avgUnits;

        if ($officialPdfReading !== null && $officialPdfReading > 0 && $projected < $officialPdfReading) {
            $projected = $officialPdfReading;
        }

        return $projected;
    }
}
