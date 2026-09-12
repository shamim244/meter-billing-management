<?php

namespace App\Console\Commands;

use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportBasalgaonAugustDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:basalgaon-august 
                            {--email=shamim244d@gmail.com : User email}
                            {--mru=18 : MRU ID (default: 18 for Basalgaon)}
                            {--month=8 : Target billing month (default: 8 for August)}
                            {--year=2026 : Target billing year (default: 2026)}
                            {--dry-run : Run import without saving changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import August working readings and statuses for Basalgaon (0631 / MRU 18) from migration directory';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email');
        $mruId = (int) $this->option('mru');
        $month = (int) $this->option('month');
        $year = (int) $this->option('year');
        $dryRun = (bool) $this->option('dry-run');

        $user = User::where('email', $email)->first();
        if (! $user) {
            $user = User::where('email', 'like', '%shamim244d%')->first();
        }

        if (! $user) {
            $this->error("User not found for email: {$email}");

            return Command::FAILURE;
        }

        $mru = Mru::where('id', $mruId)->where('user_id', $user->id)->first();
        if (! $mru) {
            $mru = Mru::where('code', '0631')->where('user_id', $user->id)->first();
        }

        if (! $mru) {
            $this->error("MRU not found for ID {$mruId} / code 0631 and user {$user->id}");

            return Command::FAILURE;
        }

        $baseDir = base_path('.agents/docs/Migrate/Basalgaon-0631');
        $billDataPath = $baseDir.'/bill_data.json';
        $statusesPath = $baseDir.'/statuses.json';

        if (! file_exists($billDataPath)) {
            $this->error("bill_data.json not found at: {$billDataPath}");

            return Command::FAILURE;
        }

        if (! file_exists($statusesPath)) {
            $this->error("statuses.json not found at: {$statusesPath}");

            return Command::FAILURE;
        }

        $billData = json_decode(file_get_contents($billDataPath), true);
        $statusesData = json_decode(file_get_contents($statusesPath), true);

        $this->info("Starting Basalgaon (0631) August migration for User #{$user->id} ({$user->email}), MRU #{$mru->id} ({$mru->name})...");
        if ($dryRun) {
            $this->warn('⚠️ RUNNING IN DRY-RUN MODE (No database records will be modified).');
        }
        $this->info('  • bill_data.json records: '.count($billData));
        $this->info('  • statuses.json records: '.count($statusesData));

        $workingReadingsUpdated = 0;
        $statusesUpdated = 0;
        $consumersUpdated = 0;
        $missingBillsForReading = [];
        $missingBillsForStatus = [];

        try {
            DB::transaction(function () use (
                $user,
                $mru,
                $month,
                $year,
                $billData,
                $statusesData,
                $dryRun,
                &$workingReadingsUpdated,
                &$statusesUpdated,
                &$consumersUpdated,
                &$missingBillsForReading,
                &$missingBillsForStatus
            ) {
                // 1. Update Working Readings from bill_data.json
                // Note: In Basalgaon bill_data.json, readings are under 'remark' or 'working_reading'
                foreach ($billData as $caNumber => $data) {
                    $caStr = trim((string) $caNumber);
                    $workingReading = isset($data['working_reading']) ? trim((string) $data['working_reading']) : (isset($data['remark']) ? trim((string) $data['remark']) : null);

                    if ($workingReading !== null && $workingReading !== '') {
                        $bills = BillRecord::where('user_id', $user->id)
                            ->where('mru_id', $mru->id)
                            ->where('ca_number', $caStr)
                            ->where('billing_month', $month)
                            ->when($year > 0, fn ($q) => $q->where('billing_year', $year))
                            ->get();

                        if ($bills->isEmpty()) {
                            $missingBillsForReading[] = $caStr;
                        } else {
                            foreach ($bills as $bill) {
                                if (! $dryRun) {
                                    $bill->working_reading = $workingReading;
                                    $bill->save();
                                }
                                $workingReadingsUpdated++;
                            }
                        }

                        // Update ConsumerAccount master reading ledger
                        $consumers = ConsumerAccount::where('user_id', $user->id)
                            ->where('mru_id', $mru->id)
                            ->where('ca_number', $caStr)
                            ->get();

                        foreach ($consumers as $consumer) {
                            if (! $dryRun) {
                                $consumer->last_working_reading = $workingReading;
                                $consumer->last_working_month = $month;
                                $consumer->last_working_year = $year;
                                $consumer->save();
                            }
                            $consumersUpdated++;
                        }
                    }
                }

                // 2. Update Statuses from statuses.json
                foreach ($statusesData as $caNumber => $status) {
                    $caStr = trim((string) $caNumber);
                    $statusStr = strtolower(trim((string) $status));

                    $bills = BillRecord::where('user_id', $user->id)
                        ->where('mru_id', $mru->id)
                        ->where('ca_number', $caStr)
                        ->where('billing_month', $month)
                        ->when($year > 0, fn ($q) => $q->where('billing_year', $year))
                        ->get();

                    if ($bills->isEmpty()) {
                        $missingBillsForStatus[] = $caStr;
                    } else {
                        foreach ($bills as $bill) {
                            if (! $dryRun) {
                                $bill->review_status = $statusStr;
                                $bill->save();

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
                            $statusesUpdated++;
                        }
                    }
                }

                if ($dryRun) {
                    // Rollback transaction automatically in dry run
                    throw new \Exception('DRY_RUN_COMPLETED');
                }
            });
        } catch (\Exception $e) {
            if ($e->getMessage() !== 'DRY_RUN_COMPLETED') {
                throw $e;
            }
        }

        $this->info("✅ Successfully updated {$workingReadingsUpdated} BillRecord(s) with working_reading.");
        $this->info("✅ Successfully updated {$consumersUpdated} ConsumerAccount ledger(s).");
        $this->info("✅ Successfully synchronized {$statusesUpdated} BillRecord and BillStatus status records.");

        if (! empty($missingBillsForReading)) {
            $this->warn('Notice: '.count($missingBillsForReading).' CA(s) with readings were not found in MRU #'.$mru->id.': '.implode(', ', $missingBillsForReading));
        }
        if (! empty($missingBillsForStatus)) {
            $this->warn('Notice: '.count($missingBillsForStatus).' CA(s) with statuses were not found in MRU #'.$mru->id.': '.implode(', ', $missingBillsForStatus));
        }

        return Command::SUCCESS;
    }
}
