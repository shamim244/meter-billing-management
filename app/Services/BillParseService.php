<?php

namespace App\Services;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Services\Extraction\BillExtractionManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class BillParseService
{
    protected ?Parser $pdfParser = null;

    protected BillExtractionManager $extractionManager;

    public function __construct(?BillExtractionManager $extractionManager = null)
    {
        $this->extractionManager = $extractionManager ?: app(BillExtractionManager::class);
    }

    protected function getParser(): Parser
    {
        if ($this->pdfParser === null) {
            require_once base_path('../vendor/autoload.php');
            $this->pdfParser = new Parser;
        }

        return $this->pdfParser;
    }

    /**
     * Parse all downloaded bills for a user in a specific period & MRU.
     */
    public function parse(int $userId, int $month, int $year, ?int $mruId = null, bool $pendingOnly = false): array
    {
        $this->appendLog($userId, '==================================================');
        $this->appendLog($userId, sprintf('Initiating task: Bill Parser & Extractor (Period: %02d/%04d)...', $month, $year));

        $query = BillRecord::where('user_id', $userId)
            ->where('billing_month', $month)
            ->where('billing_year', $year)
            ->where('download_status', 'downloaded')
            ->whereNotNull('pdf_path');

        if (! empty($mruId)) {
            $query->where('mru_id', $mruId);
        }

        if ($pendingOnly) {
            $query->where(function ($q) {
                $q->whereNull('parse_status')->orWhere('parse_status', '!=', 'parsed');
            });
        }

        $records = $query->get();

        $results = [
            'total' => $records->count(),
            'success' => 0,
            'failed' => 0,
            'details' => [],
        ];

        if ($records->isEmpty()) {
            $this->appendLog($userId, 'No downloaded PDF bills found matching the selection to parse.');

            return $results;
        }

        foreach ($records as $idx => $record) {
            $num = $idx + 1;
            $ca = $record->ca_number;
            $pdfFullPath = Storage::disk('local')->path($record->pdf_path);

            if (! File::exists($pdfFullPath) || File::size($pdfFullPath) === 0) {
                $results['failed']++;
                $record->update([
                    'parse_status' => 'failed',
                    'error_message' => 'PDF file missing on disk',
                    'processing_date' => now(),
                ]);
                $this->appendLog($userId, "[{$num}/{$results['total']}] ❌ CA: {$ca} — PDF file missing on disk");

                continue;
            }

            try {
                $extracted = $this->extractFromPdf($pdfFullPath);

                // Smart Master Account Synchronization
                $masterAccount = ConsumerAccount::where('user_id', $userId)
                    ->where('ca_number', $ca)
                    ->first();

                if (! $masterAccount) {
                    // 1. First Time: Auto-Register into Master List
                    $masterAccount = ConsumerAccount::create([
                        'user_id' => $userId,
                        'ca_number' => $ca,
                        'mru_id' => $record->mru_id,
                        'consumer_name' => $extracted['consumer_name'] ?: "Consumer {$ca}",
                        'father_name' => $extracted['father_name'] ?? null,
                        'meter_no' => $extracted['meter_no'],
                        'tariff_category' => $extracted['tariff_category'] ?? null,
                        'billing_basis' => $extracted['billing_basis'] ?? 'OK',
                        'baseline_amount' => $extracted['total_amount'] ?? 0.00,
                        'status' => 'active',
                    ]);
                } else {
                    // 2. Every Time: Continuous Smart Update with Non-Destructive Protection
                    $changed = false;

                    // Update name if valid, clean, and not generic
                    if (! empty($extracted['consumer_name']) &&
                        strlen($extracted['consumer_name']) >= 3 &&
                        ! str_starts_with($extracted['consumer_name'], 'Consumer ') &&
                        $masterAccount->consumer_name !== $extracted['consumer_name']) {
                        $masterAccount->consumer_name = $extracted['consumer_name'];
                        $changed = true;
                    }

                    if (! empty($extracted['father_name']) && $masterAccount->father_name !== $extracted['father_name']) {
                        $masterAccount->father_name = $extracted['father_name'];
                        $changed = true;
                    }

                    // Update meter number if changed (e.g. Smart Meter replacement)
                    if (! empty($extracted['meter_no']) && $masterAccount->meter_no !== $extracted['meter_no']) {
                        $masterAccount->meter_no = $extracted['meter_no'];
                        $changed = true;
                    }

                    // Update tariff category if found
                    if (! empty($extracted['tariff_category']) && $masterAccount->tariff_category !== $extracted['tariff_category']) {
                        $masterAccount->tariff_category = $extracted['tariff_category'];
                        $changed = true;
                    }

                    if (! empty($extracted['billing_basis']) && (empty($masterAccount->billing_basis) || $masterAccount->billing_basis === 'OK')) {
                        $masterAccount->billing_basis = $extracted['billing_basis'];
                        $changed = true;
                    }

                    if (! empty($extracted['total_amount']) && ((float) $masterAccount->baseline_amount == 0.0 || empty($masterAccount->baseline_amount))) {
                        $masterAccount->baseline_amount = (float) $extracted['total_amount'];
                        $changed = true;
                    }

                    if ($record->mru_id && $masterAccount->mru_id !== $record->mru_id) {
                        $masterAccount->mru_id = $record->mru_id;
                        $changed = true;
                    }

                    // Sync initial baseline and reading ledger
                    if (empty($masterAccount->baseline_previous_reading) && ! empty($extracted['previous_reading'])) {
                        $masterAccount->baseline_previous_reading = (string) $extracted['previous_reading'];
                        $changed = true;
                    }
                    if (empty($masterAccount->last_working_reading) && ! empty($extracted['current_reading'])) {
                        $masterAccount->last_working_reading = (string) $extracted['current_reading'];
                        $masterAccount->last_working_month = $record->billing_month;
                        $masterAccount->last_working_year = $record->billing_year;
                        $changed = true;
                    }

                    if ($changed) {
                        $masterAccount->save();
                    }
                }

                // Final resolved identity: Master takes precedence over raw extraction
                $finalConsumerName = (! empty($masterAccount->consumer_name) && ! str_starts_with($masterAccount->consumer_name, 'Consumer '))
                    ? $masterAccount->consumer_name
                    : ($extracted['consumer_name'] ?: ($record->consumer_name ?: "Consumer {$ca}"));

                $finalMeterNo = ! empty($masterAccount->meter_no)
                    ? $masterAccount->meter_no
                    : ($extracted['meter_no'] ?: $record->meter_no);

                $finalTariff = ! empty($masterAccount->tariff_category)
                    ? $masterAccount->tariff_category
                    : ($extracted['tariff_category'] ?? $record->tariff_category);

                $initialWorking = $record->working_reading;
                if (empty($initialWorking) && ! empty($extracted['current_reading'])) {
                    $initialWorking = (string) $extracted['current_reading'];
                }

                $record->update([
                    'bill_month_label' => $extracted['bill_month'] ?: $record->bill_month_label,
                    'consumer_name' => $finalConsumerName,
                    'total_amount' => $extracted['total_amount'],
                    'current_reading' => $extracted['current_reading'],
                    'previous_reading' => $extracted['previous_reading'],
                    'working_reading' => $initialWorking,
                    'units_consumed' => $extracted['units_consumed'],
                    'meter_no' => $finalMeterNo,
                    'tariff_category' => $finalTariff,
                    'billing_basis' => $extracted['billing_basis'] ?? ($record->billing_basis ?: 'OK'),
                    'bill_date' => $extracted['bill_date'],
                    'due_date' => $extracted['due_date'],
                    'parse_status' => 'parsed',
                    'error_message' => null,
                    'processing_date' => now(),
                ]);

                // Hook into Usage Tracking System for billing basis and consecutive estimate detection
                try {
                    app(BillingBasisTrackingService::class)->recordFromBillRecord($record);
                } catch (\Throwable $e) {
                    Log::warning("BillingBasisTrackingService hook failed for CA {$ca}: ".$e->getMessage());
                }

                // Hook into Dedicated Meter Reading History System
                try {
                    app(MeterReadingHistoryService::class)->recordFromPdf($record, $extracted['consumption_history'] ?? []);
                } catch (\Throwable $e) {
                    Log::warning("MeterReadingHistoryService hook failed for CA {$ca}: ".$e->getMessage());
                }

                $results['success']++;
                $amountFormatted = number_format($extracted['total_amount'], 2);
                $basisBadge = $extracted['billing_basis'] ?? 'OK';
                $tariffBadge = $finalTariff ?: 'GEN';
                $this->appendLog($userId, "[{$num}/{$results['total']}] ✅ CA: {$ca} | {$finalConsumerName} | [{$tariffBadge}] [{$basisBadge}] | Units: {$extracted['units_consumed']} | ₹{$amountFormatted}");
            } catch (\Exception $e) {
                $results['failed']++;
                $record->update([
                    'parse_status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'processing_date' => now(),
                ]);
                $this->appendLog($userId, "[{$num}/{$results['total']}] ❌ CA: {$ca} — Parse error: ".$e->getMessage());
            }
        }

        $this->appendLog($userId, '==================================================');
        $this->appendLog($userId, "Task Completed: {$results['success']} parsed successfully, {$results['failed']} failed.");

        return $results;
    }

