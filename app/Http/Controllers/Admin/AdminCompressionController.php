<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\CompressionNegotiator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AdminCompressionController extends Controller
{
    /**
     * Display the Adaptive Compression Monitor & Configuration Dashboard.
     */
    public function index(Request $request): View
    {
        $status = CompressionNegotiator::getStatus();

        // Sample realistic billing payload for immediate benchmark visualization
        $sampleBillingJson = json_encode([
            'status' => 'success',
            'mru' => 'LAHGARIYA_LALPUR',
            'records_count' => 10,
            'consumers' => array_map(function ($i) {
                return [
                    'ca_number' => sprintf('8801000000%02d', $i),
                    'consumer_name' => "RAMESH PRASAD SINGH {$i}",
                    'father_name' => 'LATE DINESH SINGH',
                    'meter_no' => sprintf('MTR-%06d', 450000 + $i),
                    'previous_reading' => 1250 + ($i * 15),
                    'working_reading' => 1320 + ($i * 15),
                    'units_consumed' => 70,
                    'bill_status' => 'Submitted',
                    'billing_month' => 6,
                    'billing_year' => 2026,
                    'amount_due' => 450.75,
                    'mobile' => '9876543210',
                ];
            }, range(1, 10)),
        ], JSON_PRETTY_PRINT);

        $benchmark = CompressionNegotiator::runDiagnosticBenchmark($sampleBillingJson, 'Realistic NBPDCL Billing JSON (10 records)');

        return view('admin.compression.index', [
            'status' => $status,
            'benchmark' => $benchmark,
            'samplePayload' => $sampleBillingJson,
        ]);
    }

    /**
     * Update compression settings and algorithm toggles.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'compression_enabled' => 'nullable|boolean',
            'disabled_algorithms' => 'nullable|array',
            'disabled_algorithms.*' => 'string|in:zstd,br,gzip,deflate',
            'priority' => 'nullable|array',
            'priority.*' => 'string|in:zstd,br,gzip,deflate',
        ]);

        $isEnabled = $request->boolean('compression_enabled', true);
        $disabled = $validated['disabled_algorithms'] ?? [];
        $priority = ! empty($validated['priority'])
            ? array_values(array_unique($validated['priority']))
            : config('compression.priority', ['zstd', 'br', 'gzip', 'deflate']);

        SystemSetting::set('compression_enabled', $isEnabled);
        SystemSetting::set('compression_disabled_algorithms', $disabled);
        SystemSetting::set('compression_priority', $priority);

        CompressionNegotiator::clearRuntimeCache();

        return redirect()->route('admin.compression.index')
            ->with('status', 'Adaptive compression settings updated successfully.');
    }

    /**
     * Reset all compression configurations back to system defaults.
     */
    public function reset(): RedirectResponse
    {
        SystemSetting::whereIn('key', [
            'compression_enabled',
            'compression_disabled_algorithms',
            'compression_priority',
        ])->delete();

        Cache::forget('system_setting_compression_enabled');
        Cache::forget('system_setting_compression_disabled_algorithms');
        Cache::forget('system_setting_compression_priority');

        SystemSetting::clearRuntimeCache();
        CompressionNegotiator::clearRuntimeCache();

        return redirect()->route('admin.compression.index')
            ->with('status', 'Compression configuration reset to factory defaults.');
    }

    /**
     * Execute a real-time diagnostic compression benchmark on provided or sample payload.
     */
    public function diagnostic(Request $request): JsonResponse
    {
        $payloadType = $request->input('type', 'json');
        $customText = $request->input('custom_text');

        if (! empty($customText) && is_string($customText)) {
            $payload = $customText;
            $label = 'Custom User Input Payload';
        } elseif ($payloadType === 'html') {
            $payload = str_repeat(
                '<div class="consumer-card"><h2 class="name">NBPDCL Consumer Bill</h2><p class="ca">CA: 88019928371</p><span class="badge bg-green">Submitted</span><div class="reading">Reading: 1450 Units</div></div>',
                25
            );
            $label = 'Synthetic HTML Document Markup';
        } elseif ($payloadType === 'csv') {
            $header = "ca_number,consumer_name,meter_no,prev_reading,curr_reading,status,amount\n";
            $rows = array_map(function ($i) {
                return sprintf("8801000000%02d,CONSUMER TEST %d,MTR%05d,%d,%d,Submitted,%.2f\n", $i, $i, $i, 1000 + $i, 1100 + $i, 450.50);
            }, range(1, 50));
            $payload = $header.implode('', $rows);
            $label = 'Tabular Billing CSV Export (50 rows)';
        } else {
            // Default JSON
            $payload = json_encode([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'records' => array_map(function ($i) {
                    return [
                        'id' => $i,
                        'ca' => sprintf('8801000000%02d', $i),
                        'status' => 'Submitted',
                        'reading' => 200 + $i,
                        'remark' => 'Verified and reconciled by billing agent',
                    ];
                }, range(1, 30)),
            ], JSON_PRETTY_PRINT);
            $label = 'API JSON Response (30 items)';
        }

        $benchmark = CompressionNegotiator::runDiagnosticBenchmark($payload, $label);

        // Find fastest algorithm and best ratio algorithm
        $fastest = null;
        $bestRatio = null;
        $minDuration = PHP_FLOAT_MAX;
        $maxRatio = -1.0;

        foreach ($benchmark['algorithms'] as $algo => $res) {
            if (! empty($res['available']) && isset($res['duration_microseconds']) && isset($res['ratio_percent'])) {
                if ($res['duration_microseconds'] < $minDuration) {
                    $minDuration = $res['duration_microseconds'];
                    $fastest = $algo;
                }
                if ($res['ratio_percent'] > $maxRatio) {
                    $maxRatio = $res['ratio_percent'];
                    $bestRatio = $algo;
                }
            }
        }

        return response()->json([
            'success' => true,
            'benchmark' => $benchmark,
            'summary' => [
                'fastest_algorithm' => $fastest,
                'fastest_duration_ms' => $fastest ? round($minDuration / 1000, 3) : null,
                'highest_ratio_algorithm' => $bestRatio,
                'highest_ratio_percent' => $maxRatio > 0 ? $maxRatio : null,
                'recommended_for_content' => CompressionNegotiator::getPreferredAlgorithmForContentType($payloadType),
            ],
        ]);
    }
}
