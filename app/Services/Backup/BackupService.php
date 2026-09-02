<?php

namespace App\Services\Backup;

use App\Models\SystemBackup;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupService
{
    public function __construct(
        protected DatabaseDumpService $dbDumper,
        protected StorageBackupService $storageDumper,
        protected BackupRetentionService $retentionService
    ) {}

    /**
     * Create a system backup of the requested type.
     *
     * @param string $type db_only, storage_only, full
     * @param int|null $triggeredBy User ID of admin or null for cron
     * @param string|null $disk Destination storage disk (defaults to config or 'local')
     * @return SystemBackup
     */
    public function createBackup(string $type = 'db_only', ?int $triggeredBy = null, ?string $disk = null): SystemBackup
    {
        $disk = $disk ?: config('filesystems.backup_disk', 'local');
        $timestamp = date('Y-m-d_His');
        $code = "backup-{$timestamp}-{$type}";
        $startTime = microtime(true);

        // Ensure backups directory exists on local disk
        $tempDir = storage_path('app/backups/_temp_' . uniqid());
        File::ensureDirectoryExists($tempDir);
        File::ensureDirectoryExists(storage_path('app/backups'));

        $backup = SystemBackup::create([
            'backup_code' => $code,
            'type' => $type,
            'filename' => "pending-{$code}",
            'disk' => $disk,
            'status' => 'processing',
            'triggered_by' => $triggeredBy,
            'created_at' => now(),
        ]);

        try {
            $finalFilename = '';
            $meta = [];
            $tempFinalFile = '';

            if ($type === 'db_only') {
                $finalFilename = "nbpdcl_backup_{$timestamp}_db.sql.gz";
                $tempFinalFile = $tempDir . DIRECTORY_SEPARATOR . $finalFilename;

                $meta['database'] = $this->dbDumper->dump($tempFinalFile);
            } elseif ($type === 'storage_only') {
                $finalFilename = "nbpdcl_backup_{$timestamp}_storage.zip";
                $tempFinalFile = $tempDir . DIRECTORY_SEPARATOR . $finalFilename;

                $meta['storage'] = $this->storageDumper->archive($tempFinalFile);
            } elseif ($type === 'full') {
                $finalFilename = "nbpdcl_backup_{$timestamp}_full.zip";
                $tempFinalFile = $tempDir . DIRECTORY_SEPARATOR . $finalFilename;

                // 1. Dump database inside temp
                $dbFile = $tempDir . DIRECTORY_SEPARATOR . 'database.sql.gz';
                $meta['database'] = $this->dbDumper->dump($dbFile);

                // 2. Dump storage inside temp
                $storageFile = $tempDir . DIRECTORY_SEPARATOR . 'storage.zip';
                $meta['storage'] = $this->storageDumper->archive($storageFile);

                // 3. Create full master ZIP bundling both + manifest
                $masterZip = new ZipArchive();
                if ($masterZip->open($tempFinalFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    throw new \RuntimeException("Failed to create master full backup ZIP archive.");
                }

                $masterZip->addFile($dbFile, 'database.sql.gz');
                $masterZip->addFile($storageFile, 'storage.zip');

                $manifest = [
                    'app' => 'NBPDCL Electricity Billing SaaS Pro',
                    'version' => '2.4.0',
                    'backup_code' => $code,
                    'backup_type' => $type,
                    'created_at' => now()->toIso8601String(),
                    'environment' => app()->environment(),
                    'meta' => $meta,
                ];
                $masterZip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
                $masterZip->close();

                @unlink($dbFile);
                @unlink($storageFile);
            } else {
                throw new \InvalidArgumentException("Invalid backup type specified: {$type}");
            }

            if (! file_exists($tempFinalFile)) {
                throw new \RuntimeException("Backup output file was not generated: {$tempFinalFile}");
            }

            $sizeBytes = filesize($tempFinalFile);
            $sha256 = hash_file('sha256', $tempFinalFile);
            $duration = round(microtime(true) - $startTime, 2);

            // Move / Stream file to target storage disk under 'backups/'
            $targetStoragePath = 'backups/' . $finalFilename;
            $stream = fopen($tempFinalFile, 'r');
            Storage::disk($disk)->put($targetStoragePath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            // Cleanup local temp directory
            File::deleteDirectory($tempDir);

            // Update backup record
            $backup->update([
                'filename' => $finalFilename,
                'size_bytes' => $sizeBytes,
                'sha256_hash' => $sha256,
                'duration_seconds' => $duration,
                'status' => 'completed',
                'meta' => $meta,
            ]);

            // Run automated retention pruning
            $this->retentionService->prune(false);

            return $backup->fresh();

        } catch (\Throwable $e) {
            File::deleteDirectory($tempDir);

            $duration = round(microtime(true) - $startTime, 2);
            $backup->update([
                'status' => 'failed',
                'duration_seconds' => $duration,
                'error_message' => $e->getMessage() . "\n" . $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete a backup archive and its database record.
     */
    public function deleteBackup(SystemBackup $backup): bool
    {
        try {
            $path = $backup->getStoragePath();
            if (Storage::disk($backup->disk)->exists($path)) {
                Storage::disk($backup->disk)->delete($path);
            }
        } catch (\Throwable $e) {
            // Proceed to delete record even if disk file is missing
        }

        return (bool) $backup->delete();
    }

    /**
     * Get system storage metrics.
     */
    public function getSystemStorageStats(): array
    {
        $backupDisk = config('filesystems.backup_disk', 'local');
        $backups = SystemBackup::where('status', 'completed')->get();
        $totalBackupBytes = $backups->sum('size_bytes');

        $totalBills = 0;
        $billBytes = 0;
        $billsPath = storage_path('app/bills');

        if (file_exists($billsPath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($billsPath, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    $totalBills++;
                    $billBytes += $item->getSize();
                }
            }
        }

        $freeDiskSpace = @disk_free_space(storage_path('app')) ?: 0;
        $totalDiskSpace = @disk_total_space(storage_path('app')) ?: 0;

        return [
            'backup_count' => $backups->count(),
            'total_backup_bytes' => $totalBackupBytes,
            'total_backup_human' => $this->formatBytes($totalBackupBytes),
            'total_bills_count' => $totalBills,
            'total_bills_bytes' => $billBytes,
            'total_bills_human' => $this->formatBytes($billBytes),
            'free_disk_space_human' => $this->formatBytes($freeDiskSpace),
            'total_disk_space_human' => $this->formatBytes($totalDiskSpace),
            'last_backup' => SystemBackup::where('status', 'completed')->latest()->first(),
            'backup_disk' => $backupDisk,
        ];
    }

    protected function formatBytes(int|float $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . ($units[$i] ?? 'B');
    }
}
