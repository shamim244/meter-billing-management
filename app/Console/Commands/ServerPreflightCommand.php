<?php

namespace App\Console\Commands;

use App\Services\Migration\ServerMigrationService;
use Illuminate\Console\Command;

class ServerPreflightCommand extends Command
{
    protected $signature = 'app:preflight-check';

    protected $description = 'Audit server environment readiness for hosting (PHP 8.4, extensions, permissions, database, redis)';

    public function handle(ServerMigrationService $migrationService): int
    {
        $this->info('🔍 Running Full Server Environment Audit...');
        $this->newLine();

        $check = $migrationService->runPreflightCheck();

        // 1. PHP Version
        $phpStatus = $check['php_satisfies'] ? '✅ OK' : '❌ FAIL (Requires >= 8.3)';
        $this->line("• PHP Version: <info>{$check['php_version']}</info> [{$phpStatus}]");

        // 2. Extensions Table
        $extRows = [];
        foreach ($check['extensions'] as $ext => $loaded) {
            $extRows[] = [
                $ext,
                $loaded ? '<info>INSTALLED</info>' : '<fg=red>MISSING</fg=red>',
                $loaded ? '✅' : '❌',
            ];
        }
        $this->table(['Extension', 'Status', 'Pass'], $extRows);

        // 3. Writable Directories
        $dirRows = [];
        foreach ($check['writable_paths'] as $path => $writable) {
            $dirRows[] = [
                $path,
                $writable ? '<info>WRITABLE</info>' : '<fg=red>READ-ONLY</fg=red>',
                $writable ? '✅' : '❌',
            ];
        }
        $this->table(['Directory', 'Permission', 'Pass'], $dirRows);

        // 4. Connectivity
        $dbStatus = $check['database']['connected'] ? '✅ CONNECTED' : '❌ FAILED ('.$check['database']['error'].')';
        $this->line("• Database ({$check['database']['driver']}): {$dbStatus}");

        $redisStatus = $check['redis']['connected'] ? '✅ CONNECTED' : '⚠️ OFFLINE (Optional for cache/queue)';
        $this->line("• Redis: {$redisStatus}");

        // 5. PHP Limits
        $this->line("• Memory Limit: <comment>{$check['memory_limit']}</comment>");
        $this->line("• Upload Max Filesize: <comment>{$check['upload_max_filesize']}</comment>");
        $this->line("• Post Max Size: <comment>{$check['post_max_size']}</comment>");

        $this->newLine();
        if ($check['ready']) {
            $this->info('🎉 Server is 100% READY for production hosting!');

            return self::SUCCESS;
        }

        $this->error('⚠️ Server has failed critical requirements. Please review above.');

        return self::FAILURE;
    }
}
