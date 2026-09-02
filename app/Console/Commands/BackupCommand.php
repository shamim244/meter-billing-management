<?php

namespace App\Console\Commands;

use App\Jobs\CreateBackupJob;
use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

class BackupCommand extends Command
{
    protected $signature = 'saas:backup 
                            {--type=db_only : Type of backup: db_only, storage_only, full} 
                            {--disk= : Destination storage disk (local, s3, etc.)} 
                            {--async : Dispatch backup job to queue}';

    protected $description = 'Create a secure system backup of the database, storage, or full snapshot.';

    public function handle(BackupService $backupService): int
    {
        $type = $this->option('type') ?: 'db_only';
        $disk = $this->option('disk');
        $isAsync = $this->option('async');

        if (! in_array($type, ['db_only', 'storage_only', 'full'])) {
            $this->error("Invalid backup type: {$type}. Must be one of: db_only, storage_only, full");
            return self::FAILURE;
        }

        $this->info("⚡ Initiating NBPDCL SaaS Backup [Type: {$type}]...");

        if ($isAsync) {
            CreateBackupJob::dispatch($type, null, $disk);
            $this->info("✓ Backup job dispatched to queue.");
            return self::SUCCESS;
        }

        try {
            $backup = $backupService->createBackup($type, null, $disk);
            $this->newLine();
            $this->info("🎉 Backup Created Successfully!");
            $this->table(
                ['Property', 'Value'],
                [
                    ['Backup Code', $backup->backup_code],
                    ['Type', $backup->type_label],
                    ['Filename', $backup->filename],
                    ['Disk', $backup->disk],
                    ['Size', $backup->human_size],
                    ['SHA-256 Hash', $backup->sha256_hash],
                    ['Duration', $backup->duration_seconds . 's'],
                    ['Created At', $backup->created_at->toDateTimeString()],
                ]
            );
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ Backup failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
