<?php

namespace App\Console\Commands;

use App\Models\SystemBackup;
use App\Services\Backup\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupRestoreCommand extends Command
{
    protected $signature = 'saas:restore 
                            {backup : ID, backup_code or relative storage path of the backup} 
                            {--dry-run : Verify integrity without applying} 
                            {--force : Force restore without interactive confirmation}';

    protected $description = 'Disaster Recovery: Verify and restore a database/system backup.';

    public function handle(BackupService $backupService): int
    {
        $identifier = $this->argument('backup');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        // Locate backup record or file
        $backup = is_numeric($identifier)
            ? SystemBackup::find($identifier)
            : SystemBackup::where('backup_code', $identifier)->orWhere('filename', $identifier)->first();

        if (! $backup) {
            $this->error("Backup [{$identifier}] not found in database.");
            return self::FAILURE;
        }

        $storagePath = $backup->getStoragePath();
        if (! Storage::disk($backup->disk)->exists($storagePath)) {
            $this->error("Physical archive file is missing from disk: {$storagePath}");
            return self::FAILURE;
        }

        $this->info("🔍 Inspecting Backup Archive: [{$backup->filename}] ({$backup->human_size})");

        // Verify SHA-256 Checksum
        $localAbsPath = Storage::disk($backup->disk)->path($storagePath);
        if (file_exists($localAbsPath)) {
            $actualHash = hash_file('sha256', $localAbsPath);
            if ($backup->sha256_hash && $actualHash !== $backup->sha256_hash) {
                $this->error("❌ Integrity Check FAILED! Checksum mismatch. Expected: {$backup->sha256_hash}, Got: {$actualHash}");
                return self::FAILURE;
            }
            $this->info("✓ SHA-256 Checksum verified ({$actualHash})");
        }

        if ($dryRun) {
            $this->info("✓ Dry-run completed. Archive is valid and ready for restore.");
            return self::SUCCESS;
        }

        if (! $force && ! $this->confirm("⚠️ DANGER: Restoring this backup will overwrite current data. Do you wish to continue?")) {
            $this->warn("Restore canceled.");
            return self::SUCCESS;
        }

        $this->info("1. Taking automatic pre-restore safety snapshot of current database...");
        try {
            $preSnapshot = $backupService->createBackup('db_only', null);
            $this->info("✓ Pre-restore snapshot created: {$preSnapshot->filename}");
        } catch (\Throwable $e) {
            $this->warn("Warning: Could not create pre-restore snapshot: " . $e->getMessage());
        }

        $this->info("2. Executing restoration for [{$backup->type_label}]...");

        try {
            if (str_ends_with($backup->filename, '.sql.gz')) {
                $this->restoreSqlGz($localAbsPath);
            } elseif (str_ends_with($backup->filename, '.zip')) {
                $this->restoreZip($localAbsPath);
            }

            $this->info("🎉 Backup [{$backup->filename}] restored successfully!");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ Restore execution failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function restoreSqlGz(string $gzPath): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $tempSql = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'restore_' . uniqid() . '.sql';
        
        // Decompress GZ
        $gz = gzopen($gzPath, 'rb');
        $out = fopen($tempSql, 'wb');
        while (! feof($gz)) {
            fwrite($out, gzread($gz, 1024 * 512));
        }
        gzclose($gz);
        fclose($out);

        if ($driver === 'mysql') {
            $host = config("database.connections.{$connection}.host", '127.0.0.1');
            $port = config("database.connections.{$connection}.port", '3306');
            $database = config("database.connections.{$connection}.database");
            $username = config("database.connections.{$connection}.username");
            $password = config("database.connections.{$connection}.password", '');

            $cmd = sprintf(
                'mysql --host=%s --port=%s --user=%s %s %s < %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                $password !== '' ? '--password=' . escapeshellarg($password) : '',
                escapeshellarg($database),
                escapeshellarg($tempSql)
            );

            $process = Process::fromShellCommandline($cmd);
            $process->setTimeout(600);
            $process->run();

            @unlink($tempSql);

            if (! $process->isSuccessful()) {
                throw new \RuntimeException("mysql client restore failed: " . $process->getErrorOutput());
            }
        } else {
            // SQLite / Fallback direct multi-query execution
            $sql = file_get_contents($tempSql);
            @unlink($tempSql);
            DB::unprepared($sql);
        }
    }

    protected function restoreZip(string $zipPath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === true) {
            $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'restore_zip_' . uniqid();
            $zip->extractTo($tempDir);
            $zip->close();

            $dbFile = $tempDir . DIRECTORY_SEPARATOR . 'database.sql.gz';
            if (file_exists($dbFile)) {
                $this->restoreSqlGz($dbFile);
            }

            $storageZip = $tempDir . DIRECTORY_SEPARATOR . 'storage.zip';
            if (file_exists($storageZip)) {
                $sZip = new \ZipArchive();
                if ($sZip->open($storageZip) === true) {
                    $sZip->extractTo(storage_path('app'));
                    $sZip->close();
                }
            }

            \Illuminate\Support\Facades\File::deleteDirectory($tempDir);
        }
    }
}
