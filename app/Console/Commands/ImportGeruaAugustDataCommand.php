<?php

namespace App\Console\Commands;

use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\User;
use App\Models\Mru;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportGeruaAugustDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:gerua-august 
                            {--email=shasmim244d@gmail.com : User email}
                            {--month=8 : Target billing month (default: 8 for August)}
                            {--year=2026 : Target billing year (default: 2026)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import August working readings and statuses for Gerua (0477) from migration directory';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email');
        $month = (int) $this->option('month');
        $year = (int) $this->option('year');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("User not found for email: {$email}");
            return Command::FAILURE;
        }

        $baseDir = base_path('.agents/docs/Migrate/Gerua-0477');
        $billDataPath = $baseDir . '/bill_data.json';
        $statusesPath = $baseDir . '/statuses.json';

        if (!file_exists($billDataPath)) {
            $this->error("bill_data.json not found at: {$billDataPath}");
            return Command::FAILURE;
        }

        if (!file_exists($statusesPath)) {
            $this->error("statuses.json not found at: {$statusesPath}");
            return Command::FAILURE;
        }

        $billData = json_decode(file_get_contents($billDataPath), true);
        $statusesData = json_decode(file_get_contents($statusesPath), true);

        $this->info("Starting Gerua August migration for User #{$user->id} ({$user->email})...");
        $this->info("  • bill_data.json records: " . count($billData));
        $this->info("  • statuses.json records: " . count($statusesData));

        $workingReadingsUpdated = 0;
        $statusesUpdated = 0;
        $consumersUpdated = 0;

        DB::transaction(function () use (
            $user,
            $month,
            $year,
            $billData,
            $statusesData,
            &$workingReadingsUpdated,
            &$statusesUpdated,
            &$consumersUpdated
        ) {
            // 1. Update Working Readings from bill_data.json
            foreach ($billData as $caNumber => $data) {
                $caStr = trim((string) $caNumber);
                $workingReading = isset($data['working_reading']) ? trim((string) $data['working_reading']) : null;

                if ($workingReading !== null && $workingReading !== '') {
                    $bills = BillRecord::where('user_id', $user->id)
                        ->where('ca_number', $caStr)
                        ->where('billing_month', $month)
                        ->when($year > 0, fn($q) => $q->where('billing_year', $year))
                        ->get();

                    foreach ($bills as $bill) {
                        $bill->working_reading = $workingReading;
                        $bill->save();
                        $workingReadingsUpdated++;
                    }

                    // Update ConsumerAccount master reading ledger
                    $consumers = ConsumerAccount::where('user_id', $user->id)
                        ->where('ca_number', $caStr)
                        ->get();

                    foreach ($consumers as $consumer) {
                        $consumer->last_working_reading = $workingReading;
                        $consumer->last_working_month = $month;
                        $consumer->last_working_year = $year;
                        $consumer->save();
                        $consumersUpdated++;
                    }
                }
            }

            // 2. Update Statuses from statuses.json
            foreach ($statusesData as $caNumber => $status) {
                $caStr = trim((string) $caNumber);
                $statusStr = strtolower(trim((string) $status));

                $bills = BillRecord::where('user_id', $user->id)
                    ->where('ca_number', $caStr)
                    ->where('billing_month', $month)
                    ->when($year > 0, fn($q) => $q->where('billing_year', $year))
                    ->get();

                foreach ($bills as $bill) {
                    $bill->review_status = $statusStr;
                    $bill->save();
                    $statusesUpdated++;

                    // Synchronize with BillStatus record
                    BillStatus::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'ca_number' => $caStr,
                            'billing_month' => $bill->billing_month,
                            'billing_year' => $bill->billing_year,
                        ],
                        [
                            'status' => $statusStr,
                        ]
                    );
                }
            }
        });

        $this->info("✅ Successfully updated {$workingReadingsUpdated} BillRecord(s) with working_reading.");
        $this->info("✅ Successfully updated {$consumersUpdated} ConsumerAccount ledger(s).");
        $this->info("✅ Successfully synchronized {$statusesUpdated} BillRecord and BillStatus status records.");

        return Command::SUCCESS;
    }
}
