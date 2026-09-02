<?php

namespace App\Services\Backup;

use App\Models\SystemBackup;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class BackupRetentionService
{
    /**
     * Run the backup retention policy and prune expired archives.
     *
     * @param bool $dryRun If true, returns list of candidates without deleting
     * @return array Summary of kept and pruned backups
     */
    public function prune(bool $dryRun = false): array
    {
        $allBackups = SystemBackup::where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();

        $now = Carbon::now();
        $keepIds = [];
        $pruneIds = [];

        // Daily bucket: Keep all backups within the last 7 days
        $sevenDaysAgo = $now->copy()->subDays(7);
        // Weekly bucket: 4 weeks ago
        $fourWeeksAgo = $now->copy()->subWeeks(4);
        // Monthly bucket: 6 months ago
        $sixMonthsAgo = $now->copy()->subMonths(6);

        $weeklyBuckets = [];
        $monthlyBuckets = [];

        foreach ($allBackups as $backup) {
            $created = $backup->created_at;

            // 1. Keep everything within the last 7 days
            if ($created->greaterThanOrEqualTo($sevenDaysAgo)) {
                $keepIds[] = $backup->id;
                continue;
            }

            // 2. Between 7 days and 4 weeks: Keep 1 per week (e.g. key: "2026-W34")
            if ($created->greaterThanOrEqualTo($fourWeeksAgo)) {
                $weekKey = $created->format('Y-W');
                if (! isset($weeklyBuckets[$weekKey])) {
                    $weeklyBuckets[$weekKey] = $backup->id;
                    $keepIds[] = $backup->id;
                    continue;
                }
            }

            // 3. Between 4 weeks and 6 months: Keep 1 per month (e.g. key: "2026-03")
            if ($created->greaterThanOrEqualTo($sixMonthsAgo)) {
                $monthKey = $created->format('Y-m');
                if (! isset($monthlyBuckets[$monthKey])) {
                    $monthlyBuckets[$monthKey] = $backup->id;
                    $keepIds[] = $backup->id;
                    continue;
                }
            }

            // Otherwise, candidate for pruning
            $pruneIds[] = $backup->id;
        }

        $prunedList = [];

        if (! $dryRun && ! empty($pruneIds)) {
            $candidates = SystemBackup::whereIn('id', $pruneIds)->get();

            foreach ($candidates as $candidate) {
                // Delete physical file from disk
                try {
                    $storagePath = $candidate->getStoragePath();
                    if (Storage::disk($candidate->disk)->exists($storagePath)) {
                        Storage::disk($candidate->disk)->delete($storagePath);
                    }
                } catch (\Throwable $e) {
                    // Log or handle disk errors
                }

                $prunedList[] = [
                    'id' => $candidate->id,
                    'backup_code' => $candidate->backup_code,
                    'filename' => $candidate->filename,
                    'size' => $candidate->human_size,
                    'created_at' => $candidate->created_at->toDateTimeString(),
                ];

                $candidate->delete();
            }
        }

        return [
            'total_analyzed' => $allBackups->count(),
            'total_kept' => count(array_unique($keepIds)),
            'total_pruned' => count($pruneIds),
            'pruned_items' => $prunedList,
            'dry_run' => $dryRun,
        ];
    }
}
