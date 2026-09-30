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
use Illuminate\Support\Facades\Validator;
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
            'extraction_filter_enabled' => (bool) SystemSetting::get('extraction_filter_enabled', true),
            'calculation_filter_enabled' => (bool) SystemSetting::get('calculation_filter_enabled', true),
            'filter_non_ok_bases' => (bool) SystemSetting::get('filter_non_ok_bases', true),
            'filter_zero_unit_months' => (bool) SystemSetting::get('filter_zero_unit_months', true),
            'global_spike_multiplier' => (float) SystemSetting::get('global_spike_multiplier', 2.0),
            'min_spike_unit_buffer' => (int) SystemSetting::get('min_spike_unit_buffer', 30),
            'agriculture_spike_bypass' => (bool) SystemSetting::get('agriculture_spike_bypass', true),
            'agriculture_spike_multiplier' => (float) SystemSetting::get('agriculture_spike_multiplier', 4.0),
            'commercial_spike_multiplier' => (float) SystemSetting::get('commercial_spike_multiplier', 2.5),
            'domestic_spike_multiplier' => (float) SystemSetting::get('domestic_spike_multiplier', 2.0),
            'dynamic_colorization_enabled' => (bool) SystemSetting::get('dynamic_colorization_enabled', true),
            'color_amount_safe_ceiling' => (float) SystemSetting::get('color_amount_safe_ceiling', 500.0),
            'color_amount_warning_ceiling' => (float) SystemSetting::get('color_amount_warning_ceiling', 1500.0),
            'color_amount_danger_floor' => (float) SystemSetting::get('color_amount_danger_floor', 2500.0),
            'color_units_safe_ceiling' => (int) SystemSetting::get('color_units_safe_ceiling', 50),
            'color_units_warning_ceiling' => (int) SystemSetting::get('color_units_warning_ceiling', 120),
            'color_units_danger_floor' => (int) SystemSetting::get('color_units_danger_floor', 200),
        ];

        return view('admin.bills.engine-settings', compact('settings'));
    }

    /**
     * Update NBPDCL Download & Extraction Engine settings.
     */
    public function updateEngineSettings(Request $request): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'download_driver' => 'sometimes|required|in:auto,wss,legacy',
            'extraction_engine' => 'sometimes|required|in:auto,jasper_unicode,legacy_krutidev',
            'wss_url' => 'sometimes|required|url',
            'aes_key' => 'sometimes|required|string',
            'legacy_url' => 'sometimes|required|url',
            'timeout' => 'sometimes|required|integer|min:5|max:180',
            'concurrency' => 'sometimes|required|integer|min:1|max:50',
            'extraction_filter_enabled' => 'nullable',
            'calculation_filter_enabled' => 'nullable',
            'filter_non_ok_bases' => 'nullable',
            'filter_zero_unit_months' => 'nullable',
            'global_spike_multiplier' => 'nullable|numeric|min:1.0|max:10.0',
            'min_spike_unit_buffer' => 'nullable|integer|min:0|max:500',
            'agriculture_spike_bypass' => 'nullable',
            'agriculture_spike_multiplier' => 'nullable|numeric|min:1.0|max:20.0',
            'commercial_spike_multiplier' => 'nullable|numeric|min:1.0|max:10.0',
            'domestic_spike_multiplier' => 'nullable|numeric|min:1.0|max:10.0',
            'dynamic_colorization_enabled' => 'nullable',
            'color_amount_safe_ceiling' => 'nullable|numeric|min:0',
            'color_amount_warning_ceiling' => 'nullable|numeric|min:0',
            'color_amount_danger_floor' => 'nullable|numeric|min:0',
            'color_units_safe_ceiling' => 'nullable|integer|min:0',
            'color_units_warning_ceiling' => 'nullable|integer|min:0',
            'color_units_danger_floor' => 'nullable|integer|min:0',
        ]);

        $validator->after(function ($validator) use ($request) {
            $hasAmtSafe = $request->filled('color_amount_safe_ceiling');
            $hasAmtWarn = $request->filled('color_amount_warning_ceiling');
            $hasAmtDanger = $request->filled('color_amount_danger_floor');

            if ($hasAmtSafe || $hasAmtWarn || $hasAmtDanger) {
                $safe = $hasAmtSafe ? (float) $request->input('color_amount_safe_ceiling') : (float) SystemSetting::get('color_amount_safe_ceiling', 500.0);
                $warn = $hasAmtWarn ? (float) $request->input('color_amount_warning_ceiling') : (float) SystemSetting::get('color_amount_warning_ceiling', 1500.0);
                $danger = $hasAmtDanger ? (float) $request->input('color_amount_danger_floor') : (float) SystemSetting::get('color_amount_danger_floor', 2500.0);

                if ($safe > $warn) {
                    $validator->errors()->add('color_amount_safe_ceiling', 'Safe zone ceiling cannot exceed warning zone ceiling.');
                }
                if ($warn > $danger) {
                    $validator->errors()->add('color_amount_warning_ceiling', 'Warning zone ceiling cannot exceed danger zone floor.');
                }
            }

            $hasUnitSafe = $request->filled('color_units_safe_ceiling');
            $hasUnitWarn = $request->filled('color_units_warning_ceiling');
            $hasUnitDanger = $request->filled('color_units_danger_floor');

            if ($hasUnitSafe || $hasUnitWarn || $hasUnitDanger) {
                $uSafe = $hasUnitSafe ? (int) $request->input('color_units_safe_ceiling') : (int) SystemSetting::get('color_units_safe_ceiling', 50);
                $uWarn = $hasUnitWarn ? (int) $request->input('color_units_warning_ceiling') : (int) SystemSetting::get('color_units_warning_ceiling', 120);
                $uDanger = $hasUnitDanger ? (int) $request->input('color_units_danger_floor') : (int) SystemSetting::get('color_units_danger_floor', 200);

                if ($uSafe > $uWarn) {
                    $validator->errors()->add('color_units_safe_ceiling', 'Safe zone units ceiling cannot exceed warning zone units ceiling.');
                }
                if ($uWarn > $uDanger) {
                    $validator->errors()->add('color_units_warning_ceiling', 'Warning zone units ceiling cannot exceed danger zone units floor.');
                }
            }
        });

        $validated = $validator->validate();

        if (isset($validated['download_driver'])) {
            SystemSetting::set('nbpdcl_download_driver', $validated['download_driver']);
        }
        if (isset($validated['extraction_engine'])) {
            SystemSetting::set('nbpdcl_extraction_engine', $validated['extraction_engine']);
        }
        if (isset($validated['wss_url'])) {
            SystemSetting::set('nbpdcl_wss_url', $validated['wss_url']);
        }
        if (isset($validated['aes_key'])) {
            SystemSetting::set('nbpdcl_aes_key', $validated['aes_key']);
        }
        if (isset($validated['legacy_url'])) {
            SystemSetting::set('nbpdcl_legacy_url', $validated['legacy_url']);
        }
        if (isset($validated['timeout'])) {
            SystemSetting::set('nbpdcl_timeout', (int) $validated['timeout']);
        }
        if (isset($validated['concurrency'])) {
            SystemSetting::set('nbpdcl_concurrency', (int) $validated['concurrency']);
        }

        if ($request->has('has_spike_settings')) {
            SystemSetting::set('extraction_filter_enabled', $request->boolean('extraction_filter_enabled'));
            SystemSetting::set('calculation_filter_enabled', $request->boolean('calculation_filter_enabled'));
            SystemSetting::set('filter_non_ok_bases', $request->boolean('filter_non_ok_bases'));
            SystemSetting::set('filter_zero_unit_months', $request->boolean('filter_zero_unit_months'));
            SystemSetting::set('agriculture_spike_bypass', $request->boolean('agriculture_spike_bypass'));

            if ($request->filled('global_spike_multiplier')) {
                SystemSetting::set('global_spike_multiplier', (float) $request->input('global_spike_multiplier'));
            }
            if ($request->filled('min_spike_unit_buffer')) {
                SystemSetting::set('min_spike_unit_buffer', (int) $request->input('min_spike_unit_buffer'));
            }
            if ($request->filled('agriculture_spike_multiplier')) {
                SystemSetting::set('agriculture_spike_multiplier', (float) $request->input('agriculture_spike_multiplier'));
            }
            if ($request->filled('commercial_spike_multiplier')) {
                SystemSetting::set('commercial_spike_multiplier', (float) $request->input('commercial_spike_multiplier'));
            }
            if ($request->filled('domestic_spike_multiplier')) {
                SystemSetting::set('domestic_spike_multiplier', (float) $request->input('domestic_spike_multiplier'));
            }
        } else {
            // Support partial updates without unintentionally resetting unmentioned toggles
            if ($request->has('extraction_filter_enabled')) {
                SystemSetting::set('extraction_filter_enabled', $request->boolean('extraction_filter_enabled'));
            }
            if ($request->has('calculation_filter_enabled')) {
                SystemSetting::set('calculation_filter_enabled', $request->boolean('calculation_filter_enabled'));
            }
            if ($request->has('filter_non_ok_bases')) {
                SystemSetting::set('filter_non_ok_bases', $request->boolean('filter_non_ok_bases'));
            }
            if ($request->has('filter_zero_unit_months')) {
                SystemSetting::set('filter_zero_unit_months', $request->boolean('filter_zero_unit_months'));
            }
            if ($request->has('agriculture_spike_bypass')) {
                SystemSetting::set('agriculture_spike_bypass', $request->boolean('agriculture_spike_bypass'));
            }
            if ($request->filled('global_spike_multiplier')) {
                SystemSetting::set('global_spike_multiplier', (float) $request->input('global_spike_multiplier'));
            }
            if ($request->filled('min_spike_unit_buffer')) {
                SystemSetting::set('min_spike_unit_buffer', (int) $request->input('min_spike_unit_buffer'));
            }
            if ($request->filled('agriculture_spike_multiplier')) {
                SystemSetting::set('agriculture_spike_multiplier', (float) $request->input('agriculture_spike_multiplier'));
            }
            if ($request->filled('commercial_spike_multiplier')) {
                SystemSetting::set('commercial_spike_multiplier', (float) $request->input('commercial_spike_multiplier'));
            }
            if ($request->filled('domestic_spike_multiplier')) {
                SystemSetting::set('domestic_spike_multiplier', (float) $request->input('domestic_spike_multiplier'));
            }
        }

        if ($request->has('has_color_settings')) {
            SystemSetting::set('dynamic_colorization_enabled', $request->boolean('dynamic_colorization_enabled'));
            if ($request->filled('color_amount_safe_ceiling')) {
                SystemSetting::set('color_amount_safe_ceiling', (float) $request->input('color_amount_safe_ceiling'));
            }
            if ($request->filled('color_amount_warning_ceiling')) {
                SystemSetting::set('color_amount_warning_ceiling', (float) $request->input('color_amount_warning_ceiling'));
            }
            if ($request->filled('color_amount_danger_floor')) {
                SystemSetting::set('color_amount_danger_floor', (float) $request->input('color_amount_danger_floor'));
            }
            if ($request->filled('color_units_safe_ceiling')) {
                SystemSetting::set('color_units_safe_ceiling', (int) $request->input('color_units_safe_ceiling'));
            }
            if ($request->filled('color_units_warning_ceiling')) {
                SystemSetting::set('color_units_warning_ceiling', (int) $request->input('color_units_warning_ceiling'));
            }
            if ($request->filled('color_units_danger_floor')) {
                SystemSetting::set('color_units_danger_floor', (int) $request->input('color_units_danger_floor'));
            }
        } else {
            if ($request->has('dynamic_colorization_enabled')) {
                SystemSetting::set('dynamic_colorization_enabled', $request->boolean('dynamic_colorization_enabled'));
            }
            if ($request->filled('color_amount_safe_ceiling')) {
                SystemSetting::set('color_amount_safe_ceiling', (float) $request->input('color_amount_safe_ceiling'));
            }
            if ($request->filled('color_amount_warning_ceiling')) {
                SystemSetting::set('color_amount_warning_ceiling', (float) $request->input('color_amount_warning_ceiling'));
            }
            if ($request->filled('color_amount_danger_floor')) {
                SystemSetting::set('color_amount_danger_floor', (float) $request->input('color_amount_danger_floor'));
            }
            if ($request->filled('color_units_safe_ceiling')) {
                SystemSetting::set('color_units_safe_ceiling', (int) $request->input('color_units_safe_ceiling'));
            }
            if ($request->filled('color_units_warning_ceiling')) {
                SystemSetting::set('color_units_warning_ceiling', (int) $request->input('color_units_warning_ceiling'));
            }
            if ($request->filled('color_units_danger_floor')) {
                SystemSetting::set('color_units_danger_floor', (int) $request->input('color_units_danger_floor'));
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'NBPDCL Engine & Smart Average Configuration updated successfully.',
            ]);
        }

        return redirect()
            ->route('admin.bills.engine-settings')
            ->with('status', 'NBPDCL Engine & Smart Average Configuration updated successfully.');
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

        SystemSetting::set('extraction_filter_enabled', true);
        SystemSetting::set('calculation_filter_enabled', true);
        SystemSetting::set('filter_non_ok_bases', true);
        SystemSetting::set('filter_zero_unit_months', true);
        SystemSetting::set('global_spike_multiplier', 2.0);
        SystemSetting::set('min_spike_unit_buffer', 30);
        SystemSetting::set('agriculture_spike_bypass', true);
        SystemSetting::set('agriculture_spike_multiplier', 4.0);
        SystemSetting::set('commercial_spike_multiplier', 2.5);
        SystemSetting::set('domestic_spike_multiplier', 2.0);

        SystemSetting::set('dynamic_colorization_enabled', true);
        SystemSetting::set('color_amount_safe_ceiling', 500.0);
        SystemSetting::set('color_amount_warning_ceiling', 1500.0);
        SystemSetting::set('color_amount_danger_floor', 2500.0);
        SystemSetting::set('color_units_safe_ceiling', 50);
        SystemSetting::set('color_units_warning_ceiling', 120);
        SystemSetting::set('color_units_danger_floor', 200);

        return redirect()
            ->route('admin.bills.engine-settings')
            ->with('status', 'NBPDCL Engine & Smart Average configuration restored to system default values.');
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
