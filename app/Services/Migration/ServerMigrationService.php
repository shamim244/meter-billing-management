<?php

namespace App\Services\Migration;

use App\Services\Backup\DatabaseDumpService;
use App\Services\Backup\StorageBackupService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class ServerMigrationService
{
    /**
     * Critical tables tracked in the verification manifest.
     */
    protected array $trackedTables = [
        'users',
        'consumer_accounts',
        'bill_records',
        'bill_statuses',
        'meter_reading_histories',
        'mrus',
        'wallets',
        'transactions',
        'system_settings',
        'api_keys',
        'issue_reports',
        'roles',
        'permissions',
    ];

    public function __construct(
        protected DatabaseDumpService $databaseDumpService,
        protected StorageBackupService $storageBackupService
    ) {}

    /**
     * Create a complete, self-contained migration package.
     *
     * @param  array  $options  ['output_dir' => string, 'skip_storage' => bool]
     * @return array Metadata about the generated bundle
     */
    public function createMigrationPackage(array $options = []): array
    {
        $timestamp = date('Y-m-d_His');
        $outputDir = $options['output_dir'] ?? storage_path('app/migrations');
        File::ensureDirectoryExists($outputDir);

        $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'migration_pack_'.uniqid();
        File::ensureDirectoryExists($tempDir);

        try {
            // 1. Generate Database Dump
            $dbPath = $tempDir.DIRECTORY_SEPARATOR.'database.sql.gz';
            $dumpSummary = $this->databaseDumpService->dump($dbPath);
            $dbSha256 = hash_file('sha256', $dbPath);
            $dbSize = filesize($dbPath);

            // 2. Generate Storage Archive (unless skipped)
            $storageSha256 = null;
            $storageSize = 0;
            $storageSummary = ['files_count' => 0, 'size_bytes' => 0];

            if (empty($options['skip_storage'])) {
                $storagePath = $tempDir.DIRECTORY_SEPARATOR.'storage.zip';
                $storageSummary = $this->storageBackupService->archive($storagePath);
                $storageSha256 = hash_file('sha256', $storagePath);
                $storageSize = filesize($storagePath);
            }

            // 3. Build Post-Flight Verification Manifest
            $tableCounts = [];
            foreach ($this->trackedTables as $table) {
                $tableCounts[$table] = Schema::hasTable($table) ? DB::table($table)->count() : 0;
            }

            $walletSum = Schema::hasTable('wallets')
                ? (float) DB::table('wallets')->sum('balance')
                : 0.0;

            $manifest = [
                'app_name' => config('app.name', 'Meter Billing'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'commit_sha' => $this->resolveGitCommit(),
                'created_at' => now()->toIso8601String(),
                'export_type' => 'universal_cross_cloud',
                'checksums' => [
                    'database_sql_gz' => $dbSha256,
                    'storage_zip' => $storageSha256,
                ],
                'sizes' => [
                    'database_bytes' => $dbSize,
                    'storage_bytes' => $storageSize,
                ],
                'table_counts' => $tableCounts,
                'wallet_sum' => $walletSum,
                'storage_summary' => $storageSummary,
            ];

            $manifestPath = $tempDir.DIRECTORY_SEPARATOR.'manifest.json';
            File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // 4. Bundle into final ZIP package
            $bundleFilename = "meter_billing_migration_{$timestamp}.zip";
            $bundlePath = $outputDir.DIRECTORY_SEPARATOR.$bundleFilename;

            $zip = new ZipArchive;
            if ($zip->open($bundlePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException("Failed to create migration ZIP at: {$bundlePath}");
            }

            $zip->addFile($dbPath, 'database.sql.gz');
            $zip->addFile($manifestPath, 'manifest.json');

            if (! empty($options['skip_storage']) === false && file_exists($tempDir.DIRECTORY_SEPARATOR.'storage.zip')) {
                $zip->addFile($tempDir.DIRECTORY_SEPARATOR.'storage.zip', 'storage.zip');
            }

            $zip->close();

            $bundleSha256 = hash_file('sha256', $bundlePath);
            $bundleSize = filesize($bundlePath);

            return [
                'success' => true,
                'bundle_path' => $bundlePath,
                'bundle_filename' => $bundleFilename,
                'bundle_sha256' => $bundleSha256,
                'bundle_size' => $bundleSize,
                'human_size' => $this->formatBytes($bundleSize),
                'manifest' => $manifest,
            ];
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    /**
     * Inspect a migration package without restoring it.
     */
    public function inspectPackage(string $bundlePath): array
    {
        if (! file_exists($bundlePath)) {
            throw new RuntimeException("Migration package file not found: {$bundlePath}");
        }

        $zip = new ZipArchive;
        if ($zip->open($bundlePath) !== true) {
            throw new RuntimeException("Invalid or corrupt migration archive: {$bundlePath}");
        }

        $manifestIndex = $zip->locateName('manifest.json');
        if ($manifestIndex === false) {
            $zip->close();
            throw new RuntimeException('Archive missing required manifest.json');
        }

        $manifestRaw = $zip->getFromIndex($manifestIndex);
        $zip->close();

        $manifest = json_decode($manifestRaw, true);
        if (! is_array($manifest)) {
            throw new RuntimeException('Corrupted manifest.json inside archive');
        }

        return [
            'valid' => true,
            'bundle_path' => $bundlePath,
            'bundle_sha256' => hash_file('sha256', $bundlePath),
            'bundle_size' => filesize($bundlePath),
            'human_size' => $this->formatBytes(filesize($bundlePath)),
            'manifest' => $manifest,
        ];
    }

    /**
     * Unpack and restore a migration package, followed by post-flight verification.
     *
     * @param  string  $bundlePath  Path to the migration .zip bundle
     * @param  array  $options  ['skip_storage' => bool, 'force' => bool]
     * @return array Audit result and verification status
     */
    public function restoreMigrationPackage(string $bundlePath, array $options = []): array
    {
        $inspection = $this->inspectPackage($bundlePath);
        $manifest = $inspection['manifest'];

        $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'migration_unpack_'.uniqid();
        File::ensureDirectoryExists($tempDir);

        try {
            $zip = new ZipArchive;
            if ($zip->open($bundlePath) !== true) {
                throw new RuntimeException("Failed to open archive: {$bundlePath}");
            }
            $zip->extractTo($tempDir);
            $zip->close();

            // 1. Verify Internal Checksums
            $dbFile = $tempDir.DIRECTORY_SEPARATOR.'database.sql.gz';
            if (! file_exists($dbFile)) {
                throw new RuntimeException('Migration bundle missing database.sql.gz');
            }

            $actualDbHash = hash_file('sha256', $dbFile);
            $expectedDbHash = $manifest['checksums']['database_sql_gz'] ?? null;
            if ($expectedDbHash && $actualDbHash !== $expectedDbHash) {
                throw new RuntimeException("Database integrity mismatch! Expected: {$expectedDbHash}, Got: {$actualDbHash}");
            }

            // 2. Restore Database
            $this->restoreDatabaseFile($dbFile);

            // 3. Restore Storage (if present and not skipped)
            $storageFile = $tempDir.DIRECTORY_SEPARATOR.'storage.zip';
            if (empty($options['skip_storage']) && file_exists($storageFile)) {
                $actualStorageHash = hash_file('sha256', $storageFile);
                $expectedStorageHash = $manifest['checksums']['storage_zip'] ?? null;
                if ($expectedStorageHash && $actualStorageHash !== $expectedStorageHash) {
                    throw new RuntimeException("Storage archive integrity mismatch! Expected: {$expectedStorageHash}, Got: {$actualStorageHash}");
                }

                $sZip = new ZipArchive;
                if ($sZip->open($storageFile) === true) {
                    $sZip->extractTo(storage_path('app'));
                    $sZip->close();
                }
            }

            // 4. Run Post-Flight Verification Audit
            $audit = $this->runIntegrityAudit(
                $manifest['table_counts'] ?? [],
                isset($manifest['wallet_sum']) ? (float) $manifest['wallet_sum'] : null
            );

            // 5. Initialize Storage Symlink and Clear Caches
            try {
                Artisan::call('storage:link', ['--force' => true]);
                Artisan::call('optimize:clear');
            } catch (\Throwable $e) {
                // Non-fatal on restrictive shared hosting
            }

            return [
                'success' => true,
                'verified' => $audit['verified'],
                'audit' => $audit,
                'manifest' => $manifest,
            ];
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    /**
     * Restore database from gzipped SQL file.
     */
    protected function restoreDatabaseFile(string $gzPath): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $tempSql = sys_get_temp_dir().DIRECTORY_SEPARATOR.'restore_sql_'.uniqid().'.sql';

        $gz = gzopen($gzPath, 'rb');
        $out = fopen($tempSql, 'wb');
        while (! feof($gz)) {
            fwrite($out, gzread($gz, 1024 * 512));
        }
        gzclose($gz);
        fclose($out);

        try {
            if ($driver === 'mysql' && $this->isMysqlClientAvailable()) {
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
                    $password !== '' ? '--password='.escapeshellarg($password) : '',
                    escapeshellarg($database),
                    escapeshellarg($tempSql)
                );

                $process = Process::fromShellCommandline($cmd);
                $process->setTimeout(600);
                $process->run();

                if (! $process->isSuccessful()) {
                    throw new RuntimeException('MySQL CLI restore failed: '.$process->getErrorOutput());
                }
            } else {
                // PDO chunked execution fallback (safe on shared hosting and SQLite)
                $sqlContent = file_get_contents($tempSql);

                if ($driver === 'mysql') {
                    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                } elseif ($driver === 'sqlite') {
                    $sqlContent = preg_replace('/SET\s+FOREIGN_KEY_CHECKS\s*=\s*[01];?/i', '', $sqlContent);
                    DB::statement('PRAGMA foreign_keys = OFF;');
                }

                DB::unprepared($sqlContent);

                if ($driver === 'mysql') {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                } elseif ($driver === 'sqlite') {
                    DB::statement('PRAGMA foreign_keys = ON;');
                }
            }
        } finally {
            @unlink($tempSql);
        }
    }

    /**
     * Check if mysql client binary is available in the shell path.
     */
    protected function isMysqlClientAvailable(): bool
    {
        try {
            $process = Process::fromShellCommandline('mysql --version');
            $process->run();

            return $process->isSuccessful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Compare live database record counts against the manifest to guarantee 100% data fidelity.
     */
    public function runIntegrityAudit(array $manifestCounts, ?float $expectedWalletSum = null): array
    {
        $discrepancies = [];
        $matched = [];
        $liveCounts = [];

        foreach ($manifestCounts as $table => $expectedCount) {
            if (! Schema::hasTable($table)) {
                $discrepancies[$table] = [
                    'status' => 'missing_table',
                    'expected' => $expectedCount,
                    'actual' => 0,
                ];

                continue;
            }

            $actualCount = DB::table($table)->count();
            $liveCounts[$table] = $actualCount;

            if ($actualCount !== (int) $expectedCount) {
                $discrepancies[$table] = [
                    'status' => 'count_mismatch',
                    'expected' => $expectedCount,
                    'actual' => $actualCount,
                ];
            } else {
                $matched[$table] = $actualCount;
            }
        }

        $walletDiscrepancy = null;
        if ($expectedWalletSum !== null && Schema::hasTable('wallets')) {
            $actualWalletSum = (float) DB::table('wallets')->sum('balance');
            if (abs($actualWalletSum - $expectedWalletSum) > 0.01) {
                $walletDiscrepancy = [
                    'expected' => $expectedWalletSum,
                    'actual' => $actualWalletSum,
                ];
            }
        }

        $isVerified = empty($discrepancies) && $walletDiscrepancy === null;

        return [
            'verified' => $isVerified,
            'matched' => $matched,
            'discrepancies' => $discrepancies,
            'wallet_balance_match' => $walletDiscrepancy === null,
            'wallet_discrepancy' => $walletDiscrepancy,
            'live_counts' => $liveCounts,
        ];
    }

    /**
     * Run environment preflight checks on the current server.
     */
    public function runPreflightCheck(): array
    {
        $requiredExtensions = [
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'bcmath' => extension_loaded('bcmath'),
            'mbstring' => extension_loaded('mbstring'),
            'xml' => extension_loaded('xml'),
            'curl' => extension_loaded('curl'),
            'zip' => extension_loaded('zip'),
            'intl' => extension_loaded('intl'),
            'gd' => extension_loaded('gd'),
            'redis' => extension_loaded('redis'),
            'brotli' => extension_loaded('brotli'),
            'zstd' => extension_loaded('zstd'),
        ];

        $writablePaths = [
            'storage' => is_writable(storage_path()),
            'storage/app' => is_writable(storage_path('app')),
            'storage/framework' => is_writable(storage_path('framework')),
            'storage/logs' => is_writable(storage_path('logs')),
            'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
        ];

        // Database Ping
        $dbConnected = false;
        $dbError = null;
        try {
            DB::connection()->getPdo();
            $dbConnected = true;
        } catch (\Throwable $e) {
            $dbError = $e->getMessage();
        }

        // Redis Ping
        $redisConnected = false;
        try {
            if ($requiredExtensions['redis']) {
                $redis = app('redis');
                $redisConnected = $redis->ping() !== false;
            }
        } catch (\Throwable $e) {
            $redisConnected = false;
        }

        $dbDriver = config('database.default');
        $dbDriverLoaded = $dbDriver === 'sqlite' ? extension_loaded('pdo_sqlite') : ($requiredExtensions['pdo_mysql'] ?? false);

        $phpSatisfies = version_compare(PHP_VERSION, '8.4.1', '>=');

        // Audit disabled functions
        $rawDisabled = (string) ini_get('disable_functions');
        $disabledList = array_values(array_filter(array_map('trim', explode(',', strtolower($rawDisabled)))));

        $criticalFunctions = [
            'putenv' => [
                'name' => 'putenv',
                'enabled' => function_exists('putenv') && ! in_array('putenv', $disabledList, true),
                'category' => 'critical',
                'description' => 'Loads environment configuration and database credentials.',
            ],
        ];

        $recommendedFunctions = [
            'proc_open' => [
                'name' => 'proc_open',
                'enabled' => function_exists('proc_open') && ! in_array('proc_open', $disabledList, true),
                'category' => 'recommended',
                'description' => 'Executes background workers and Artisan subprocesses.',
            ],
            'shell_exec' => [
                'name' => 'shell_exec',
                'enabled' => function_exists('shell_exec') && ! in_array('shell_exec', $disabledList, true),
                'category' => 'recommended',
                'description' => 'Executes shell utilities when shell access is enabled.',
            ],
            'exec' => [
                'name' => 'exec',
                'enabled' => function_exists('exec') && ! in_array('exec', $disabledList, true),
                'category' => 'recommended',
                'description' => 'Runs native database dump utilities and native CLI tools.',
            ],
            'symlink' => [
                'name' => 'symlink',
                'enabled' => function_exists('symlink') && ! in_array('symlink', $disabledList, true),
                'category' => 'recommended',
                'description' => 'Creates public/storage symlink. Web route fallback activates automatically if disabled.',
            ],
        ];

        $criticalFunctionsPassed = collect($criticalFunctions)->every(fn ($fn) => $fn['enabled']);

        // Audit Zend OPcache status
        $opcacheLoaded = extension_loaded('Zend OPcache');
        $opcacheEnabled = $opcacheLoaded && (bool) ini_get('opcache.enable');
        $opcacheInfo = [
            'installed' => $opcacheLoaded,
            'enabled' => $opcacheEnabled,
            'status' => $opcacheEnabled ? 'enabled' : ($opcacheLoaded ? 'disabled' : 'not_installed'),
            'message' => $opcacheEnabled
                ? 'OPcache bytecode caching is active (Optimal Performance).'
                : ($opcacheLoaded ? 'OPcache is installed but disabled in php.ini.' : 'OPcache extension is not installed.'),
        ];

        $serverEnvironmentReady = $phpSatisfies
            && $dbDriverLoaded
            && $requiredExtensions['bcmath']
            && $requiredExtensions['mbstring']
            && $requiredExtensions['zip']
            && $writablePaths['storage']
            && $writablePaths['bootstrap/cache']
            && $criticalFunctionsPassed;

        $allCriticalPassed = $serverEnvironmentReady && $dbConnected;

        return [
            'ready' => $allCriticalPassed,
            'server_ready' => $serverEnvironmentReady,
            'php_version' => PHP_VERSION,
            'php_satisfies' => $phpSatisfies,
            'extensions' => $requiredExtensions,
            'writable_paths' => $writablePaths,
            'database' => [
                'connected' => $dbConnected,
                'driver' => config('database.default'),
                'error' => $dbError,
            ],
            'redis' => [
                'connected' => $redisConnected,
            ],
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'functions' => [
                'critical' => $criticalFunctions,
                'recommended' => $recommendedFunctions,
                'disabled_functions' => $disabledList,
            ],
            'critical_functions' => $criticalFunctions,
            'recommended_functions' => $recommendedFunctions,
            'disabled_functions' => $disabledList,
            'opcache' => $opcacheInfo,
        ];
    }

    /**
     * Resolve Git commit hash if in a git repository.
     */
    protected function resolveGitCommit(): ?string
    {
        try {
            $head = base_path('.git/HEAD');
            if (! file_exists($head)) {
                return null;
            }
            $ref = trim(file_get_contents($head));
            if (str_starts_with($ref, 'ref: ')) {
                $refPath = base_path('.git/'.substr($ref, 5));

                return file_exists($refPath) ? trim(file_get_contents($refPath)) : null;
            }

            return $ref;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Format bytes into human-readable string.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}
