<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillRecord;
use App\Models\Mru;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\BillDownloadService;
use App\Services\Extraction\BillExtractionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Smalot\PdfParser\Parser;

class AdminBillController extends Controller
{
    /**
     * Display a listing of all bills across all billing agents / users.
     */
    public function index(Request $request): View
    {
        $userId = $request->get('user_id');
        $mruId = $request->get('mru_id');
        $month = $request->get('month');
        $year = $request->get('year');
        $search = trim($request->get('search', ''));

        $query = BillRecord::withoutGlobalScope('belongs_to_user')
            ->with(['user', 'mru']);

        if (! empty($userId)) {
            $query->where('user_id', $userId);
        }

        if (! empty($mruId)) {
            $query->where('mru_id', $mruId);
        }

        if (! empty($month)) {
            $query->where('billing_month', (int) $month);
        }

        if (! empty($year)) {
            $query->where('billing_year', (int) $year);
        }

        if (! empty($search)) {
            $escaped = addcslashes($search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('ca_number', 'like', "%{$escaped}%")
                    ->orWhere('consumer_name', 'like', "%{$escaped}%")
                    ->orWhere('meter_no', 'like', "%{$escaped}%");
            });
        }

        $bills = $query->orderBy('created_at', 'desc')->paginate(25);

        $users = User::orderBy('name')->get();
        $mrus = Mru::orderBy('code')->get();

        $periods = BillRecord::withoutGlobalScope('belongs_to_user')
            ->select('billing_month', 'billing_year')
            ->distinct()
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get();

