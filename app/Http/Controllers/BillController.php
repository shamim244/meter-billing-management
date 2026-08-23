<?php

namespace App\Http\Controllers;

use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Services\EngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class BillController extends Controller
{
    protected EngineService $engineService;

    public function __construct(EngineService $engineService)
    {
        $this->engineService = $engineService;
    }

    /**
     * Process bulk CA numbers (Download & Parse).
     */
    public function process(Request $request): JsonResponse
    {
        $request->validate([
            'ca_numbers' => 'required',
        ]);

        $rawInput = $request->input('ca_numbers');
        
        // Handle array or newline/comma-delimited text
        if (is_array($rawInput)) {
            $caList = $rawInput;
        } else {
            $caList = preg_split('/[\r\n,]+/', (string) $rawInput);
        }

        // Clean and filter valid CA numbers
        $caNumbers = array_values(array_unique(array_filter(array_map('trim', $caList), function ($val) {
            return !empty($val) && preg_match('/^\d+$/', $val);
        })));

        if (empty($caNumbers)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid CA numbers found in input. Please provide numeric CA numbers.',
            ], 422);
        }

        $userId = Auth::id();
        $results = $this->engineService->downloadAndParseBills($caNumbers, $userId);

        return response()->json([
            'success' => true,
            'message' => "Successfully processed {$results['success']} out of {$results['total']} bills.",
            'results' => $results,
        ]);
    }

    /**
     * Download and parse a single CA bill on demand.
     */
    public function downloadSingle(Request $request): JsonResponse
    {
        $request->validate([
            'ca_number' => 'required|string',
            'billing_month' => 'nullable|integer|between:1,12',
            'billing_year' => 'nullable|integer|between:2020,2035',
            'mru_id' => 'nullable|integer',
        ]);

        $ca = trim($request->ca_number);
        $month = (int)$request->input('billing_month', now()->month);
        $year = (int)$request->input('billing_year', now()->year);
        $mruId = $request->input('mru_id');

        $userId = Auth::id();
        $results = $this->engineService->downloadAndParseBills([$ca], $userId, $month, $year, $mruId);

        $success = ($results['success'] ?? 0) > 0;
        $bill = BillRecord::where('user_id', $userId)
            ->where('ca_number', $ca)
            ->where('billing_month', $month)
            ->where('billing_year', $year)
            ->with(['mru'])
            ->first();

        if ($bill) {
            $statusRecord = BillStatus::where('user_id', $userId)
                ->where('ca_number', $ca)
                ->where('billing_month', $month)
                ->where('billing_year', $year)
                ->first();
            $bill->review_status = $statusRecord ? $statusRecord->status : 'pending';
            $bill->remark = $statusRecord ? $statusRecord->remark : null;
            $bill->has_pdf = !empty($bill->pdf_path);
        }

        return response()->json([
            'success' => $success,
            'message' => $success ? "Successfully downloaded bill for CA {$ca}." : "Failed to download bill for CA {$ca}.",
            'bill' => $bill,
            'results' => $results,
        ], $success ? 200 : 422);
    }

    /**
     * Incrementally sync missing/failed bills for a given month, year, and optional MRU.
     */
    public function syncMissing(Request $request): JsonResponse
    {
        $request->validate([
            'billing_month' => 'required|integer|between:1,12',
            'billing_year' => 'required|integer|between:2020,2035',
            'mru_id' => 'nullable|integer',
        ]);

        $userId = Auth::id();
        $month = (int)$request->input('billing_month');
        $year = (int)$request->input('billing_year');
        $mruId = $request->input('mru_id');

        // Find consumer accounts in this MRU/user context
        $query = ConsumerAccount::where('user_id', $userId)->where('status', 'active');
        if (!empty($mruId)) {
            $query->where('mru_id', $mruId);
        }
        $allCAs = $query->pluck('ca_number')->toArray();

        if (empty($allCAs)) {
            return response()->json([
                'success' => false,
                'message' => 'No active consumers found to sync.',
                'missing_count' => 0,
            ], 422);
        }

        // Find already downloaded CAs for this period
        $downloadedCAs = BillRecord::where('user_id', $userId)
            ->where('billing_month', $month)
            ->where('billing_year', $year)
            ->where('download_status', 'downloaded')
            ->whereNotNull('pdf_path')
            ->pluck('ca_number')
            ->toArray();

        $missingCAs = array_values(array_diff($allCAs, $downloadedCAs));

        if (empty($missingCAs)) {
            return response()->json([
                'success' => true,
                'message' => 'All bills in this period are already downloaded!',
                'missing_count' => 0,
                'synced_count' => 0,
            ]);
        }

        $results = $this->engineService->downloadAndParseBills($missingCAs, $userId, $month, $year, $mruId);

        return response()->json([
            'success' => true,
            'message' => "Synced {$results['success']} out of {$results['total']} missing bills.",
            'missing_count' => count($missingCAs),
            'synced_count' => $results['success'],
            'results' => $results,
        ]);
    }

    /**
     * Update review status for a specific bill (Submitted, Critical, Doubt, Pending).
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'ca_number' => 'required|string',
            'billing_month' => 'required|integer|between:1,12',
            'billing_year' => 'required|integer',
            'status' => 'required|string|in:submitted,critical,doubt,pending',
        ]);

        $userId = Auth::id();
        $ca = $request->input('ca_number');
        $month = (int) $request->input('billing_month');
        $year = (int) $request->input('billing_year');
        $status = $request->input('status');

        \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $ca, $month, $year, $status) {
            $billStatus = BillStatus::firstOrNew([
                'user_id' => $userId,
                'ca_number' => $ca,
                'billing_month' => $month,
                'billing_year' => $year,
            ]);

            if ($status === 'pending' && empty($billStatus->remark)) {
                $billStatus->delete();
            } else {
                $billStatus->status = $status;
                $billStatus->save();
            }

            // Synchronize with bill_records table
            BillRecord::where('user_id', $userId)
                ->where('ca_number', $ca)
                ->where('billing_month', $month)
                ->where('billing_year', $year)
                ->update(['review_status' => $status]);
        });

        return response()->json([
            'success' => true,
            'ca_number' => $ca,
            'status' => $status,
        ]);
    }

    /**
     * Save or clear remark for a bill.
     */
    public function saveRemark(Request $request): JsonResponse
    {
        $request->validate([
            'ca_number' => 'required|string',
            'billing_month' => 'required|integer|between:1,12',
            'billing_year' => 'required|integer',
            'remark' => 'nullable|string|max:1000',
        ]);

        $userId = Auth::id();
        $ca = $request->input('ca_number');
        $month = (int) $request->input('billing_month');
        $year = (int) $request->input('billing_year');
        $remark = trim((string) $request->input('remark'));

        $finalRemark = !empty($remark) ? $remark : null;

        \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $ca, $month, $year, $finalRemark) {
            $billStatus = BillStatus::firstOrNew([
                'user_id' => $userId,
                'ca_number' => $ca,
                'billing_month' => $month,
                'billing_year' => $year,
            ]);

            $billStatus->remark = $finalRemark;
            if (!$billStatus->exists && empty($billStatus->status)) {
                $billStatus->status = 'pending';
            }
            $billStatus->save();

            // Synchronize with bill_records table
            BillRecord::where('user_id', $userId)
                ->where('ca_number', $ca)
                ->where('billing_month', $month)
                ->where('billing_year', $year)
                ->update(['remark' => $finalRemark]);
        });

        return response()->json([
            'success' => true,
            'ca_number' => $ca,
            'remark' => $finalRemark,
        ]);
    }

    /**
     * Save or update tag for a bill.
     */
    public function saveTag(Request $request): JsonResponse
    {
        $request->validate([
            'ca_number' => 'required|string',
            'billing_month' => 'required|integer|between:1,12',
            'billing_year' => 'required|integer',
            'tag' => 'required|string|max:64',
        ]);

        $userId = Auth::id();
        $ca = $request->input('ca_number');
        $month = (int) $request->input('billing_month');
        $year = (int) $request->input('billing_year');
        $tag = trim((string) $request->input('tag')) ?: 'OK';

        \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $ca, $month, $year, $tag) {
            $billStatus = BillStatus::firstOrNew([
                'user_id' => $userId,
                'ca_number' => $ca,
                'billing_month' => $month,
                'billing_year' => $year,
            ]);

            $billStatus->tag = $tag;
            if (!$billStatus->exists && empty($billStatus->status)) {
                $billStatus->status = 'pending';
            }
            $billStatus->save();

            // Synchronize with bill_records table
            BillRecord::where('user_id', $userId)
                ->where('ca_number', $ca)
                ->where('billing_month', $month)
                ->where('billing_year', $year)
                ->update(['tag' => $tag]);
        });

        $tagService = app(\App\Services\BillTagService::class);

        return response()->json([
            'success' => true,
            'ca_number' => $ca,
            'tag' => $tag,
            'display_tag' => $tagService->getDisplayLabel($tag),
            'full_tag' => $tagService->getFullLabel($tag),
        ]);
    }

    /**
     * Stream or download a single bill PDF.
     */
    public function viewPdf(BillRecord $bill): BinaryFileResponse|JsonResponse
    {
        $currentUser = Auth::user();
        if (!$currentUser) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        // Strict user isolation guard
        if ($bill->user_id !== $currentUser->id && !$currentUser->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized access to bill document.'], 403);
        }

        if (empty($bill->pdf_path) || str_contains($bill->pdf_path, '..') || !str_ends_with(strtolower($bill->pdf_path), '.pdf')) {
            return response()->json(['error' => 'Invalid or missing PDF path.'], 404);
        }

        if (!Storage::disk('local')->exists($bill->pdf_path)) {
            return response()->json(['error' => 'PDF file not found in storage.'], 404);
        }

        $fullPath = Storage::disk('local')->path($bill->pdf_path);
        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($bill->pdf_path) . '"',
        ]);
    }

    /**
     * Export matching bills as CSV download.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        $mruId = $request->get('mru_id');
        $filter = $request->get('filter', $request->get('status', 'all'));
        $search = trim($request->get('search', ''));

        $query = BillRecord::with('mru')
            ->where('billing_month', $month)
            ->where('billing_year', $year);

        if (!empty($mruId)) {
            $query->where('mru_id', $mruId);
        }

        if (!empty($search)) {
            $escapedSearch = addcslashes($search, '%_\\');
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('ca_number', 'like', "%{$escapedSearch}%")
                  ->orWhere('consumer_name', 'like', "%{$escapedSearch}%")
                  ->orWhere('meter_no', 'like', "%{$escapedSearch}%");
            });
        }

        $bills = $query->orderBy('ca_number')->get();

        $userStatuses = BillStatus::where('billing_month', $month)
            ->where('billing_year', $year)
            ->get()
            ->keyBy('ca_number');

        $tagFilter = $request->get('tag_filter', $request->get('tag', 'all'));

        if (!empty($filter) && $filter !== 'all') {
            $bills = $bills->filter(function ($b) use ($userStatuses, $filter) {
                $st = $userStatuses[$b->ca_number] ?? null;
                $currentStatus = $st ? $st->status : 'pending';
                return $currentStatus === $filter;
            });
        }

        if (!empty($tagFilter) && $tagFilter !== 'all') {
            $bills = $bills->filter(function ($b) use ($userStatuses, $tagFilter) {
                $st = $userStatuses[$b->ca_number] ?? null;
                $currentTag = !empty($b->tag) ? $b->tag : ($st ? ($st->tag ?? 'OK') : 'OK');
                return strtoupper($currentTag) === strtoupper($tagFilter);
            });
        }

        $fileName = "nbpdcl_bills_{$year}_{$month}_" . date('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($bills, $userStatuses) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel compatibility

            fputcsv($output, [
                'Consumer No',
                'Consumer Name',
                'MRU Code',
                'Current Reading',
                'Previous Reading',
                'Units Consumed',
                'Amount Due (₹)',
                'Meter Number',
                'Bill Month',
                'Status',
                'Tag',
                'Remark'
            ]);

            $tagService = app(\App\Services\BillTagService::class);

            foreach ($bills as $bill) {
                $st = $userStatuses[$bill->ca_number] ?? null;
                $status = $st ? $st->status : 'pending';
                $remark = $st ? ($st->remark ?? '') : '';
                $rawTag = !empty($bill->tag) ? $bill->tag : ($st ? ($st->tag ?? 'OK') : 'OK');
                $tagLabel = $tagService->getFullLabel($rawTag);

                fputcsv($output, [
                    $bill->ca_number,
                    $bill->consumer_name,
                    $bill->mru ? $bill->mru->code : 'GENERAL',
                    $bill->current_reading ?? '—',
                    $bill->previous_reading ?? '—',
                    $bill->units_consumed ?? 0,
                    $bill->total_amount > 0 ? $bill->total_amount : 'N/A',
                    $bill->meter_no ?? '—',
                    $bill->bill_month_label ?: "{$bill->billing_month}/{$bill->billing_year}",
                    ucfirst($status),
                    $tagLabel,
                    $remark,
                ]);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Export matching bills as an organized ZIP archive.
     */
    public function exportZip(Request $request): StreamedResponse|JsonResponse
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        $mruId = $request->get('mru_id');
        $filter = $request->get('filter', $request->get('status', 'all'));
        $search = trim($request->get('search', ''));

        $query = BillRecord::with('mru')
            ->where('billing_month', $month)
            ->where('billing_year', $year)
            ->whereNotNull('pdf_path');

        if (!empty($mruId)) {
            $query->where('mru_id', $mruId);
        }

        if (!empty($search)) {
            $escapedSearch = addcslashes($search, '%_\\');
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('ca_number', 'like', "%{$escapedSearch}%")
                  ->orWhere('consumer_name', 'like', "%{$escapedSearch}%")
                  ->orWhere('meter_no', 'like', "%{$escapedSearch}%");
            });
        }

        $bills = $query->get();

        if (!empty($filter) && $filter !== 'all') {
            $userStatuses = BillStatus::where('billing_month', $month)
                ->where('billing_year', $year)
                ->pluck('status', 'ca_number')
                ->toArray();

            $bills = $bills->filter(function ($b) use ($userStatuses, $filter) {
                $st = $userStatuses[$b->ca_number] ?? 'pending';
                return $st === $filter;
            });
        }

        if ($bills->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No bills with downloadable PDFs matched the filter.'], 404);
            }
            return redirect()->back()->with('error', 'No bills with downloadable PDFs matched the current filter.');
        }

        $zipFileName = "NBPDCL_Bills_{$year}_{$month}_" . time() . ".zip";
        $zipTempPath = tempnam(sys_get_temp_dir(), 'zip_');

        try {
            $zip = new ZipArchive();
            if ($zip->open($zipTempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                return response()->json(['error' => 'Failed to create zip archive.'], 500);
            }

            foreach ($bills as $bill) {
                if (!empty($bill->pdf_path) && Storage::disk('local')->exists($bill->pdf_path)) {
                    $fileContent = Storage::disk('local')->get($bill->pdf_path);
                    $mruFolder = $bill->mru ? $bill->mru->code : 'GENERAL';
                    $zipInternalPath = "{$year}/{$month}/{$mruFolder}/{$bill->ca_number}.pdf";
                    $zip->addFromString($zipInternalPath, $fileContent);
                }
            }

            $zip->close();

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
     * Show consumer historical bill records.
     */
    public function history(string $caNumber): View
    {
        $userId = Auth::id();
        $account = ConsumerAccount::with('mru')
            ->where('user_id', $userId)
            ->where('ca_number', $caNumber)
            ->firstOrFail();
        
        $bills = BillRecord::with('mru')
            ->where('user_id', $userId)
            ->where('ca_number', $caNumber)
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get();

        $statuses = BillStatus::where('user_id', $userId)
            ->where('ca_number', $caNumber)
            ->get()
            ->keyBy(fn($s) => "{$s->billing_year}_{$s->billing_month}");

        return view('bills.history', compact('account', 'bills', 'statuses'));
    }

    /**
     * Delete/Reset single bill PDF from physical disk and database.
     */
    public function deletePdf(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|exists:bill_records,id',
        ]);

        $userId = Auth::id();
        $bill = BillRecord::where('user_id', $userId)
            ->where('id', $request->id)
            ->firstOrFail();

        // Remove from physical disk if exists
        if (!empty($bill->pdf_path) && Storage::disk('local')->exists($bill->pdf_path)) {
            Storage::disk('local')->delete($bill->pdf_path);
        }

        // Reset PDF and parsed status
        $bill->pdf_path = null;
        $bill->pdf_filename = null;
        $bill->download_status = 'pending';
        $bill->parse_status = 'pending';
        $bill->current_reading = null;
        $bill->total_amount = 0;
        $bill->units_consumed = 0;
        $bill->error_message = null;
        $bill->save();

        return response()->json([
            'success' => true,
            'message' => "PDF for CA {$bill->ca_number} deleted and reset to Pending.",
        ]);
    }
}
