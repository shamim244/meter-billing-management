<?php

namespace App\Services;

use App\Models\BillRecord;

class EngineService
{
    public function __construct(
        protected BillDownloadService $downloadService,
        protected BillParseService $parseService
    ) {}

    /**
     * Download and parse bills using native multi-driver download & dual extraction engine.
     */
    public function downloadAndParseBills(
        array $caNumbers,
        int $userId,
        ?int $targetMonth = null,
        ?int $targetYear = null,
        ?int $targetMruId = null
    ): array {
        $defaultMonth = $targetMonth ?: (int) now()->month;
        $defaultYear = $targetYear ?: (int) now()->year;

        // 1. Download bills using multi-driver download service (WSS / Legacy / Auto)
        $this->downloadService->download(
            caNumbers: $caNumbers,
            userId: $userId,
            month: $defaultMonth,
            year: $defaultYear,
            mruId: $targetMruId
        );

        // 2. Parse downloaded bills using dual extraction engine
        $records = BillRecord::where('user_id', $userId)
            ->whereIn('ca_number', $caNumbers)
            ->where('billing_month', $defaultMonth)
            ->where('billing_year', $defaultYear)
            ->where('download_status', 'downloaded')
            ->whereNotNull('pdf_path')
            ->get();

        if ($records->isNotEmpty()) {
            $this->parseService->parseSpecificBills($userId, $records->pluck('id')->toArray());
        }

        // 3. Assemble results in standard format
        $results = [
            'total' => count($caNumbers),
            'success' => 0,
            'failed_download' => 0,
            'failed_parse' => 0,
            'details' => [],
        ];

        foreach ($caNumbers as $ca) {
            $rec = BillRecord::where('user_id', $userId)
                ->where('ca_number', $ca)
                ->where('billing_month', $defaultMonth)
                ->where('billing_year', $defaultYear)
                ->with('mru')
                ->first();

            if (! $rec || $rec->download_status !== 'downloaded') {
                $results['failed_download']++;
                $results['details'][$ca] = [
                    'status' => 'failed_download',
                    'error' => $rec?->error_message ?: 'PDF was not downloaded or is empty',
                ];
            } elseif ($rec->parse_status !== 'parsed') {
                $results['failed_parse']++;
                $results['details'][$ca] = [
                    'status' => 'failed_parse',
                    'error' => $rec->error_message ?: 'PDF parsing failed to extract consumer details',
                ];
            } else {
                $results['success']++;
                $results['details'][$ca] = [
                    'status' => 'success',
                    'consumer_name' => $rec->consumer_name ?: '',
                    'total_amount' => (float) ($rec->total_amount ?: 0),
                    'billing_period' => "{$defaultMonth}/{$defaultYear}",
                    'mru' => $rec->mru?->code ?: 'GENERAL',
                ];
            }
        }

        return $results;
    }
}
