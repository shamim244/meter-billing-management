<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupRetentionService;
use Illuminate\Console\Command;

class BackupCleanCommand extends Command
{
    protected $signature = 'saas:backup-clean {--dry-run : List candidates without deleting}';

    protected $description = 'Prune old system backups according to retention policies (7d daily, 4w weekly, 6m monthly).';

    public function handle(BackupRetentionService $retentionService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? "🔍 Running Backup Retention Check (Dry Run)..." : "🧹 Pruning Expired Backups...");

        $result = $retentionService->prune($dryRun);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Backups Analyzed', $result['total_analyzed']],
                ['Total Kept', $result['total_kept']],
                ['Total Pruned', $result['total_pruned']],
            ]
        );

        if (! empty($result['pruned_items'])) {
            $this->newLine();
            $this->warn("Pruned Backups:");
            $this->table(['ID', 'Code', 'Filename', 'Size', 'Created At'], $result['pruned_items']);
        } else {
            $this->info("✓ No expired backups required pruning.");
        }

        return self::SUCCESS;
    }
}
