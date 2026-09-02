<?php

namespace App\Console\Commands;

use App\Models\SystemBackup;
use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

class BackupListCommand extends Command
{
    protected $signature = 'saas:backup-list';

    protected $description = 'List all system backups and storage statistics.';

    public function handle(BackupService $backupService): int
    {
        $stats = $backupService->getSystemStorageStats();

        $this->info("💾 NBPDCL SaaS Backup System Status");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Backups Count', $stats['backup_count']],
                ['Total Backup Archive Size', $stats['total_backup_human']],
                ['Total Consumer PDFs Stored', "{$stats['total_bills_count']} files ({$stats['total_bills_human']})"],
                ['Free Server Disk Space', $stats['free_disk_space_human']],
                ['Total Server Disk Space', $stats['total_disk_space_human']],
                ['Configured Backup Disk', $stats['backup_disk']],
            ]
        );

        $backups = SystemBackup::latest()->limit(25)->get();

        if ($backups->isEmpty()) {
            $this->warn("No backups found in database.");
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info("Latest 25 Backups:");

        $rows = $backups->map(function ($b) {
            return [
                $b->id,
                $b->backup_code,
                $b->type_label,
                $b->filename,
                $b->human_size,
                strtoupper($b->status),
                $b->duration_seconds . 's',
                $b->created_at->toDateTimeString(),
            ];
        });

        $this->table(
            ['ID', 'Code', 'Type', 'Filename', 'Size', 'Status', 'Duration', 'Created At'],
            $rows->toArray()
        );

        return self::SUCCESS;
    }
}
