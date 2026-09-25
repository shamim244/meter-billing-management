<?php

namespace App\Services\Installation;

use App\Models\User;
use App\Services\Migration\ServerMigrationService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

class InstallerService
{
    public function __construct(
        protected ServerMigrationService $migrationService
    ) {}

    /**
     * Determine if the application is already installed.
     */
    public function isInstalled(): bool
    {
        // When in testing environment, bypass unless test explicitly sets app.testing_installer
        if (app()->environment('testing') && ! config('app.testing_installer', false)) {
            return true;
        }

        return File::exists(storage_path('installed.lock'));
    }

    /**
     * Mark the application as installed by creating the lock file.
     */
    public function markAsInstalled(array $meta = []): void
    {
        $payload = array_merge([
            'installed' => true,
            'installed_at' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'php_version' => PHP_VERSION,
            'method' => 'web_wizard',
        ], $meta);

        File::put(storage_path('installed.lock'), json_encode($payload, JSON_PRETTY_PRINT));
    }

    /**
     * Test a database connection using raw PDO (does not rely on Laravel DB config being saved yet).
     */
    public function testDatabaseConnection(array $config): array
    {
        $driver = $config['driver'] ?? 'mysql';

        if ($driver === 'sqlite') {
            $database = $config['database'] ?? database_path('database.sqlite');
            if ($database !== ':memory:' && ! File::exists($database)) {
                File::ensureDirectoryExists(dirname($database));
                touch($database);
            }

            return [
                'success' => true,
                'message' => 'SQLite connection verified successfully.',
            ];
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = (int) ($config['port'] ?? 3306);
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';

        if (empty($database)) {
            return [
                'success' => false,
                'message' => 'Database name is required.',
            ];
        }

        try {
            // First attempt to connect to the specific target database
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            return [
                'success' => true,
                'message' => "Successfully connected to database [{$database}]!",
            ];
        } catch (PDOException $e) {
            // Unknown database error code: 1049
            if ($e->getCode() == 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                try {
                    // Connect to server without database and auto-create it
                    $serverDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                    $serverPdo = new PDO($serverDsn, $username, $password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 5,
                    ]);
                    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

                    return [
                        'success' => true,
                        'message' => "Database [{$database}] did not exist, but was created automatically!",
                    ];
                } catch (Throwable $createEx) {
                    return [
                        'success' => false,
                        'message' => "Database [{$database}] does not exist and could not be created automatically: ".$createEx->getMessage(),
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Connection failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Write or update the .env file with the specified database and application parameters.
     */
    public function saveEnvironment(array $dbConfig, ?string $appUrl = null): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        if (! File::exists($envPath)) {
            if (File::exists($examplePath)) {
                File::copy($examplePath, $envPath);
            } else {
                File::put($envPath, "APP_NAME=\"NBPDCL Meter Billing\"\nAPP_ENV=production\nAPP_KEY=\nAPP_DEBUG=false\n");
            }
        }

        $envContent = File::get($envPath);

        $updates = [
            'DB_CONNECTION' => $dbConfig['driver'] ?? 'mysql',
            'DB_HOST' => $dbConfig['host'] ?? '127.0.0.1',
            'DB_PORT' => $dbConfig['port'] ?? '3306',
            'DB_DATABASE' => $dbConfig['database'] ?? '',
            'DB_USERNAME' => $dbConfig['username'] ?? '',
            'DB_PASSWORD' => $dbConfig['password'] ?? '',
        ];

        if ($appUrl) {
            $updates['APP_URL'] = rtrim($appUrl, '/');
        }

        foreach ($updates as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $escapedValue = (str_contains($value, ' ') || str_contains($value, '#')) ? "\"{$value}\"" : $value;
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "{$key}={$escapedValue}", $envContent);
            } else {
                $envContent .= "\n{$key}={$escapedValue}";
            }
        }

        File::put($envPath, $envContent);

        // Update live in-memory config so current request can connect
        config([
            'database.default' => $updates['DB_CONNECTION'],
            "database.connections.{$updates['DB_CONNECTION']}.host" => $updates['DB_HOST'],
            "database.connections.{$updates['DB_CONNECTION']}.port" => $updates['DB_PORT'],
            "database.connections.{$updates['DB_CONNECTION']}.database" => $updates['DB_DATABASE'],
            "database.connections.{$updates['DB_CONNECTION']}.username" => $updates['DB_USERNAME'],
            "database.connections.{$updates['DB_CONNECTION']}.password" => $updates['DB_PASSWORD'],
        ]);

        DB::purge($updates['DB_CONNECTION']);

        // Generate APP_KEY if empty
        if (! config('app.key') || empty(env('APP_KEY'))) {
            try {
                Artisan::call('key:generate', ['--force' => true]);
            } catch (Throwable $e) {
                // Non-fatal if already set
            }
        }
    }

    /**
     * Run clean installation: migrate database, run seeders, create initial Super Admin.
     */
    public function executeCleanInstall(array $adminData): array
    {
        // 1. Run migrations & seeders (skip during testing as RefreshDatabase manages schema)
        if (! app()->environment('testing')) {
            $migrateCode = Artisan::call('migrate', ['--force' => true]);
            if ($migrateCode !== 0) {
                throw new RuntimeException('Database migration failed: '.Artisan::output());
            }

            try {
                Artisan::call('db:seed', ['--force' => true]);
            } catch (Throwable $e) {
                Artisan::call('db:seed', ['--class' => 'RoleAndPermissionSeeder', '--force' => true]);
            }
        }

        // 3. Create or update Super Admin user
        $admin = User::firstOrNew(['email' => $adminData['email']]);
        $admin->name = $adminData['name'];
        $admin->password = Hash::make($adminData['password']);
        $admin->status = 'active';
        $admin->plan_tier = 'enterprise';
        $admin->email_verified_at = now();
        $admin->save();

        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // 4. Mark application as installed
        $this->markAsInstalled([
            'method' => 'clean_install',
            'admin_email' => $admin->email,
        ]);

        try {
            Artisan::call('optimize:clear');
            if (! file_exists(public_path('storage'))) {
                Artisan::call('storage:link');
            }
        } catch (Throwable $e) {
            // Ignore in restricted environments
        }

        return [
            'success' => true,
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
        ];
    }

    /**
     * Run restore installation from an uploaded universal migration package.
     */
    public function executeRestoreInstall(string $bundlePath): array
    {
        $result = $this->migrationService->restoreMigrationPackage($bundlePath, [
            'force' => true,
        ]);

        $this->markAsInstalled([
            'method' => 'migration_restore',
            'restored_at' => now()->toIso8601String(),
            'manifest_commit' => $result['manifest']['commit_sha'] ?? null,
            'verified' => $result['verified'] ?? false,
        ]);

        return $result;
    }
}
