<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\MeterInspection;
use App\Models\Mru;
use App\Services\Reading\ReadingGapReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReadingGapController extends Controller
{
    public function __construct(
        protected ReadingGapReconciliationService $gapService
    ) {}

    /**
     * Display the Admin System-Wide Reading Gap & Unbilled Energy Intelligence Hub.
     */
    public function index(): View
    {
        return view('admin.reading-gap.index');
    }

    /**
     * Fetch aggregated cross-tenant intelligence, MRU breakdown, and recent audit trail.
     */
    public function getData(): JsonResponse
    {
        // 1. System-Wide Aggregates
        $systemKpis = $this->gapService->getMruGapAnalytics(null, null);
        $systemKpis['total_unbilled_mwh'] = round(($systemKpis['total_unbilled_kwh'] ?? 0) / 1000, 2);
        $systemKpis['estimated_financial_gap_lakhs'] = round(($systemKpis['estimated_financial_gap_rs'] ?? 0) / 100000, 2);

        // 2. MRU Breakdown
        $mrus = Mru::with(['user:id,name,email'])->get();
        $allConsumers = ConsumerAccount::whereNotNull('mru_id')
            ->select('id', 'user_id', 'mru_id', 'ca_number', 'last_physical_reading', 'last_inspected_at', 'tariff_category')
            ->get()
            ->groupBy('mru_id');

        $allCaNumbers = ConsumerAccount::pluck('ca_number')->filter()->unique();
        $latestBills = BillRecord::withoutGlobalScopes()
            ->whereIn('ca_number', $allCaNumbers)
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get()
            ->groupBy('ca_number')
            ->map(fn ($group) => $group->first());

        $mruBreakdown = [];
        foreach ($mrus as $mru) {
            $consumers = $allConsumers->get($mru->id, collect());
            if ($consumers->isEmpty()) {
                continue;
            }

            $unbilledKwh = 0;
            $overPostedKwh = 0;
            $underPostedCount = 0;
            $overPostedCount = 0;

            foreach ($consumers as $c) {
                if ($c->last_physical_reading === null || ! is_numeric($c->last_physical_reading)) {
                    continue;
                }
                $bill = $latestBills->get($c->ca_number);
                $posted = $bill ? (int) ($bill->previous_reading ?? $bill->current_reading ?? 0) : 0;
                $rollover = $this->gapService->detectMeterRollover((int) $c->last_physical_reading, $posted);
                $gap = $rollover['adjusted_gap'];

                if ($gap > 0) {
                    $unbilledKwh += $gap;
                    $underPostedCount++;
                } elseif ($gap < 0) {
                    $overPostedKwh += abs($gap);
                    $overPostedCount++;
                }
            }

            $mruBreakdown[] = [
                'id' => $mru->id,
                'code' => $mru->code,
                'name' => $mru->name,
                'agent_name' => $mru->user?->name ?? 'Unassigned',
                'total_consumers' => $consumers->count(),
                'under_posted_count' => $underPostedCount,
                'over_posted_count' => $overPostedCount,
                'unbilled_kwh' => $unbilledKwh,
                'over_posted_kwh' => $overPostedKwh,
                'estimated_risk_rs' => round($unbilledKwh * 6.5, 2),
            ];
        }

        // Rank by unbilled kWh descending
        usort($mruBreakdown, fn ($a, $b) => $b['unbilled_kwh'] <=> $a['unbilled_kwh']);

        // 3. Recent Inspections Audit Log
        $recentInspections = MeterInspection::with(['user:id,name,email', 'consumerAccount:id,ca_number,consumer_name'])
            ->orderBy('inspected_at', 'desc')
            ->take(25)
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'ca_number' => $i->ca_number,
                'consumer_name' => $i->consumerAccount?->consumer_name ?? 'N/A',
                'agent_name' => $i->user?->name ?? 'System',
                'physical_reading' => $i->physical_reading,
                'inspected_at' => $i->inspected_at ? Carbon::parse($i->inspected_at)->format('d M Y H:i') : 'N/A',
                'notes' => $i->notes ?? '—',
            ]);

        return response()->json([
            'success' => true,
            'kpis' => $systemKpis,
            'mru_breakdown' => $mruBreakdown,
            'recent_inspections' => $recentInspections,
        ]);
    }

    /**
     * Export complete inspection audit trail as CSV.
     */
    public function exportAudit(Request $request): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="all-meter-inspections-audit-'.now()->format('Y-m-d').'.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Inspection ID',
                'Date & Time',
                'CA Number',
                'Consumer Name',
                'Physical Reading (kWh)',
                'Agent / Inspector',
                'Agent Email',
                'Audit Notes',
            ]);

            MeterInspection::with(['user:id,name,email', 'consumerAccount:id,ca_number,consumer_name'])
                ->orderBy('inspected_at', 'desc')
                ->chunk(200, function ($inspections) use ($handle) {
                    foreach ($inspections as $i) {
                        fputcsv($handle, [
                            $i->id,
                            $i->inspected_at ? Carbon::parse($i->inspected_at)->format('Y-m-d H:i:s') : '',
                            $i->ca_number,
                            $i->consumerAccount?->consumer_name ?? '',
                            $i->physical_reading,
                            $i->user?->name ?? '',
                            $i->user?->email ?? '',
                            $i->notes ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }
}