        return view('admin.bills.index', compact(
            'bills',
            'users',
            'mrus',
            'periods',
            'userId',
            'mruId',
            'month',
            'year',
            'search'
        ));
    }

    /**
     * Display the NBPDCL Download & Extraction Engine Control Center.
     */
    public function engineSettings(): View
    {
        $settings = [
            'download_driver' => SystemSetting::get('nbpdcl_download_driver', config('nbpdcl.download_driver', 'auto')),
            'extraction_engine' => SystemSetting::get('nbpdcl_extraction_engine', config('nbpdcl.extraction_engine', 'auto')),
            'wss_url' => SystemSetting::get('nbpdcl_wss_url', config('nbpdcl.wss_url')),
            'aes_key' => SystemSetting::get('nbpdcl_aes_key', config('nbpdcl.aes_key')),
            'legacy_url' => SystemSetting::get('nbpdcl_legacy_url', config('nbpdcl.api_url')),
            'timeout' => (int) SystemSetting::get('nbpdcl_timeout', config('nbpdcl.timeout', 45)),
            'concurrency' => (int) SystemSetting::get('nbpdcl_concurrency', config('nbpdcl.concurrency', 10)),
        ];

        return view('admin.bills.engine-settings', compact('settings'));
    }

    /**
     * Update NBPDCL Download & Extraction Engine settings.
     */
    public function updateEngineSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'download_driver' => 'required|in:auto,wss,legacy',
            'extraction_engine' => 'required|in:auto,jasper_unicode,legacy_krutidev',
            'wss_url' => 'required|url',
            'aes_key' => 'required|string',
            'legacy_url' => 'required|url',
            'timeout' => 'required|integer|min:5|max:180',
            'concurrency' => 'required|integer|min:1|max:50',
        ]);

        SystemSetting::set('nbpdcl_download_driver', $validated['download_driver']);
        SystemSetting::set('nbpdcl_extraction_engine', $validated['extraction_engine']);
        SystemSetting::set('nbpdcl_wss_url', $validated['wss_url']);
        SystemSetting::set('nbpdcl_aes_key', $validated['aes_key']);
        SystemSetting::set('nbpdcl_legacy_url', $validated['legacy_url']);
        SystemSetting::set('nbpdcl_timeout', (int) $validated['timeout']);
        SystemSetting::set('nbpdcl_concurrency', (int) $validated['concurrency']);

        return redirect()
            ->route('admin.bills.engine-settings')
            ->with('status', 'NBPDCL Engine & Extraction configuration updated successfully.');
    }

    /**
     * Reset NBPDCL Engine settings to system defaults.
     */
    public function resetEngineSettings(): RedirectResponse
    {
        SystemSetting::set('nbpdcl_download_driver', 'auto');
        SystemSetting::set('nbpdcl_extraction_engine', 'auto');
        SystemSetting::set('nbpdcl_wss_url', config('nbpdcl.wss_url'));
        SystemSetting::set('nbpdcl_aes_key', config('nbpdcl.aes_key'));
        SystemSetting::set('nbpdcl_legacy_url', config('nbpdcl.api_url'));
        SystemSetting::set('nbpdcl_timeout', (int) config('nbpdcl.timeout', 45));
        SystemSetting::set('nbpdcl_concurrency', (int) config('nbpdcl.concurrency', 10));

        return redirect()
            ->route('admin.bills.engine-settings')
            ->with('status', 'NBPDCL Engine configuration restored to system default values.');
    }

    /**
     * Perform an in-flight diagnostic test of download & extraction without altering database records.
     */
    public function testEngineDiagnostic(Request $request, BillDownloadService $downloadService, BillExtractionManager $extractionManager): JsonResponse
    {
        $request->validate([
            'ca_number' => 'required|string',
            'driver' => 'nullable|in:auto,wss,legacy',
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|between:2020,2035',
        ]);

        $ca = trim($request->input('ca_number'));
        $driver = $request->input('driver', SystemSetting::get('nbpdcl_download_driver', 'auto'));
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $downloadResult = $downloadService->downloadSingle($ca, $month, $year, $driver);
        $isValidPdf = $downloadResult['success'];
        $content = $downloadResult['pdf_content'];

        $extractionReport = null;
        if ($isValidPdf && ! empty($content)) {
            try {
                if (! class_exists(Parser::class) && file_exists(base_path('../vendor/autoload.php'))) {
                    require_once base_path('../vendor/autoload.php');
                }
                $parser = new Parser;
                $pdf = $parser->parseContent($content);
                $rawText = $pdf->getText();
                $extracted = $extractionManager->extract($rawText);

                $extractionReport = [
                    'detected_format' => $extracted['detected_format'] ?? 'unknown',
                    'extractor_used' => $extracted['extractor_used'] ?? 'unknown',
                    'consumer_name' => $extracted['consumer_name'] ?? null,
                    'father_name' => $extracted['father_name'] ?? null,
                    'bill_number' => $extracted['bill_number'] ?? null,
                    'bill_month' => $extracted['bill_month'] ?? null,
                    'bill_date' => $extracted['bill_date'] ?? null,
                    'due_date' => $extracted['due_date'] ?? null,
                    'sanctioned_load' => $extracted['sanctioned_load'] ?? null,
                    'phase' => $extracted['phase'] ?? null,
                    'total_amount' => $extracted['total_amount'] ?? 0.0,
                    'energy_charges' => $extracted['energy_charges'] ?? 0.0,
                    'fixed_charges' => $extracted['fixed_charges'] ?? 0.0,
                    'government_subsidy' => $extracted['government_subsidy'] ?? 0.0,
                    'electricity_duty' => $extracted['electricity_duty'] ?? 0.0,
                    'arrears' => $extracted['arrears'] ?? 0.0,
                    'current_reading' => $extracted['current_reading'] ?? null,
                    'previous_reading' => $extracted['previous_reading'] ?? null,
                    'units_consumed' => $extracted['units_consumed'] ?? 0,
                    'meter_no' => $extracted['meter_no'] ?? null,
                    'tariff_category' => $extracted['tariff_category'] ?? null,
                    'billing_basis' => $extracted['billing_basis'] ?? null,
                    'mru' => $extracted['mru'] ?? null,
                    'history_count' => count($extracted['consumption_history'] ?? []),
                ];
            } catch (\Throwable $e) {
                $extractionReport = [
                    'error' => 'Extraction diagnostic failed: '.$e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => $isValidPdf,
            'driver_requested' => $driver,
            'driver_executed' => $downloadResult['driver_used'],
            'resolved_cycle' => $downloadResult['resolved_cycle'] ?? null,
            'lookback_steps' => $downloadResult['lookback_steps'] ?? 0,
            'http_code' => $downloadResult['http_code'],
            'latency_ms' => $downloadResult['latency_ms'],
            'pdf_bytes' => $downloadResult['pdf_bytes'],
            'is_valid_pdf' => $isValidPdf,
            'error' => $downloadResult['error'],
            'upstream_message' => $downloadResult['upstream_message'],
            'extraction' => $extractionReport,
        ]);
    }
}
