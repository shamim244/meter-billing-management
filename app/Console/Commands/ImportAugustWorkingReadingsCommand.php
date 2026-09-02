<?php

namespace App\Console\Commands;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportAugustWorkingReadingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:august-readings 
                            {--file= : Path to JSON data file}
                            {--month=8 : Target billing month (default: 8 for August)}
                            {--year=2026 : Target billing year (default: 2026)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import August working readings from JSON data file into BillRecords and ConsumerAccounts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->option('file') ?: base_path('.agents/data_user9_76ca.json');
        $month = (int) $this->option('month');
        $year = (int) $this->option('year');

        if (!file_exists($filePath)) {
            $this->error("JSON data file not found at: {$filePath}");
            return Command::FAILURE;
        }

        $rawJson = file_get_contents($filePath);
        $data = json_decode($rawJson, true);

        if (!is_array($data) || empty($data)) {
            $this->error("Invalid or empty JSON data in: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Importing " . count($data) . " working readings for Month: {$month}, Year: {$year}...");

        $billsUpdated = 0;
        $consumersUpdated = 0;
        $missingBills = [];

        DB::transaction(function () use ($data, $month, $year, &$billsUpdated, &$consumersUpdated, &$missingBills) {
            foreach ($data as $caNumber => $workingReadingValue) {
                $caStr = trim((string) $caNumber);
                $valStr = trim((string) $workingReadingValue);

                // 1. Find all matching BillRecords for this CA and month
                $bills = BillRecord::where('ca_number', $caStr)
                    ->where('billing_month', $month)
                    ->when($year > 0, fn($q) => $q->where('billing_year', $year))
                    ->get();

                if ($bills->isEmpty()) {
                    // Fallback to latest bill for this CA if month not strictly matched
                    $bills = BillRecord::where('ca_number', $caStr)
                        ->where('billing_month', $month)
                        ->get();
                }

                if ($bills->isEmpty()) {
                    $missingBills[] = $caStr;
                } else {
                    foreach ($bills as $bill) {
                        $bill->working_reading = $valStr;
                        $bill->save();
                        $billsUpdated++;

                        // Cascade to subsequent cycles if any
                        $subsequentBills = BillRecord::where('user_id', $bill->user_id)
                            ->where('ca_number', $bill->ca_number)
                            ->where(function ($q) use ($bill) {
                                $q->where('billing_year', '>', $bill->billing_year)
                                  ->orWhere(function ($q2) use ($bill) {
                                      $q2->where('billing_year', $bill->billing_year)
                                         ->where('billing_month', '>', $bill->billing_month);
                                  });
                            })
                            ->orderBy('billing_year', 'asc')
                            ->orderBy('billing_month', 'asc')
                            ->get();

                        $numericVal = 0;
                        if (preg_match('/(\d+)/', $valStr, $matches)) {
                            $numericVal = (int) $matches[1];
                        }

                        $currentChainReading = $numericVal;
                        foreach ($subsequentBills as $futureBill) {
                            $futureBill->previous_reading = (string) $currentChainReading;
                            $avgUnits = $futureBill->units_consumed ?: 50;
                            $newProjected = $currentChainReading + $avgUnits;
                            if (!empty($futureBill->current_reading) && is_numeric($futureBill->current_reading)) {
                                $pdfReading = (int) $futureBill->current_reading;
                                if ($newProjected < $pdfReading) {
                                    $newProjected = $pdfReading;
                                }
                            }
                            $futureBill->working_reading = (string) $newProjected;
                            $futureBill->save();
                            $currentChainReading = $newProjected;
                        }
                    }
                }

                // 2. Update Master ConsumerAccount ledger
                $consumers = ConsumerAccount::where('ca_number', $caStr)->get();
                foreach ($consumers as $consumer) {
                    $consumer->last_working_reading = $valStr;
                    $consumer->last_working_month = $month;
                    $consumer->last_working_year = $year;
                    $consumer->save();
                    $consumersUpdated++;
                }
            }
        });

        $this->info("✅ Successfully updated {$billsUpdated} BillRecord(s) with August working readings.");
        $this->info("✅ Successfully updated {$consumersUpdated} ConsumerAccount ledger(s).");

        if (!empty($missingBills)) {
            $this->warn("⚠️ " . count($missingBills) . " CAs had no August BillRecord: " . implode(', ', $missingBills));
        }

        return Command::SUCCESS;
    }
}
