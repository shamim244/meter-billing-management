<?php

namespace App\Console\Commands;

use App\Services\Migration\ServerMigrationService;
use Illuminate\Console\Command;

class MigrationUnpackCommand extends Command
{
    protected $signature = 'app:migration-unpack 
                            {archive : Path to the migration bundle .zip file} 
                            {--skip-storage : Skip restoring storage files} 
                            {--force : Force restore without interactive confirmation}';

    protected $description = 'Universal Cloud Portability: Restore migration archive on new server with post-flight verification audit';

    public function handle(ServerMigrationService $migrationService): int
    {
        $archivePath = $this->argument('archive');
        $force = (bool) $this->option('force');
        $skipStorage = (bool) $this->option('skip-storage');

        if (! file_exists($archivePath)) {
            $this->error("Migration archive file not found: {$archivePath}");

            return self::FAILURE;
        }

        $this->info("🔍 Inspecting Migration Bundle: [{$archivePath}]...");

        try {
            $inspection = $migrationService->inspectPackage($archivePath);
            $manifest = $inspection['manifest'];

            $this->line("• Archive Size: <comment>{$inspection['human_size']}</comment>");
            $this->line("• Created At: <comment>{$manifest['created_at']}</comment>");
            $this->line("• Source PHP: <comment>{$manifest['php_version']}</comment> | Laravel: <comment>{$manifest['laravel_version']}</comment>");
            $this->line("• SHA-256: <info>{$inspection['bundle_sha256']}</info>");

            $this->newLine();
            $this->info('📊 Source Server Record Manifest:');
            $rows = [];
            foreach ($manifest['table_counts'] as $table => $count) {
                $rows[] = [$table, number_format($count)];
            }
            $this->table(['Table', 'Expected Rows'], $rows);

            if (isset($manifest['wallet_sum'])) {
                $this->line('• Expected Wallet Ledger Balance: <info>₹'.number_format($manifest['wallet_sum'], 2).'</info>');
            }

            if (! $force && ! $this->confirm('⚠️ Restoring this archive will overwrite current database and storage files on this server. Proceed?', false)) {
                $this->comment('Operation cancelled by user.');

                return self::SUCCESS;
            }

            $this->newLine();
            $this->info('🚀 Restoring Database and Media Assets...');

            $result = $migrationService->restoreMigrationPackage($archivePath, [
                'skip_storage' => $skipStorage,
                'force' => $force,
            ]);

            $audit = $result['audit'];

            $this->newLine();
            $this->info('🔍 Post-Flight Integrity Audit Results:');

            $auditRows = [];
            foreach ($audit['live_counts'] as $table => $liveCount) {
                $expected = $manifest['table_counts'][$table] ?? 0;
                $status = ($liveCount === (int) $expected)
                    ? '<info>MATCHED (100%)</info>'
                    : '<fg=red>MISMATCH</fg=red>';

                $auditRows[] = [
                    $table,
                    number_format($expected),
                    number_format($liveCount),
                    $status,
                ];
            }
            $this->table(['Table', 'Expected', 'Restored', 'Audit Status'], $auditRows);

            if ($audit['wallet_balance_match']) {
                $this->info('✅ Financial Wallet Ledger Balance: 100% MATCHED');
            } else {
                $this->error('❌ Wallet Ledger Balance Discrepancy Detected!');
            }

            if ($audit['verified']) {
                $this->newLine();
                $this->info('🎉 Migration 100% VERIFIED & COMPLETE! Zero data loss.');
                $this->info('The application is fully restored, caches warmed, and ready for production traffic.');

                return self::SUCCESS;
            }

            $this->warn('⚠️ Migration restored but some table counts had discrepancies. Please review the audit table above.');

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('❌ Migration unpack failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
