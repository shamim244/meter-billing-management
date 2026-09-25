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

        $wssUrl = SystemSetting::get('nbpdcl_wss_url', config('nbpdcl.wss_url'));
        $aesKey = SystemSetting::get('nbpdcl_aes_key', config('nbpdcl.aes_key'));
        $legacyUrl = SystemSetting::get('nbpdcl_legacy_url', config('nbpdcl.api_url'));
        $timeout = (int) SystemSetting::get('nbpdcl_timeout', 45);

        $startTime = microtime(true);
        $driverUsed = ($driver === 'legacy') ? 'legacy' : 'wss';

        $executeRequest = function (string $mode) use ($ca, $month, $year, $wssUrl, $aesKey, $legacyUrl, $timeout, $downloadService) {
            $ch = curl_init();
            if ($mode === 'legacy') {
                $url = $legacyUrl.urlencode($ca);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ]);
            } else {
                $payload = [
                    'req' => [
                        'billMonth' => sprintf('%02d', $month),
                        'billYear' => (string) $year,
                        'scno' => $ca,
                        'lang' => 'H',
                        'printtype' => 'PDF',
                        'modulename' => 'WSS',
                        'genPDF' => 'N',
                        'finalflag' => 'X',
                    ],
                    'action' => 'billing/getviewbillprint',
                    'method' => 'POST',
                    'auth' => 'TOKEN',
                ];
                $encryptedBody = $downloadService->encryptCryptoJS(json_encode($payload), $aesKey);

                curl_setopt($ch, CURLOPT_URL, $wssUrl);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encryptedBody);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: text/plain',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ]);
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($body && ($pos = strpos($body, '%PDF')) !== false) {
                $body = substr($body, $pos);
            }

            return [$code, $body, $err];
        };

        [$httpCode, $content, $curlErr] = $executeRequest($driverUsed);

        // Auto fallback
        if ($driver === 'auto' && $driverUsed === 'wss' && ($httpCode !== 200 || empty($content) || ! str_starts_with($content, '%PDF'))) {
            $driverUsed = 'legacy (auto-fallback)';
            [$httpCode, $content, $curlErr] = $executeRequest('legacy');
        }

        $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
        $isValidPdf = ($httpCode === 200 && ! empty($content) && str_starts_with($content, '%PDF'));

        $extractionReport = null;
        if ($isValidPdf) {
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
                    'bill_month' => $extracted['bill_month'] ?? null,
                    'total_amount' => $extracted['total_amount'] ?? 0.0,
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
            'driver_executed' => $driverUsed,
            'http_code' => $httpCode,
            'latency_ms' => $latencyMs,
            'pdf_bytes' => $isValidPdf ? strlen($content) : 0,
            'is_valid_pdf' => $isValidPdf,
            'error' => $isValidPdf ? null : ($curlErr ?: "HTTP {$httpCode} - No valid %PDF stream received"),
            'extraction' => $extractionReport,
        ]);
    }
}
