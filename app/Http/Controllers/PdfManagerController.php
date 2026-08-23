<?php

namespace App\Http\Controllers;

use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Services\BillParseService;
use App\Services\EngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class PdfManagerController extends Controller
{
    protected BillParseService $parseService;
    protected EngineService $engineService;

    public function __construct(BillParseService $parseService, EngineService $engineService)
    {
        $this->parseService = $parseService;
        $this->engineService = $engineService;
    }

    /**
     * Display the PDF Manager Hub.
     */
    public function index(Request $request): View
    {
        $userId = Auth::id();
        $user = Auth::user();

        // 1. Fetch Available Active Cycles for this user
        $availableCycles = BillRecord::where('user_id', $userId)
            ->select('billing_year', 'billing_month')
            ->distinct()
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get();

        $latestCycle = $availableCycles->first();

        // Check if user provided explicit filters via query string
        $hasExplicitFilter = $request->has('mru_id') || $request->has('month') || $request->has('year') || $request->has('status') || $request->has('search');

        // Default to latest available active cycle on first visit
        if (!$hasExplicitFilter && $latestCycle) {
            $month = (int) $latestCycle->billing_month;
            $year = (int) $latestCycle->billing_year;
            $mruId = null;
        } else {
            $mruId = $request->get('mru_id');
            $month = $request->get('month');
            $year = $request->get('year');
        }

        $status = $request->get('status', 'all');
        $search = trim($request->get('search', ''));
        $viewMode = $request->get('view', 'table'); // 'table' or 'grid'

        // 2. Build Query with User Scoping
        $query = BillRecord::with('mru')
            ->where('user_id', $userId);

        if (!empty($mruId)) {
            $query->where('mru_id', $mruId);
        }

        if (!empty($month)) {
            $query->where('billing_month', (int)$month);
        }

        if (!empty($year)) {
            $query->where('billing_year', (int)$year);
        }

        // Status filters
        if ($status === 'downloaded') {
            $query->where('download_status', 'downloaded')->whereNotNull('pdf_path');
        } elseif ($status === 'missing') {
            $query->where(function ($q) {
                $q->whereNull('pdf_path')->orWhere('download_status', '!=', 'downloaded');
            });
        } elseif ($status === 'parsed') {
            $query->where('parse_status', 'parsed');
        } elseif ($status === 'unparsed') {
            $query->where('download_status', 'downloaded')
                  ->whereNotNull('pdf_path')
                  ->where(function ($q) {
                      $q->whereNull('parse_status')->orWhere('parse_status', '!=', 'parsed');
                  });
        } elseif ($status === 'failed') {
            $query->where('parse_status', 'failed');
        }

        // Search
        if (!empty($search)) {
            $escapedSearch = addcslashes($search, '%_\\');
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('ca_number', 'like', "%{$escapedSearch}%")
                  ->orWhere('consumer_name', 'like', "%{$escapedSearch}%")
                  ->orWhere('meter_no', 'like', "%{$escapedSearch}%")
                  ->orWhere('pdf_filename', 'like', "%{$escapedSearch}%");
            });
        }

        $bills = $query->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->orderBy('ca_number', 'asc')
            ->paginate(24)
            ->withQueryString();

        // 3. Compute Live Storage Metrics for User
        $userPdfBaseDir = "users/{$userId}/pdfs";
        $totalBytes = 0;
        $totalDiskFiles = 0;

        if (Storage::disk('local')->exists($userPdfBaseDir)) {
            $allFiles = Storage::disk('local')->allFiles($userPdfBaseDir);
            foreach ($allFiles as $filePath) {
                if (str_ends_with(strtolower($filePath), '.pdf')) {
                    $totalDiskFiles++;
                    $totalBytes += Storage::disk('local')->size($filePath);
                }
            }
        }

        $allUserBillsCount = BillRecord::where('user_id', $userId)->count();
        $downloadedBillsCount = BillRecord::where('user_id', $userId)
            ->where('download_status', 'downloaded')
            ->whereNotNull('pdf_path')
            ->count();
        $parsedBillsCount = BillRecord::where('user_id', $userId)
            ->where('parse_status', 'parsed')
            ->count();

        $metrics = [
            'total_bills' => $allUserBillsCount,
            'downloaded_count' => $downloadedBillsCount,
            'parsed_count' => $parsedBillsCount,
            'disk_files_count' => $totalDiskFiles,
            'total_size_bytes' => $totalBytes,
            'total_size_formatted' => $this->formatBytes($totalBytes),
            'avg_size_kb' => $totalDiskFiles > 0 ? round(($totalBytes / $totalDiskFiles) / 1024, 1) : 0,
            'download_rate' => $allUserBillsCount > 0 ? round(($downloadedBillsCount / $allUserBillsCount) * 100, 1) : 0,
            'parsed_rate' => $allUserBillsCount > 0 ? round(($parsedBillsCount / $allUserBillsCount) * 100, 1) : 0,
            'plan_tier' => ucfirst($user->plan_tier ?? 'Free'),
            'storage_limit_mb' => $user->storage_limit_mb ?? 100,
            'storage_limit_bytes' => $user->getStorageLimitBytes(),
            'storage_limit_formatted' => $this->formatBytes($user->getStorageLimitBytes()),
            'storage_usage_percent' => $user->getStorageUsagePercent(),
            'is_limit_exceeded' => $user->isStorageLimitExceeded(),
        ];

        // 4. Auxiliary dropdown data
        $mrus = Mru::where('user_id', $userId)->withCount('billRecords')->orderBy('code')->get();

        $availableMonths = BillRecord::where('user_id', $userId)
            ->select('billing_month', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('billing_month')
            ->orderBy('billing_month', 'desc')
            ->pluck('total', 'billing_month')
            ->toArray();

        $availableYears = BillRecord::where('user_id', $userId)
            ->select('billing_year', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('billing_year')
            ->orderBy('billing_year', 'desc')
            ->pluck('total', 'billing_year')
            ->toArray();

        // 5. Compute Cycle-by-Cycle Storage Breakdown for Old Cycle Purge
        $currentMonth = (int) now()->month;
        $currentYear = (int) now()->year;
        $cycleStats = [];

        foreach ($availableCycles as $cycle) {
            $cMonth = (int) $cycle->billing_month;
            $cYear = (int) $cycle->billing_year;
            $isCurrent = ($cMonth === $currentMonth && $cYear === $currentYear);

            $cycleBills = BillRecord::where('user_id', $userId)
                ->where('billing_month', $cMonth)
                ->where('billing_year', $cYear)
                ->get();

            $cycleBytes = 0;
            $cyclePdfCount = 0;

            foreach ($cycleBills as $cb) {
                if (!empty($cb->pdf_path) && Storage::disk('local')->exists($cb->pdf_path)) {
                    $cyclePdfCount++;
                    $cycleBytes += Storage::disk('local')->size($cb->pdf_path);
                }
            }

            $cycleStats[] = [
                'month' => $cMonth,
                'year' => $cYear,
                'label' => date('F Y', mktime(0, 0, 0, $cMonth, 1, $cYear)),
                'is_current' => $isCurrent,
                'is_older' => ($cYear < $currentYear) || ($cYear === $currentYear && $cMonth < $currentMonth),
                'total_bills' => $cycleBills->count(),
                'pdf_count' => $cyclePdfCount,
                'total_bytes' => $cycleBytes,
                'total_size_formatted' => $this->formatBytes($cycleBytes),
            ];
        }

        // Attach file sizes for current page items
        foreach ($bills as $b) {
            if (!empty($b->pdf_path) && Storage::disk('local')->exists($b->pdf_path)) {
                $b->file_size_bytes = Storage::disk('local')->size($b->pdf_path);
                $b->file_size_formatted = $this->formatBytes($b->file_size_bytes);
                $b->file_exists = true;
            } else {
                $b->file_size_bytes = 0;
                $b->file_size_formatted = '0 KB';
                $b->file_exists = false;
            }
        }

        return view('pdf-manager.index', compact(
            'bills',
            'metrics',
            'mrus',
            'availableCycles',
            'availableMonths',
            'availableYears',
            'cycleStats',
            'mruId',
            'month',
            'year',
            'status',
            'search',
            'viewMode'
        ));
    }

    /**
     * Batch export selected or filtered PDFs as a ZIP archive.
     */
    public function batchDownload(Request $request): StreamedResponse|JsonResponse
    {
        $userId = Auth::id();
        $billIds = $request->input('bill_ids');

        $query = BillRecord::with('mru')
            ->where('user_id', $userId)
            ->whereNotNull('pdf_path');

        if (!empty($billIds) && is_array($billIds)) {
            $query->whereIn('id', $billIds);
        } else {
            // Apply filter scope
            if ($request->filled('mru_id')) $query->where('mru_id', $request->mru_id);
            if ($request->filled('month')) $query->where('billing_month', (int)$request->month);
            if ($request->filled('year')) $query->where('billing_year', (int)$request->year);
            if ($request->filled('search')) {
                $escapedSearch = addcslashes($request->search, '%_\\');
                $query->where(function ($q) use ($escapedSearch) {
                    $q->where('ca_number', 'like', "%{$escapedSearch}%")
                      ->orWhere('consumer_name', 'like', "%{$escapedSearch}%");
                });
            }
        }

        $bills = $query->get();

        if ($bills->isEmpty()) {
            return response()->json(['error' => 'No matching bills with stored PDFs found to download.'], 404);
        }

        $zipFileName = "NBPDCL_PDFs_" . date('Ymd_His') . ".zip";
        $zipTempPath = tempnam(sys_get_temp_dir(), 'pdf_zip_');

        try {
            $zip = new ZipArchive();
            if ($zip->open($zipTempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                return response()->json(['error' => 'Failed to initialize ZIP archive.'], 500);
            }

            $addedFiles = 0;
            foreach ($bills as $bill) {
                if (!empty($bill->pdf_path) && Storage::disk('local')->exists($bill->pdf_path)) {
                    $content = Storage::disk('local')->get($bill->pdf_path);
                    $mruCode = $bill->mru ? $bill->mru->code : 'GENERAL';
                    $zipPath = "{$bill->billing_year}/{$bill->billing_month}/{$mruCode}/{$bill->ca_number}.pdf";
                    $zip->addFromString($zipPath, $content);
                    $addedFiles++;
                }
            }

            $zip->close();

            if ($addedFiles === 0) {
                if (file_exists($zipTempPath)) @unlink($zipTempPath);
                return response()->json(['error' => 'None of the selected bill records have valid physical PDF files on disk.'], 404);
            }

            return response()->streamDownload(function () use ($zipTempPath) {
                try {
                    $stream = fopen($zipTempPath, 'r');
                    fpassthru($stream);
                    fclose($stream);
                } finally {
                    if (file_exists($zipTempPath)) {
                        @unlink($zipTempPath);
                    }
                }
            }, $zipFileName, [
                'Content-Type' => 'application/zip',
            ]);
        } catch (\Throwable $e) {
            if (file_exists($zipTempPath)) {
                @unlink($zipTempPath);
            }
            throw $e;
        }
    }

    /**
     * Batch re-parse data from local PDF files.
     */
    public function batchReparse(Request $request): JsonResponse
    {
        $request->validate([
            'bill_ids' => 'required|array',
            'bill_ids.*' => 'integer',
        ]);

        $userId = Auth::id();
        $billIds = $request->input('bill_ids');

        $results = $this->parseService->parseSpecificBills($userId, $billIds);

        return response()->json([
            'success' => true,
            'message' => "Successfully re-parsed {$results['success']} out of {$results['total']} selected PDF bills.",
            'results' => $results,
        ]);
    }

    /**
     * Batch force re-download selected CAs.
     */
    public function batchRedownload(Request $request): JsonResponse
    {
        $request->validate([
            'bill_ids' => 'required|array',
            'bill_ids.*' => 'integer',
        ]);

        $userId = Auth::id();
        $billIds = $request->input('bill_ids');

        $bills = BillRecord::where('user_id', $userId)
            ->whereIn('id', $billIds)
            ->get();

        if ($bills->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No matching bill records found.'], 404);
        }

        $grouped = $bills->groupBy(fn($b) => "{$b->billing_month}_{$b->billing_year}_{$b->mru_id}");
        $totalSuccess = 0;
        $totalCount = 0;

        foreach ($grouped as $group) {
            $first = $group->first();
            $cas = $group->pluck('ca_number')->unique()->toArray();
            $res = $this->engineService->downloadAndParseBills($cas, $userId, $first->billing_month, $first->billing_year, $first->mru_id);
            $totalSuccess += ($res['success'] ?? 0);
            $totalCount += count($cas);
        }

        return response()->json([
            'success' => true,
            'message' => "Re-downloaded {$totalSuccess} out of {$totalCount} selected bills.",
            'success_count' => $totalSuccess,
            'total_count' => $totalCount,
        ]);
    }

    /**
     * Batch delete physical PDF files and reset records.
     */
    public function batchDelete(Request $request): JsonResponse
    {
        $request->validate([
            'bill_ids' => 'required|array',
            'bill_ids.*' => 'integer',
        ]);

        $userId = Auth::id();
        $billIds = $request->input('bill_ids');

        $bills = BillRecord::where('user_id', $userId)
            ->whereIn('id', $billIds)
            ->get();

        $deletedFiles = 0;
        foreach ($bills as $bill) {
            if (!empty($bill->pdf_path) && Storage::disk('local')->exists($bill->pdf_path)) {
                Storage::disk('local')->delete($bill->pdf_path);
                $deletedFiles++;
            }

            $bill->update([
                'pdf_path' => null,
                'pdf_filename' => null,
                'download_status' => 'pending',
                'parse_status' => 'pending',
                'current_reading' => null,
                'total_amount' => 0,
                'units_consumed' => 0,
                'error_message' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deletedFiles} PDF files and reset {$bills->count()} bill records to Pending.",
            'deleted_files' => $deletedFiles,
            'reset_records' => $bills->count(),
        ]);
    }

    /**
     * Run storage integrity & health check scan.
     */
    public function healthCheck(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $userPdfBaseDir = "users/{$userId}/pdfs";

        $missingOnDisk = [];
        $corruptedPdfs = [];
        $orphanedPdfs = [];

        // 1. Check all DB records marked as downloaded
        $dbBills = BillRecord::where('user_id', $userId)
            ->where('download_status', 'downloaded')
            ->whereNotNull('pdf_path')
            ->get();

        $knownPaths = [];
        foreach ($dbBills as $bill) {
            $knownPaths[$bill->pdf_path] = $bill;

            if (!Storage::disk('local')->exists($bill->pdf_path)) {
                $missingOnDisk[] = [
                    'id' => $bill->id,
                    'ca_number' => $bill->ca_number,
                    'period' => "{$bill->billing_month}/{$bill->billing_year}",
                    'pdf_path' => $bill->pdf_path,
                ];
            } else {
                $size = Storage::disk('local')->size($bill->pdf_path);
                if ($size < 500) {
                    $corruptedPdfs[] = [
                        'id' => $bill->id,
                        'ca_number' => $bill->ca_number,
                        'period' => "{$bill->billing_month}/{$bill->billing_year}",
                        'size' => $size,
                        'pdf_path' => $bill->pdf_path,
                    ];
                }
            }
        }

        // 2. Scan physical disk to find orphaned files
        if (Storage::disk('local')->exists($userPdfBaseDir)) {
            $diskFiles = Storage::disk('local')->allFiles($userPdfBaseDir);
            foreach ($diskFiles as $file) {
                if (str_ends_with(strtolower($file), '.pdf')) {
                    if (!isset($knownPaths[$file])) {
                        // Check if exists in DB at all
                        $existsInDb = BillRecord::where('user_id', $userId)->where('pdf_path', $file)->exists();
                        if (!$existsInDb) {
                            $orphanedPdfs[] = [
                                'path' => $file,
                                'filename' => basename($file),
                                'size' => Storage::disk('local')->size($file),
                            ];
                        }
                    }
                }
            }
        }

        $healthy = empty($missingOnDisk) && empty($corruptedPdfs) && empty($orphanedPdfs);

        return response()->json([
            'success' => true,
            'is_healthy' => $healthy,
            'missing_count' => count($missingOnDisk),
            'corrupted_count' => count($corruptedPdfs),
            'orphaned_count' => count($orphanedPdfs),
            'missing_files' => $missingOnDisk,
            'corrupted_files' => $corruptedPdfs,
            'orphaned_files' => $orphanedPdfs,
        ]);
    }

    /**
     * 1-Click Sync & Auto-Heal Storage.
     */
    public function syncStorage(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $userPdfBaseDir = "users/{$userId}/pdfs";
        $healedMissing = 0;
        $registeredOrphans = 0;

        // 1. Reset records with missing physical files
        $dbBills = BillRecord::where('user_id', $userId)
            ->where('download_status', 'downloaded')
            ->whereNotNull('pdf_path')
            ->get();

        foreach ($dbBills as $bill) {
            if (!Storage::disk('local')->exists($bill->pdf_path)) {
                $bill->update([
                    'pdf_path' => null,
                    'pdf_filename' => null,
                    'download_status' => 'pending',
                    'parse_status' => 'pending',
                    'error_message' => 'Physical PDF missing on disk; reset by Storage Sync',
                ]);
                $healedMissing++;
            }
        }

        // 2. Discover and register orphaned PDFs
        if (Storage::disk('local')->exists($userPdfBaseDir)) {
            $diskFiles = Storage::disk('local')->allFiles($userPdfBaseDir);
            foreach ($diskFiles as $file) {
                if (str_ends_with(strtolower($file), '.pdf')) {
                    $existsInDb = BillRecord::where('user_id', $userId)->where('pdf_path', $file)->exists();
                    if (!$existsInDb) {
                        // Extract metadata from path: users/{userId}/pdfs/{year}/{month}/{mruCode}/{ca}.pdf
                        $parts = explode('/', $file);
                        if (count($parts) >= 6) {
                            $year = (int)$parts[3];
                            $month = (int)$parts[4];
                            $mruCode = $parts[5];
                            $ca = pathinfo(end($parts), PATHINFO_FILENAME);

                            if ($year >= 2020 && $month >= 1 && $month <= 12 && preg_match('/^\d+$/', $ca)) {
                                $mru = Mru::firstOrCreate(
                                    ['user_id' => $userId, 'code' => $mruCode],
                                    ['name' => "MRU {$mruCode}", 'status' => 'active']
                                );

                                $record = BillRecord::updateOrCreate(
                                    [
                                        'user_id' => $userId,
                                        'ca_number' => $ca,
                                        'billing_month' => $month,
                                        'billing_year' => $year,
                                    ],
                                    [
                                        'mru_id' => $mru->id,
                                        'pdf_path' => $file,
                                        'pdf_filename' => basename($file),
                                        'download_status' => 'downloaded',
                                        'download_date' => now(),
                                    ]
                                );

                                // Trigger parsing for newly registered orphan
                                try {
                                    $this->parseService->parseSpecificBills($userId, [$record->id]);
                                } catch (\Throwable $e) {
                                    // continue
                                }

                                $registeredOrphans++;
                            }
                        }
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Storage Sync completed: Resolved {$healedMissing} missing file references and registered {$registeredOrphans} orphaned PDFs.",
            'healed_missing' => $healedMissing,
            'registered_orphans' => $registeredOrphans,
        ]);
    }

    /**
     * Purge physical PDF files for an entire billing cycle while strictly preserving database ledger records.
     */
    public function purgeCyclePdfs(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $olderThanCurrent = $request->boolean('older_than_current');
        $month = $request->filled('month') ? (int) $request->month : null;
        $year = $request->filled('year') ? (int) $request->year : null;

        $currentMonth = (int) now()->month;
        $currentYear = (int) now()->year;

        $query = BillRecord::where('user_id', $userId)
            ->whereNotNull('pdf_path');

        if ($olderThanCurrent) {
            $query->where(function ($q) use ($currentMonth, $currentYear) {
                $q->where('billing_year', '<', $currentYear)
                  ->orWhere(function ($sub) use ($currentMonth, $currentYear) {
                      $sub->where('billing_year', '=', $currentYear)
                          ->where('billing_month', '<', $currentMonth);
                  });
            });
        } elseif ($month && $year) {
            $query->where('billing_month', $month)->where('billing_year', $year);
        } else {
            return response()->json(['success' => false, 'message' => 'Please specify a valid billing cycle or choose older cycles.'], 422);
        }

        $targetScope = $request->get('target_scope', 'all');
        if ($targetScope === 'parsed') {
            $query->where('parse_status', 'parsed');
        } elseif ($targetScope === 'unparsed') {
            $query->where(function ($q) {
                $q->whereNull('parse_status')->orWhere('parse_status', '!=', 'parsed');
            });
        } elseif ($targetScope === 'failed') {
            $query->where('parse_status', 'failed');
        }

        if ($request->filled('bill_ids') && is_array($request->bill_ids)) {
            $query->whereIn('id', $request->bill_ids);
        }

        $bills = $query->get();

        if ($bills->isEmpty()) {
            return response()->json(['success' => true, 'message' => 'No stored PDF files found matching the selected criteria.', 'deleted_files' => 0, 'freed_bytes' => 0]);
        }

        $freedBytes = 0;
        $deletedFiles = 0;

        foreach ($bills as $bill) {
            if (!empty($bill->pdf_path) && Storage::disk('local')->exists($bill->pdf_path)) {
                $freedBytes += Storage::disk('local')->size($bill->pdf_path);
                Storage::disk('local')->delete($bill->pdf_path);
                $deletedFiles++;
            }

            // CRITICAL: Set pdf_path = null & download_status = pending, but PRESERVE all readings, amounts, consumer names, remarks, and review statuses!
            $bill->update([
                'pdf_path' => null,
                'pdf_filename' => null,
                'download_status' => 'pending',
            ]);
        }

        $freedFormatted = $this->formatBytes($freedBytes);

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$deletedFiles} PDF files ({$freedFormatted} storage space freed). All consumer readings, units, and amounts remain 100% preserved in database.",
            'deleted_files' => $deletedFiles,
            'freed_bytes' => $freedBytes,
            'freed_formatted' => $freedFormatted,
            'preserved_records' => $bills->count(),
        ]);
    }

    /**
     * Handle manual single/bulk PDF or ZIP upload.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'files.*' => 'required|file|mimes:pdf,zip|max:51200', // max 50MB per file
            'billing_month' => 'required|integer|between:1,12',
            'billing_year' => 'required|integer|between:2020,2035',
            'mru_id' => 'nullable|integer',
        ]);

        $userId = Auth::id();
        $user = Auth::user();

        // Quota Guard: Check if user exceeded storage limit
        if ($user && $user->isStorageLimitExceeded()) {
            return response()->json([
                'success' => false,
                'message' => "Storage Quota Exceeded ({$user->storage_limit_mb} MB limit). Please purge older cycle PDFs in PDF Manager or upgrade your subscription plan.",
            ], 422);
        }

        $month = (int)$request->billing_month;
        $year = (int)$request->billing_year;
        $mruId = $request->mru_id;

        $mru = !empty($mruId) ? Mru::where('user_id', $userId)->where('id', $mruId)->first() : null;
        $mruCode = $mru ? $mru->code : 'GENERAL';

        $uploadedCount = 0;
        $processedRecords = [];

        $files = $request->file('files');
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $uploadedFile) {
            $ext = strtolower($uploadedFile->getClientOriginalExtension());

            if ($ext === 'pdf') {
                $filename = $uploadedFile->getClientOriginalName();
                $ca = pathinfo($filename, PATHINFO_FILENAME);

                // If filename is not pure digits, fallback to auto CA or sanitized name
                if (!preg_match('/^\d+$/', $ca)) {
                    // Try to extract first numeric sequence from filename
                    if (preg_match('/(\d{8,15})/', $filename, $matches)) {
                        $ca = $matches[1];
                    }
                }

                if (!empty($ca)) {
                    $storagePath = "users/{$userId}/pdfs/{$year}/{$month}/{$mruCode}/{$ca}.pdf";
                    Storage::disk('local')->put($storagePath, file_get_contents($uploadedFile->getRealPath()));

                    $record = BillRecord::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'ca_number' => $ca,
                            'billing_month' => $month,
                            'billing_year' => $year,
                        ],
                        [
                            'mru_id' => $mru ? $mru->id : null,
                            'pdf_path' => $storagePath,
                            'pdf_filename' => "{$ca}.pdf",
                            'download_status' => 'downloaded',
                            'download_date' => now(),
                        ]
                    );

                    $processedRecords[] = $record->id;
                    $uploadedCount++;
                }
            } elseif ($ext === 'zip') {
                $zip = new ZipArchive();
                if ($zip->open($uploadedFile->getRealPath()) === true) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $entryName = $zip->getNameIndex($i);
                        if (str_ends_with(strtolower($entryName), '.pdf')) {
                            $pdfContent = $zip->getFromIndex($i);
                            $baseName = basename($entryName);
                            $ca = pathinfo($baseName, PATHINFO_FILENAME);

                            if (preg_match('/(\d{8,15})/', $ca, $m)) {
                                $ca = $m[1];
                            }

                            if (!empty($ca) && preg_match('/^\d+$/', $ca)) {
                                $storagePath = "users/{$userId}/pdfs/{$year}/{$month}/{$mruCode}/{$ca}.pdf";
                                Storage::disk('local')->put($storagePath, $pdfContent);

                                $record = BillRecord::updateOrCreate(
                                    [
                                        'user_id' => $userId,
                                        'ca_number' => $ca,
                                        'billing_month' => $month,
                                        'billing_year' => $year,
                                    ],
                                    [
                                        'mru_id' => $mru ? $mru->id : null,
                                        'pdf_path' => $storagePath,
                                        'pdf_filename' => "{$ca}.pdf",
                                        'download_status' => 'downloaded',
                                        'download_date' => now(),
                                    ]
                                );

                                $processedRecords[] = $record->id;
                                $uploadedCount++;
                            }
                        }
                    }
                    $zip->close();
                }
            }
        }

        // Auto parse uploaded records
        if (!empty($processedRecords)) {
            try {
                $this->parseService->parseSpecificBills($userId, $processedRecords);
            } catch (\Throwable $e) {
                // non-fatal
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully uploaded and processed {$uploadedCount} PDF bill(s).",
            'uploaded_count' => $uploadedCount,
        ]);
    }

    /**
     * Helper to format bytes into readable units.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
