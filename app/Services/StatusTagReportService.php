<?php

namespace App\Services;

use App\Models\BillRecord;
use App\Models\BillStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatusTagReportService
{
    protected BillTagService $billTagService;

    public function __construct(BillTagService $billTagService)
    {
        $this->billTagService = $billTagService;
    }

    /**
     * Get monthly review status breakdown (Submitted, Critical, Doubt, Pending).
     */
    public function getMonthlyStatusBreakdown(int $userId, int $month, int $year, ?int $mruId = null): array
    {
        $query = BillRecord::where('user_id', $userId)
            ->where('billing_month', $month)
            ->where('billing_year', $year);

        if ($mruId) {
            $query->where('mru_id', $mruId);
        }

        $bills = $query->get(['id', 'ca_number', 'review_status']);

        $userStatuses = BillStatus::where('user_id', $userId)
            ->where('billing_month', $month)
            ->where('billing_year', $year)
            ->get(['ca_number', 'status'])
            ->keyBy('ca_number');

        $counts = [
            'submitted' => 0,
            'critical' => 0,
            'doubt' => 0,
            'pending' => 0,
            'total' => 0,
        ];

        foreach ($bills as $b) {
            $st = $userStatuses[$b->ca_number] ?? null;
            $status = !empty($b->review_status) && $b->review_status !== 'pending' 
                ? $b->review_status 
                : ($st ? $st->status : ($b->review_status ?: 'pending'));

            $normalizedStatus = strtolower(trim($status));
            if (!isset($counts[$normalizedStatus])) {
                $normalizedStatus = 'pending';
            }

            $counts[$normalizedStatus]++;
            $counts['total']++;
        }

        return $counts;
    }

    /**
     * Get monthly tag breakdown.
     * Uses LIVE dynamic active tags from BillTagService and preserves historical deleted tags.
     */
    public function getMonthlyTagBreakdown(int $userId, int $month, int $year, ?int $mruId = null): array
    {
        $query = BillRecord::where('user_id', $userId)
            ->where('billing_month', $month)
            ->where('billing_year', $year);

        if ($mruId) {
            $query->where('mru_id', $mruId);
        }

        $bills = $query->get(['id', 'ca_number', 'tag']);

        $userStatuses = BillStatus::where('user_id', $userId)
            ->where('billing_month', $month)
            ->where('billing_year', $year)
            ->get(['ca_number', 'tag'])
            ->keyBy('ca_number');

        // Count bills per tag code
        $tagCounts = [];
        $totalBills = 0;

        foreach ($bills as $b) {
            $st = $userStatuses[$b->ca_number] ?? null;
            $rawTag = !empty($b->tag) ? $b->tag : ($st ? ($st->tag ?? 'OK') : 'OK');
            $tagCode = strtoupper(trim($rawTag ?: 'OK'));

            $tagCounts[$tagCode] = ($tagCounts[$tagCode] ?? 0) + 1;
            $totalBills++;
        }

        // Retrieve live configured active tags
        $activeConfigTags = $this->billTagService->getActiveTags();
        $breakdown = [];
        $seenCodes = [];

        // 1. First add all currently configured active tags
        foreach ($activeConfigTags as $t) {
            $code = strtoupper(trim($t['code']));
            $seenCodes[$code] = true;
            $count = $tagCounts[$code] ?? 0;
            $pct = $totalBills > 0 ? round(($count / $totalBills) * 100, 1) : 0.0;

            $breakdown[] = [
                'code' => $code,
                'label' => $t['label'],
                'short_label' => $t['short_label'] ?? $t['label'],
                'color' => $t['color'] ?? 'slate',
                'is_active' => true,
                'count' => $count,
                'percentage' => $pct,
            ];
        }

        // 2. Add any historical tag codes found in the database that are no longer in active config (deleted/inactive)
        foreach ($tagCounts as $code => $count) {
            if (!isset($seenCodes[$code])) {
                $tagDef = $this->billTagService->getTagByCode($code);
                $label = $tagDef['label'] ?? $code;
                $shortLabel = $tagDef['short_label'] ?? $label;
                $color = $tagDef['color'] ?? 'slate';
                $pct = $totalBills > 0 ? round(($count / $totalBills) * 100, 1) : 0.0;

                $breakdown[] = [
                    'code' => $code,
                    'label' => $label . ' (Archived)',
                    'short_label' => $shortLabel,
                    'color' => $color,
                    'is_active' => false,
                    'count' => $count,
                    'percentage' => $pct,
                ];
            }
        }

        return [
            'total_bills' => $totalBills,
            'tags' => $breakdown,
        ];
    }

    /**
     * Get drill-down consumer list for specific status and tag combination.
     */
    public function getConsumersByFilter(
        int $userId,
        int $month,
        int $year,
        ?int $mruId = null,
        ?string $status = null,
        ?string $tag = null,
        int $perPage = 50,
        int $page = 1
    ): LengthAwarePaginator {
        $query = BillRecord::with('mru')
            ->where('user_id', $userId)
            ->where('billing_month', $month)
            ->where('billing_year', $year);

        if ($mruId) {
            $query->where('mru_id', $mruId);
        }

        $allBills = $query->orderBy('ca_number')->get();

        $userStatuses = BillStatus::where('user_id', $userId)
            ->where('billing_month', $month)
            ->where('billing_year', $year)
            ->get()
            ->keyBy('ca_number');

        // Apply status and tag filters with status overlay
        $filtered = $allBills->filter(function ($b) use ($userStatuses, $status, $tag) {
            $st = $userStatuses[$b->ca_number] ?? null;
            $currentStatus = !empty($b->review_status) && $b->review_status !== 'pending'
                ? $b->review_status
                : ($st ? $st->status : ($b->review_status ?: 'pending'));

            $currentTag = !empty($b->tag) ? $b->tag : ($st ? ($st->tag ?? 'OK') : 'OK');

            if (!empty($status) && $status !== 'all' && strtolower($currentStatus) !== strtolower($status)) {
                return false;
            }

            if (!empty($tag) && $tag !== 'all' && strtoupper($currentTag) !== strtoupper($tag)) {
                return false;
            }

            $b->resolved_status = $currentStatus;
            $b->resolved_tag = $currentTag;
            $b->resolved_remark = $st ? ($st->remark ?? '') : ($b->remark ?? '');
            return true;
        })->values();

        $total = $filtered->count();
        $slice = $filtered->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Export status and tag drill-down as CSV download.
     */
    public function exportCsv(int $userId, int $month, int $year, array $filters = []): StreamedResponse
    {
        $mruId = $filters['mru_id'] ?? null;
        $status = $filters['status'] ?? null;
        $tag = $filters['tag'] ?? null;

        $paginator = $this->getConsumersByFilter(
            userId: $userId,
            month: $month,
            year: $year,
            mruId: $mruId ? (int) $mruId : null,
            status: $status,
            tag: $tag,
            perPage: 50000,
            page: 1
        );

        $items = $paginator->items();
        $fileName = "status_tag_report_{$year}_{$month}_" . date('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($items) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($output, [
                'Consumer No (CA)',
                'Consumer Name',
                'MRU Code',
                'Current Reading',
                'Previous Reading',
                'Units Consumed',
                'Amount Due (₹)',
                'Meter Number',
                'Billing Basis',
                'Review Status',
                'Tag',
                'Remark'
            ]);

            foreach ($items as $bill) {
                $statusLabel = ucfirst($bill->resolved_status ?? ($bill->review_status ?: 'pending'));
                $tagCode = $bill->resolved_tag ?? ($bill->tag ?: 'OK');
                $tagLabel = $this->billTagService->getFullLabel($tagCode);

                fputcsv($output, [
                    $bill->ca_number,
                    $bill->consumer_name,
                    $bill->mru ? $bill->mru->code : 'GENERAL',
                    $bill->current_reading ?? '—',
                    $bill->previous_reading ?? '—',
                    $bill->units_consumed ?? 0,
                    $bill->total_amount > 0 ? $bill->total_amount : 'N/A',
                    $bill->meter_no ?? '—',
                    $bill->billing_basis ?: 'OK',
                    $statusLabel,
                    $tagLabel,
                    $bill->resolved_remark ?? ($bill->remark ?? ''),
                ]);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
