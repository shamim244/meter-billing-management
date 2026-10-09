<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PrecompressAssetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:precompress {--force : Force recompression of all assets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-compress public CSS and JS static assets with Gzip and Brotli for zero-CPU server delivery';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Pre-compressing static CSS and JS assets for zero-CPU delivery...');

        $directories = [
            public_path('build'),
            public_path('js'),
            public_path('css'),
        ];

        $totalCompressed = 0;
        $bytesSaved = 0;

        foreach ($directories as $dir) {
            if (! File::isDirectory($dir)) {
                continue;
            }

            $files = File::allFiles($dir);

            foreach ($files as $file) {
                $ext = strtolower($file->getExtension());
                if (! in_array($ext, ['css', 'js', 'json', 'svg'])) {
                    continue;
                }

                $path = $file->getRealPath();
                $gzPath = $path.'.gz';
                $brPath = $path.'.br';

                $originalContent = file_get_contents($path);
                $originalSize = strlen($originalContent);

                if ($originalSize < 512) {
                    continue; // Skip tiny files
                }

                $needsGz = $this->option('force') || ! file_exists($gzPath) || filemtime($path) > filemtime($gzPath);
                if ($needsGz) {
                    $gzData = gzencode($originalContent, 9);
                    file_put_contents($gzPath, $gzData);
                    $totalCompressed++;
                    $bytesSaved += ($originalSize - strlen($gzData));
                }

                if (function_exists('brotli_compress')) {
                    $needsBr = $this->option('force') || ! file_exists($brPath) || filemtime($path) > filemtime($brPath);
                    if ($needsBr) {
                        $brData = brotli_compress($originalContent, 11);
                        file_put_contents($brPath, $brData);
                    }
                }
            }
        }

        $mbSaved = round($bytesSaved / (1024 * 1024), 2);
        $this->info("✅ Pre-compression complete! Compressed {$totalCompressed} asset(s), saved ~{$mbSaved} MB transfer payload.");

        return Command::SUCCESS;
    }
}
