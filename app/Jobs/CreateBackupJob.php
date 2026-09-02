<?php

namespace App\Jobs;

use App\Services\Backup\BackupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateBackupJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 1800; // 30 minutes timeout for large datasets

    public function __construct(
        public string $type = 'db_only',
        public ?int $triggeredBy = null,
        public ?string $disk = null
    ) {}

    public function handle(BackupService $backupService): void
    {
        Log::info("Starting background system backup [type: {$this->type}] by user [{$this->triggeredBy}]");

        try {
            $backup = $backupService->createBackup($this->type, $this->triggeredBy, $this->disk);
            Log::info("Backup completed successfully: {$backup->filename} ({$backup->human_size}) in {$backup->duration_seconds}s");
        } catch (\Throwable $e) {
            Log::error("Backup failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }
}
