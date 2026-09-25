<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Migration\ServerMigrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminServerMigrationController extends Controller
{
    public function __construct(
        protected ServerMigrationService $migrationService
    ) {}

    /**
     * Display the universal server migration dashboard.
     */
    public function index(): View
    {
        $preflight = $this->migrationService->runPreflightCheck();
        $migrationDir = storage_path('app/migrations');
        File::ensureDirectoryExists($migrationDir);

        $bundles = [];
        $files = File::files($migrationDir);
        foreach ($files as $file) {
            if ($file->getExtension() === 'zip') {
                $bundles[] = [
                    'filename' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size' => $this->formatBytes($file->getSize()),
                    'timestamp' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }

        // Sort descending by modified time
        usort($bundles, fn ($a, $b) => strcmp($b['timestamp'], $a['timestamp']));

        $currentEndpoint = url('/api/v1');

        return view('admin.migration.index', [
            'preflight' => $preflight,
            'bundles' => $bundles,
            'currentEndpoint' => $currentEndpoint,
        ]);
    }

    /**
     * Generate and download a universal migration package.
     */
    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        try {
            $skipStorage = $request->boolean('skip_storage');
            $result = $this->migrationService->createMigrationPackage([
                'skip_storage' => $skipStorage,
            ]);

            return response()->download($result['bundle_path'])->deleteFileAfterSend(false);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to generate migration package: '.$e->getMessage());
        }
    }

    /**
     * Inspect an uploaded migration bundle and return its manifest.
     */
    public function inspect(Request $request): JsonResponse
    {
        $request->validate([
            'bundle' => 'required|file|mimes:zip|max:524288', // 512MB max
        ]);

        $file = $request->file('bundle');
        $tempPath = $file->getRealPath();

        try {
            $inspection = $this->migrationService->inspectPackage($tempPath);

            return response()->json([
                'success' => true,
                'inspection' => $inspection,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Restore an uploaded migration bundle and return post-flight audit.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'bundle' => 'required|file|mimes:zip|max:524288',
            'skip_storage' => 'nullable|boolean',
        ]);

        $file = $request->file('bundle');
        $tempPath = $file->getRealPath();

        try {
            $result = $this->migrationService->restoreMigrationPackage($tempPath, [
                'skip_storage' => $request->boolean('skip_storage'),
            ]);

            if ($result['verified']) {
                return back()->with('success', '🎉 Migration restored and 100% verified successfully! Zero data loss.');
            }

            return back()->with('warning', 'Migration applied, but some table counts had discrepancies. Please inspect audit logs.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Migration restore failed: '.$e->getMessage());
        }
    }

    /**
     * Download an existing migration bundle.
     */
    public function download(string $filename): BinaryFileResponse|RedirectResponse
    {
        $safeFilename = basename($filename);
        $path = storage_path("app/migrations/{$safeFilename}");

        if (File::exists($path)) {
            return response()->download($path);
        }

        return back()->with('error', 'Bundle not found.');
    }

    /**
     * Delete an existing migration bundle.
     */
    public function destroy(string $filename): RedirectResponse
    {
        $safeFilename = basename($filename);
        $path = storage_path("app/migrations/{$safeFilename}");

        if (File::exists($path)) {
            File::delete($path);

            return back()->with('success', "Bundle [{$safeFilename}] deleted successfully.");
        }

        return back()->with('error', 'Bundle not found.');
    }

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
