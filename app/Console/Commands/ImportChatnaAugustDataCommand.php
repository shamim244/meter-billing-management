<?php

namespace App\Console\Commands;

use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportChatnaAugustDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:chatna-august 
                            {--email=shamim244d@gmail.com : User email}
                            {--mru=24 : MRU ID (default: 24 for Chatna)}
                            {--month=8 : Target billing month (default: 8 for August)}
                            {--year=2026 : Target billing year (default: 2026)}
                            {--dry-run : Run import without saving changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import August working readings, review statuses, and bill metrics for Chatna (0122 / MRU 24) from work backup file';

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
            $mru = Mru::where('code', '0122')->where('user_id', $user->id)->first();
        }

        if (! $mru) {
            $this->error("MRU not found for ID {$mruId} / code 0122 and user {$user->id}");

            return Command::FAILURE;
        }

        $backupPath = 'C:/Users/bccbo/Desktop/NBPDCL/billing/chatna-0122-work-backup.json';
        if (! file_exists($backupPath)) {
            $this->error("Backup file not found at: {$backupPath}");

            return Command::FAILURE;
        }

        $rawJson = file_get_contents($backupPath);
        $data = json_decode($rawJson, true);

        if (! is_array($data) || empty($data)) {
            $this->error("Invalid or empty JSON in backup file: {$backupPath}");

            return Command::FAILURE;
        }

        // Filter records for MRU 0122
        $records0122 = [];
        foreach ($data as $item) {
            if (str_contains($item['pdf_path'] ?? '', '0122')) {
                $records0122[(string) $item['ca_number']] = $item;
            }
        }

        $this->info("Starting Chatna (0122) August restore for User #{$user->id} ({$user->email}), MRU #{$mru->id} ({$mru->name})...");
        if ($dryRun) {
            $this->warn('⚠️ RUNNING IN DRY-RUN MODE (No database records will be modified).');
        }
        $this->info('  • Backup records found for 0122: '.count($records0122));

        $workingReadingsUpdated = 0;
        $statusesUpdated = 0;
        $consumersUpdated = 0;
        $missingBills = [];

        try {
            DB::transaction(function () use (
                $user,
                $mru,
                $month,
                $year,
                $records0122,
                $dryRun,
                &$workingReadingsUpdated,
                &$statusesUpdated,
                &$consumersUpdated,
                &$missingBills
            ) {
                foreach ($records0122 as $caStr => $item) {
                    $bills = BillRecord::where('user_id', $user->id)
                        ->where('mru_id', $mru->id)
                        ->where('ca_number', $caStr)
                        ->where('billing_month', $month)
                        ->when($year > 0, fn ($q) => $q->where('billing_year', $year))
                        ->get();

                    if ($bills->isEmpty()) {
                        $missingBills[] = $caStr;

                        continue;
                    }

                    $workingReading = isset($item['working_reading']) && trim((string) $item['working_reading']) !== ''
                        ? trim((string) $item['working_reading'])
                        : null;

                    $statusStr = isset($item['review_status']) && trim((string) $item['review_status']) !== ''
                        ? strtolower(trim((string) $item['review_status']))
                        : 'pending';

                    $remark = $item['remark'] ?? null;

                    foreach ($bills as $bill) {
                        if (! $dryRun) {
                            if ($workingReading !== null) {
                                $bill->working_reading = $workingReading;
                            }
                            $bill->review_status = $statusStr;
                            $bill->remark = $remark;

                            if (! empty($item['consumer_name'])) {
                                $bill->consumer_name = $item['consumer_name'];
                            }
                            if (isset($item['current_reading']) && $item['current_reading'] !== '') {
                                $bill->current_reading = $item['current_reading'];
                            }
                            if (isset($item['previous_reading']) && $item['previous_reading'] !== '') {
                                $bill->previous_reading = $item['previous_reading'];
                            }
                            if (isset($item['units_consumed'])) {
                                $bill->units_consumed = (int) $item['units_consumed'];
                            }
                            if (isset($item['total_amount'])) {
                                $bill->total_amount = (float) $item['total_amount'];
                            }
                            if (! empty($item['meter_no'])) {
                                $bill->meter_no = $item['meter_no'];
                            }
                            if (! empty($item['tariff_category'])) {
                                $bill->tariff_category = $item['tariff_category'];
                            }
                            if (! empty($item['billing_basis'])) {
                                $bill->billing_basis = $item['billing_basis'];
                            }
                            if (! empty($item['bill_date'])) {
                                $bill->bill_date = $item['bill_date'];
                            }
                            if (! empty($item['due_date'])) {
                                $bill->due_date = $item['due_date'];
                            }

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
                                    'remark' => $remark,
                                ]
                            );
                        }

                        $statusesUpdated++;
                        if ($workingReading !== null) {
                            $workingReadingsUpdated++;
                        }
                    }

                    // Update ConsumerAccount master ledger
                    $consumers = ConsumerAccount::where('user_id', $user->id)
                        ->where('mru_id', $mru->id)
                        ->where('ca_number', $caStr)
                        ->get();

                    foreach ($consumers as $consumer) {
                        if (! $dryRun) {
                            if ($workingReading !== null) {
                                $consumer->last_working_reading = $workingReading;
                                $consumer->last_working_month = $month;
                                $consumer->last_working_year = $year;
                            }
                            if (! empty($item['consumer_name'])) {
                                $consumer->consumer_name = $item['consumer_name'];
                            }
                            if (! empty($item['meter_no'])) {
                                $consumer->meter_no = $item['meter_no'];
                            }
                            if (! empty($item['tariff_category'])) {
                                $consumer->tariff_category = $item['tariff_category'];
                            }
                            if (! empty($item['billing_basis'])) {
                                $consumer->billing_basis = $item['billing_basis'];
                            }
                            $consumer->save();
                        }
                        $consumersUpdated++;
                    }
                }

                if ($dryRun) {
                    throw new \Exception('DRY_RUN_COMPLETED');
                }
            });
        } catch (\Exception $e) {
            if ($e->getMessage() !== 'DRY_RUN_COMPLETED') {
                throw $e;
            }
        }

        $this->info("✅ Successfully restored {$workingReadingsUpdated} BillRecord(s) with working_reading.");
        $this->info("✅ Successfully restored {$consumersUpdated} ConsumerAccount ledger(s).");
        $this->info("✅ Successfully synchronized {$statusesUpdated} BillRecord and BillStatus status records.");

        if (! empty($missingBills)) {
            $this->warn('Notice: '.count($missingBills).' CA(s) were not found in MRU #'.$mru->id.': '.implode(', ', $missingBills));
        }

        return Command::SUCCESS;
    }
}
