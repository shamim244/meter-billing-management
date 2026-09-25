<?php

namespace App\Console\Commands;

use App\Services\Installation\InstallerService;
use Illuminate\Console\Command;
use Throwable;

class AppInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:install
                            {--headless : Run non-interactively without prompt confirmation}
                            {--db-driver=mysql : Database driver (mysql or sqlite)}
                            {--db-host= : Database hostname}
                            {--db-port=3306 : Database port}
                            {--db-name= : Database name}
                            {--db-user= : Database username}
                            {--db-pass= : Database password}
                            {--app-url= : Application URL}
                            {--admin-name=Super Admin : Initial Admin Name}
                            {--admin-email= : Initial Admin Email}
                            {--admin-pass= : Initial Admin Password}
                            {--package= : Path to universal migration bundle (.zip) to restore}
                            {--force : Force installation even if already installed}';

    /**
     * The console command description.
     */
    protected $description = 'Universal Installation Engine for clean servers, Docker, and VPS environments';

    public function handle(InstallerService $installerService): int
    {
        $this->info('================================================================');
        $this->info('   NBPDCL SaaS - Universal Server Installation & Setup Engine   ');
        $this->info('================================================================');

        if ($installerService->isInstalled() && ! $this->option('force')) {
            $this->warn('⚠️ Application is already installed! (storage/installed.lock exists)');
            $this->line('Use --force to override existing installation lock.');

            return self::FAILURE;
        }

        $isHeadless = $this->option('headless');

        // Resolve Database Configuration
        $dbDriver = $this->option('db-driver') ?: ($isHeadless ? 'mysql' : $this->choice('Database Driver', ['mysql', 'sqlite'], 0));
        $dbHost = $this->option('db-host') ?: ($isHeadless ? env('DB_HOST', '127.0.0.1') : $this->ask('Database Host', env('DB_HOST', '127.0.0.1')));
        $dbPort = $this->option('db-port') ?: ($isHeadless ? env('DB_PORT', '3306') : $this->ask('Database Port', env('DB_PORT', '3306')));
        $dbName = $this->option('db-name') ?: ($isHeadless ? env('DB_DATABASE', 'meter_billing') : $this->ask('Database Name', env('DB_DATABASE', 'meter_billing')));
        $dbUser = $this->option('db-user') ?: ($isHeadless ? env('DB_USERNAME', 'root') : $this->ask('Database Username', env('DB_USERNAME', 'root')));
        $dbPass = $this->option('db-pass') !== null ? $this->option('db-pass') : ($isHeadless ? env('DB_PASSWORD', '') : $this->secret('Database Password'));
        $appUrl = $this->option('app-url') ?: ($isHeadless ? env('APP_URL', 'http://localhost') : $this->ask('App URL', env('APP_URL', 'http://localhost')));

        $dbConfig = [
            'driver' => $dbDriver,
            'host' => $dbHost,
            'port' => $dbPort,
            'database' => $dbName,
            'username' => $dbUser,
            'password' => $dbPass,
        ];

        $this->line("\n🔍 Testing database connectivity...");
        $testResult = $installerService->testDatabaseConnection($dbConfig);

        if (! $testResult['success']) {
            $this->error('❌ Connection Failed: '.$testResult['message']);

            return self::FAILURE;
        }

        $this->info('✅ '.$testResult['message']);

        // Save .env
        $this->line('📝 Writing environment configuration (.env)...');
        $installerService->saveEnvironment($dbConfig, $appUrl);

        // Check for Package Restore vs Clean Install
        $packagePath = $this->option('package');

        if ($packagePath) {
            $this->info("\n📦 Restoring from migration package: [{$packagePath}]...");
            try {
                $restoreResult = $installerService->executeRestoreInstall($packagePath);
                $this->info('🎉 Migration package restored and verified successfully!');
                $this->line("   Commit SHA: {$restoreResult['manifest']['commit_sha']}");
                $this->line('   Verified: '.($restoreResult['verified'] ? 'YES (100% table match)' : 'WARNING: check logs'));

                return self::SUCCESS;
            } catch (Throwable $e) {
                $this->error('❌ Package restore failed: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        // Clean Install
        $adminName = $this->option('admin-name') ?: 'Super Admin';
        $adminEmail = $this->option('admin-email') ?: ($isHeadless ? 'admin@example.com' : $this->ask('Super Admin Email', 'admin@example.com'));
        $adminPass = $this->option('admin-pass') ?: ($isHeadless ? 'password' : $this->secret('Super Admin Password (min 8 chars)'));

        $this->line("\n⚡ Executing database migrations, seeders, and creating Admin account...");

        try {
            $cleanResult = $installerService->executeCleanInstall([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => $adminPass,
            ]);

            $this->info('🎉 Clean installation completed successfully!');
            $this->line("   Admin Email: {$cleanResult['admin_email']}");
            $this->line('   Lock Created: storage/installed.lock');
            $this->info('================================================================');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Installation failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