    /**
     * Re-parse a specific batch of bill records by their IDs.
     */
    public function parseSpecificBills(int $userId, array $billIds): array
    {
        $this->appendLog($userId, '==================================================');
        $this->appendLog($userId, sprintf('Initiating batch re-parse for %d specific bills...', count($billIds)));

        $records = BillRecord::where('user_id', $userId)
            ->whereIn('id', $billIds)
            ->where('download_status', 'downloaded')
            ->whereNotNull('pdf_path')
            ->get();

        $results = [
            'total' => $records->count(),
            'success' => 0,
            'failed' => 0,
            'details' => [],
        ];

        if ($records->isEmpty()) {
            $this->appendLog($userId, 'No valid downloaded bills found matching the selected IDs.');

            return $results;
        }

        foreach ($records as $idx => $record) {
            $num = $idx + 1;
            $ca = $record->ca_number;
            $pdfFullPath = Storage::disk('local')->path($record->pdf_path);

            if (! File::exists($pdfFullPath) || File::size($pdfFullPath) === 0) {
                $results['failed']++;
                $record->update([
                    'parse_status' => 'failed',
                    'error_message' => 'PDF file missing on disk',
                    'processing_date' => now(),
                ]);
                $this->appendLog($userId, "[{$num}/{$results['total']}] ❌ CA: {$ca} — PDF file missing on disk");

                continue;
            }

            try {
                $extracted = $this->extractFromPdf($pdfFullPath);

                $masterAccount = ConsumerAccount::where('user_id', $userId)
                    ->where('ca_number', $ca)
                    ->first();

                if (! $masterAccount) {
                    $masterAccount = ConsumerAccount::create([
                        'user_id' => $userId,
                        'ca_number' => $ca,
                        'mru_id' => $record->mru_id,
                        'consumer_name' => $extracted['consumer_name'] ?: "Consumer {$ca}",
                        'father_name' => $extracted['father_name'] ?? null,
                        'meter_no' => $extracted['meter_no'],
                        'tariff_category' => $extracted['tariff_category'] ?? null,
                        'billing_basis' => $extracted['billing_basis'] ?? 'OK',
                        'baseline_amount' => $extracted['total_amount'] ?? 0.00,
                        'status' => 'active',
                    ]);
                } else {
                    $changed = false;
                    if (! empty($extracted['consumer_name']) &&
                        strlen($extracted['consumer_name']) >= 3 &&
                        ! str_starts_with($extracted['consumer_name'], 'Consumer ') &&
                        $masterAccount->consumer_name !== $extracted['consumer_name']) {
                        $masterAccount->consumer_name = $extracted['consumer_name'];
                        $changed = true;
                    }
                    if (! empty($extracted['father_name']) && $masterAccount->father_name !== $extracted['father_name']) {
                        $masterAccount->father_name = $extracted['father_name'];
                        $changed = true;
                    }
                    if (! empty($extracted['meter_no']) && $masterAccount->meter_no !== $extracted['meter_no']) {
                        $masterAccount->meter_no = $extracted['meter_no'];
                        $changed = true;
                    }
                    if (! empty($extracted['tariff_category']) && $masterAccount->tariff_category !== $extracted['tariff_category']) {
                        $masterAccount->tariff_category = $extracted['tariff_category'];
                        $changed = true;
                    }
                    if (! empty($extracted['billing_basis']) && (empty($masterAccount->billing_basis) || $masterAccount->billing_basis === 'OK')) {
                        $masterAccount->billing_basis = $extracted['billing_basis'];
                        $changed = true;
                    }
                    if (! empty($extracted['total_amount']) && ((float) $masterAccount->baseline_amount == 0.0 || empty($masterAccount->baseline_amount))) {
                        $masterAccount->baseline_amount = (float) $extracted['total_amount'];
                        $changed = true;
                    }
                    if ($record->mru_id && $masterAccount->mru_id !== $record->mru_id) {
                        $masterAccount->mru_id = $record->mru_id;
                        $changed = true;
                    }
                    if ($changed) {
                        $masterAccount->save();
                    }
                }

                $finalConsumerName = (! empty($masterAccount->consumer_name) && ! str_starts_with($masterAccount->consumer_name, 'Consumer '))
                    ? $masterAccount->consumer_name
                    : ($extracted['consumer_name'] ?: ($record->consumer_name ?: "Consumer {$ca}"));

                $finalMeterNo = ! empty($masterAccount->meter_no)
                    ? $masterAccount->meter_no
                    : ($extracted['meter_no'] ?: $record->meter_no);

                $finalTariff = ! empty($masterAccount->tariff_category)
                    ? $masterAccount->tariff_category
                    : ($extracted['tariff_category'] ?? $record->tariff_category);

                $initialWorking = $record->working_reading;
                if (empty($initialWorking) && ! empty($extracted['current_reading'])) {
                    $initialWorking = (string) $extracted['current_reading'];
                }

                $record->update([
                    'bill_month_label' => $extracted['bill_month'] ?: $record->bill_month_label,
                    'consumer_name' => $finalConsumerName,
                    'total_amount' => $extracted['total_amount'],
                    'current_reading' => $extracted['current_reading'],
                    'previous_reading' => $extracted['previous_reading'],
                    'working_reading' => $initialWorking,
                    'units_consumed' => $extracted['units_consumed'],
                    'meter_no' => $finalMeterNo,
                    'tariff_category' => $finalTariff,
                    'billing_basis' => $extracted['billing_basis'] ?? ($record->billing_basis ?: 'OK'),
                    'bill_date' => $extracted['bill_date'],
                    'due_date' => $extracted['due_date'],
                    'parse_status' => 'parsed',
                    'error_message' => null,
                    'processing_date' => now(),
                ]);

                // Hook into Usage Tracking System for billing basis and consecutive estimate detection
                try {
                    app(BillingBasisTrackingService::class)->recordFromBillRecord($record);
                } catch (\Throwable $e) {
                    Log::warning("BillingBasisTrackingService hook failed for CA {$ca}: ".$e->getMessage());
                }

                // Hook into Dedicated Meter Reading History System
                try {
                    app(MeterReadingHistoryService::class)->recordFromPdf($record, $extracted['consumption_history'] ?? []);
                } catch (\Throwable $e) {
                    Log::warning("MeterReadingHistoryService hook failed for CA {$ca}: ".$e->getMessage());
                }

                $results['success']++;
                $amountFormatted = number_format($extracted['total_amount'], 2);
                $basisBadge = $extracted['billing_basis'] ?? 'OK';
                $tariffBadge = $finalTariff ?: 'GEN';
                $this->appendLog($userId, "[{$num}/{$results['total']}] ✅ CA: {$ca} | {$finalConsumerName} | [{$tariffBadge}] [{$basisBadge}] | Units: {$extracted['units_consumed']} | ₹{$amountFormatted}");
            } catch (\Exception $e) {
                $results['failed']++;
                $record->update([
                    'parse_status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'processing_date' => now(),
                ]);
                $this->appendLog($userId, "[{$num}/{$results['total']}] ❌ CA: {$ca} — Parse error: ".$e->getMessage());
            }
        }

        $this->appendLog($userId, '==================================================');
        $this->appendLog($userId, "Batch Re-parse Completed: {$results['success']} parsed successfully, {$results['failed']} failed.");

        return $results;
    }

    /**
     * Extract structured fields from a single PDF file.
     */
    public function extractFromPdf(string $pdfPath): array
    {
        $parser = $this->getParser();
        $pdf = $parser->parseFile($pdfPath);

        return $this->extractionManager->extract($pdf->getText(), $pdfPath);
    }

    /**
     * Alias for extracting structured fields from raw OCR/bill text.
     */
    public function extractBillData(string $text): array
    {
        return $this->extractFromText($text);
    }

    /**
     * Extract structured fields from raw bill text using the multi-engine manager.
     */
    public function extractFromText(string $text, ?string $pdfPath = null): array
    {
        return $this->extractionManager->extract($text, $pdfPath);
    }

    /**
     * Write timestamped line to user process.log file.
     */
    public function appendLog(int $userId, string $message): void
    {
        $logDir = storage_path("app/users/{$userId}");
        File::ensureDirectoryExists($logDir);
        $logPath = "{$logDir}/process.log";
        $timestamp = date('Y-m-d H:i:s');
        File::append($logPath, "[{$timestamp}] {$message}\n");
    }
}
