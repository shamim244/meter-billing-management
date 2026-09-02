<?php

namespace App\Console\Commands;

use App\Models\BillRecord;
use App\Models\BillStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportAugustReviewStatusesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:august-statuses 
                            {--file= : Path to JSON status file}
                            {--month=8 : Target billing month (default: 8 for August)}
                            {--year=2026 : Target billing year (default: 2026)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import August review statuses from JSON file into BillRecords and BillStatuses';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->option('file') ?: base_path('.agents/data_user9_76ca_status.json');
        $month = (int) $this->option('month');
        $year = (int) $this->option('year');

        if (!file_exists($filePath)) {
            $this->error("JSON status file not found at: {$filePath}");
            return Command::FAILURE;
        }

        $rawJson = file_get_contents($filePath);
        $data = json_decode($rawJson, true);

        if (!is_array($data) || empty($data)) {
            $this->error("Invalid or empty JSON data in: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Importing " . count($data) . " review statuses for Month: {$month}, Year: {$year}...");

        $billsUpdated = 0;
        $statusesUpdated = 0;
        $missingBills = [];

        DB::transaction(function () use ($data, $month, $year, &$billsUpdated, &$statusesUpdated, &$missingBills) {
            foreach ($data as $caNumber => $status) {
                $caStr = trim((string) $caNumber);
                $statusStr = strtolower(trim((string) $status));

                // 1. Find and update all matching BillRecords for this CA and month
                $bills = BillRecord::where('ca_number', $caStr)
                    ->where('billing_month', $month)
                    ->when($year > 0, fn($q) => $q->where('billing_year', $year))
                    ->get();

                if ($bills->isEmpty()) {
                    $bills = BillRecord::where('ca_number', $caStr)
                        ->where('billing_month', $month)
                        ->get();
                }

                if ($bills->isEmpty()) {
                    $missingBills[] = $caStr;
                } else {
                    foreach ($bills as $bill) {
                        $bill->review_status = $statusStr;
                        $bill->save();
                        $billsUpdated++;

                        // 2. Synchronize with BillStatus record
                        BillStatus::updateOrCreate(
                            [
                                'user_id' => $bill->user_id,
                                'ca_number' => $caStr,
                                'billing_month' => $bill->billing_month,
                                'billing_year' => $bill->billing_year,
                            ],
                            [
                                'status' => $statusStr,
                            ]
                        );
                        $statusesUpdated++;
                    }
                }
            }
        });

        $this->info("✅ Successfully updated {$billsUpdated} BillRecord(s) with August review statuses.");
        $this->info("✅ Successfully synchronized {$statusesUpdated} BillStatus table record(s).");

        if (!empty($missingBills)) {
            $this->warn("⚠️ " . count($missingBills) . " CAs had no August BillRecord: " . implode(', ', $missingBills));
        }

        return Command::SUCCESS;
    }
}
