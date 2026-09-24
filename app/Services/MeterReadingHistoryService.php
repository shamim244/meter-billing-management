<?php

namespace App\Services;

use App\Models\BillRecord;
use App\Models\MeterReadingHistory;
use Illuminate\Support\Collection;

class MeterReadingHistoryService
{
    /**
     * Record or update historical entry from an official PDF bill parse.
     * Also captures decoded prior month baseline reading and all 12-month consumption history ([kir fooj.kh).
     */
    public function recordFromPdf(BillRecord $bill, array $extractedHistory = []): MeterReadingHistory
    {
        $prevMonth = $bill->billing_month == 1 ? 12 : ((int) $bill->billing_month - 1);
        $prevYear = $bill->billing_month == 1 ? ((int) $bill->billing_year - 1) : (int) $bill->billing_year;

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
                ]);
            }
        }

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
                        $existing->save();
                    }
                }

                $runningReading = $prevR;
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
    public function getConsumerMonthlyMatrix(int $userId, string $caNumber, ?int $year = null): array
    {
        $query = MeterReadingHistory::with('mru')
            ->where('user_id', $userId)
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
                    ]);
                }
            }
        }

        return $unitsCollection;
    }
}
