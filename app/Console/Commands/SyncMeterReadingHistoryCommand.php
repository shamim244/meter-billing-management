<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MeterReadingHistoryService;
use Illuminate\Console\Command;

class SyncMeterReadingHistoryCommand extends Command
{
    protected $signature = 'readings:sync-history 
                            {--user_id= : Specific user ID to synchronize}';

    protected $description = 'Safely backfill and synchronize meter_reading_histories from existing BillRecord extractions and working readings.';

    public function handle(MeterReadingHistoryService $historyService): int
    {
        $specificUserId = $this->option('user_id');

        $this->info('⚡ Starting Meter Reading History Synchronization...');

        $userQuery = User::query();
        if ($specificUserId) {
            $userQuery->where('id', $specificUserId);
        } else {
            $userQuery->whereHas('billRecords');
        }

        $users = $userQuery->get();

        if ($users->isEmpty()) {
            $this->warn('No matching users with bills found to synchronize.');

            return self::SUCCESS;
        }

        $totalSynced = 0;
        foreach ($users as $user) {
            $this->line("Processing User #{$user->id} ({$user->email})...");
            $syncedCount = $historyService->syncAllFromExistingBills($user->id);
            $this->info("  ✓ Synced {$syncedCount} entries into meter_reading_histories.");
            $totalSynced += $syncedCount;
        }

        $this->newLine();
        $this->info("🎉 Completed: Total {$totalSynced} historical entries synchronized into meter_reading_histories table.");

        return self::SUCCESS;
    }
}
