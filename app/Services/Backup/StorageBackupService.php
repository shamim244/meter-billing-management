<?php

namespace App\Services\Backup;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class StorageBackupService
{
    /**
     * Paths/patterns to strictly exclude from storage backups.
     */
    protected array $exclusions = [
        'backups',
        'framework/cache',
        'framework/sessions',
        'framework/views',
        'framework/testing',
        'logs',
        '.git',
        'node_modules',
        'vendor',
    ];

    /**
     * Archive the storage directory into a target ZIP file.
     *
     * @param string $outputPath Absolute path to the output .zip file
     * @param array $extraIncludePaths Optional specific directories to include
     * @return array Metadata about archived files and size
     */
    public function archive(string $outputPath, array $extraIncludePaths = []): array
    {
        \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($outputPath));

        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Failed to open or create ZIP archive at: {$outputPath}");
        }

        $baseStorage = storage_path('app');
        $targets = ! empty($extraIncludePaths) ? $extraIncludePaths : [$baseStorage];

        $totalFiles = 0;
        $totalBytes = 0;

        foreach ($targets as $targetPath) {
            if (! file_exists($targetPath)) {
                continue;
            }

            if (is_file($targetPath)) {
                $relativeName = basename($targetPath);
                $zip->addFile($targetPath, $relativeName);
                $totalFiles++;
                $totalBytes += filesize($targetPath);
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($targetPath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $itemPath = $item->getPathname();
                $relativePath = substr($itemPath, strlen(rtrim($targetPath, DIRECTORY_SEPARATOR)) + 1);
                $normalizedRelative = str_replace('\\', '/', $relativePath);

                if ($this->shouldExclude($normalizedRelative)) {
                    continue;
                }

                if ($item->isDir()) {
                    $zip->addEmptyDir($normalizedRelative);
                } elseif ($item->isFile()) {
                    $zip->addFile($itemPath, $normalizedRelative);
                    $totalFiles++;
                    $totalBytes += $item->getSize();
                }
            }
        }

        if ($totalFiles === 0) {
            $zip->addFromString('.empty_snapshot', "Storage had no files at time of backup.\n");
        }

        $zip->close();

        return [
            'file_count' => $totalFiles,
            'uncompressed_bytes' => $totalBytes,
            'compressed_bytes' => file_exists($outputPath) ? filesize($outputPath) : 0,
        ];
    }

    /**
     * Check if a relative path matches any exclusion rule.
     */
    protected function shouldExclude(string $relativePath): bool
    {
        foreach ($this->exclusions as $pattern) {
            if (str_starts_with($relativePath, $pattern) || str_contains($relativePath, '/' . $pattern)) {
                return true;
            }
        }
        return false;
    }
}
