<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CreateBackupJob;
use App\Models\SystemBackup;
use App\Services\Backup\BackupRetentionService;
use App\Services\Backup\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminBackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService,
        protected BackupRetentionService $retentionService
    ) {}

    /**
     * Display the SaaS Disaster Recovery & Backups Cockpit.
     */
    public function index(Request $request)
    {
        $query = SystemBackup::with('triggeredBy')->latest();

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $backups = $query->paginate(15)->withQueryString();
        $stats = $this->backupService->getSystemStorageStats();

        return view('admin.backups.index', compact('backups', 'stats'));
    }

    /**
     * Trigger a new system backup.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:db_only,storage_only,full',
            'async' => 'nullable|boolean',
        ]);

        $type = $request->input('type', 'db_only');
        $isAsync = $request->boolean('async', false);
        $userId = auth()->id();

        if ($isAsync) {
            CreateBackupJob::dispatch($type, $userId);
            return back()->with('success', 'Backup task queued successfully! Processing in background.');
        }

        try {
            $backup = $this->backupService->createBackup($type, $userId);
            return back()->with('success', "Backup [{$backup->filename}] created successfully ({$backup->human_size}) in {$backup->duration_seconds}s!");
        } catch (\Throwable $e) {
            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Download a completed backup archive.
     */
    public function download(SystemBackup $backup)
    {
        $storagePath = $backup->getStoragePath();

        if (! Storage::disk($backup->disk)->exists($storagePath)) {
            return back()->with('error', 'Backup file does not exist on disk.');
        }

        return Storage::disk($backup->disk)->download($storagePath, $backup->filename, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Return manifest JSON metadata for modal inspection.
     */
    public function manifest(SystemBackup $backup)
    {
        return response()->json([
            'backup_code' => $backup->backup_code,
            'type' => $backup->type_label,
            'filename' => $backup->filename,
            'disk' => $backup->disk,
            'size' => $backup->human_size,
            'sha256_hash' => $backup->sha256_hash,
            'duration_seconds' => $backup->duration_seconds,
            'status' => $backup->status,
            'error_message' => $backup->error_message,
            'meta' => $backup->meta,
            'created_at' => $backup->created_at->toDateTimeString(),
            'triggered_by' => $backup->triggeredBy?->name ?? 'Automated Schedule',
        ]);
    }

    /**
     * Delete a backup archive.
     */
    public function destroy(SystemBackup $backup)
    {
        $filename = $backup->filename;
        $this->backupService->deleteBackup($backup);

        return back()->with('success', "Backup [{$filename}] deleted successfully.");
    }

    /**
     * Run manual retention cleanup.
     */
    public function clean(Request $request)
    {
        $result = $this->retentionService->prune(false);
        $count = $result['total_pruned'];

        return back()->with('success', "Retention policy executed: {$count} expired backup(s) pruned.");
    }
}
