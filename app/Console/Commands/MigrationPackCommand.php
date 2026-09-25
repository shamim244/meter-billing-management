<?php

namespace App\Console\Commands;

use App\Services\Migration\ServerMigrationService;
use Illuminate\Console\Command;

class MigrationPackCommand extends Command
{
    protected $signature = 'app:migration-pack 
                            {--output= : Custom destination directory for the bundle} 
                            {--skip-storage : Skip archiving storage public assets}';

    protected $description = 'Universal Cloud Portability: Generate self-contained migration archive with cryptographic manifest';

    public function handle(ServerMigrationService $migrationService): int
    {
        $this->info('📦 Initiating Universal Migration Export...');
        $outputDir = $this->option('output') ?: storage_path('app/migrations');
        $skipStorage = (bool) $this->option('skip-storage');

        $this->line('1. Freezing state snapshot and dumping MySQL database...');
        if (! $skipStorage) {
            $this->line('2. Archiving persistent bill PDFs and public storage...');
        } else {
            $this->comment('2. Skipping persistent storage assets (--skip-storage flag active).');
        }
        $this->line('3. Computing SHA-256 cryptographic hashes & row counts manifest...');

        try {
            $result = $migrationService->createMigrationPackage([
                'output_dir' => $outputDir,
                'skip_storage' => $skipStorage,
            ]);

            $this->newLine();
            $this->info('🎉 Migration Bundle Created Successfully!');
            $this->line("• Package File: <comment>{$result['bundle_filename']}</comment>");
            $this->line("• Absolute Path: <comment>{$result['bundle_path']}</comment>");
            $this->line("• File Size: <info>{$result['human_size']}</info> ({$result['bundle_size']} bytes)");
            $this->line("• SHA-256 Hash: <info>{$result['bundle_sha256']}</info>");

            // Table of record counts
            $counts = $result['manifest']['table_counts'] ?? [];
            $rows = [];
            foreach ($counts as $table => $count) {
                $rows[] = [$table, number_format($count)];
            }
            $this->newLine();
            $this->info('📊 Manifest Record Summary:');
            $this->table(['Table', 'Recorded Rows'], $rows);

            if (isset($result['manifest']['wallet_sum'])) {
                $this->line('• Total Wallet Ledger Sum: <info>₹'.number_format($result['manifest']['wallet_sum'], 2).'</info>');
            }

            $this->newLine();
            $this->comment('🚀 Ready for transfer to new server (AWS, DigitalOcean, Hetzner, etc.):');
            $this->line("   scp {$result['bundle_path']} user@new-server:/var/www/");
            $this->line("   Then on new server run: php artisan app:migration-unpack {$result['bundle_filename']}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Migration export failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
