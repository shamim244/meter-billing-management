<?php

namespace App\Services;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class BillParseService
{
    protected ?Parser $pdfParser = null;

    protected function getParser(): Parser
    {
        if ($this->pdfParser === null) {
            require_once base_path('../vendor/autoload.php');
            $this->pdfParser = new Parser();
        }
        return $this->pdfParser;
    }

    /**
     * Parse all downloaded bills for a user in a specific period & MRU.
     */
    public function parse(int $userId, int $month, int $year, ?int $mruId = null, bool $pendingOnly = false): array
    {
        $this->appendLog($userId, "==================================================");
        $this->appendLog($userId, sprintf("Initiating task: Bill Parser & Extractor (Period: %02d/%04d)...", $month, $year));

        $query = BillRecord::where('user_id', $userId)
            ->where('billing_month', $month)
            ->where('billing_year', $year)
            ->where('download_status', 'downloaded')
            ->whereNotNull('pdf_path');

        if (!empty($mruId)) {
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
            $this->appendLog($userId, "No downloaded PDF bills found matching the selection to parse.");
            return $results;
        }

        foreach ($records as $idx => $record) {
            $num = $idx + 1;
            $ca = $record->ca_number;
            $pdfFullPath = Storage::disk('local')->path($record->pdf_path);

            if (!File::exists($pdfFullPath) || File::size($pdfFullPath) === 0) {
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

                if (!$masterAccount) {
                    // 1. First Time: Auto-Register into Master List
                    $masterAccount = ConsumerAccount::create([
                        'user_id' => $userId,
                        'ca_number' => $ca,
                        'mru_id' => $record->mru_id,
                        'consumer_name' => $extracted['consumer_name'] ?: "Consumer {$ca}",
                        'father_name' => $extracted['father_name'] ?? null,
                        'meter_no' => $extracted['meter_no'],
                        'tariff_category' => $extracted['tariff_category'] ?? null,
                        'status' => 'active',
                    ]);
                } else {
                    // 2. Every Time: Continuous Smart Update with Non-Destructive Protection
                    $changed = false;

                    // Update name if valid, clean, and not generic
                    if (!empty($extracted['consumer_name']) && 
                        strlen($extracted['consumer_name']) >= 3 && 
                        !str_starts_with($extracted['consumer_name'], 'Consumer ') && 
                        $masterAccount->consumer_name !== $extracted['consumer_name']) {
                        $masterAccount->consumer_name = $extracted['consumer_name'];
                        $changed = true;
                    }

                    if (!empty($extracted['father_name']) && $masterAccount->father_name !== $extracted['father_name']) {
                        $masterAccount->father_name = $extracted['father_name'];
                        $changed = true;
                    }

                    // Update meter number if changed (e.g. Smart Meter replacement)
                    if (!empty($extracted['meter_no']) && $masterAccount->meter_no !== $extracted['meter_no']) {
                        $masterAccount->meter_no = $extracted['meter_no'];
                        $changed = true;
                    }

                    // Update tariff category if found
                    if (!empty($extracted['tariff_category']) && $masterAccount->tariff_category !== $extracted['tariff_category']) {
                        $masterAccount->tariff_category = $extracted['tariff_category'];
                        $changed = true;
                    }

                    if ($record->mru_id && $masterAccount->mru_id !== $record->mru_id) {
                        $masterAccount->mru_id = $record->mru_id;
                        $changed = true;
                    }

                    // Sync initial baseline and reading ledger
                    if (empty($masterAccount->baseline_previous_reading) && !empty($extracted['previous_reading'])) {
                        $masterAccount->baseline_previous_reading = (string) $extracted['previous_reading'];
                        $changed = true;
                    }
                    if (empty($masterAccount->last_working_reading) && !empty($extracted['current_reading'])) {
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
                $finalConsumerName = (!empty($masterAccount->consumer_name) && !str_starts_with($masterAccount->consumer_name, 'Consumer '))
                    ? $masterAccount->consumer_name
                    : ($extracted['consumer_name'] ?: ($record->consumer_name ?: "Consumer {$ca}"));

                $finalMeterNo = !empty($masterAccount->meter_no)
                    ? $masterAccount->meter_no
                    : ($extracted['meter_no'] ?: $record->meter_no);

                $finalTariff = !empty($masterAccount->tariff_category)
                    ? $masterAccount->tariff_category
                    : ($extracted['tariff_category'] ?? $record->tariff_category);

                $initialWorking = $record->working_reading;
                if (empty($initialWorking) && !empty($extracted['current_reading'])) {
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
                    app(\App\Services\BillingBasisTrackingService::class)->recordFromBillRecord($record);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("BillingBasisTrackingService hook failed for CA {$ca}: " . $e->getMessage());
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
                $this->appendLog($userId, "[{$num}/{$results['total']}] ❌ CA: {$ca} — Parse error: " . $e->getMessage());
            }
        }

        $this->appendLog($userId, "==================================================");
        $this->appendLog($userId, "Task Completed: {$results['success']} parsed successfully, {$results['failed']} failed.");

        return $results;
    }

    /**
     * Re-parse a specific batch of bill records by their IDs.
     */
    public function parseSpecificBills(int $userId, array $billIds): array
    {
        $this->appendLog($userId, "==================================================");
        $this->appendLog($userId, sprintf("Initiating batch re-parse for %d specific bills...", count($billIds)));

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
            $this->appendLog($userId, "No valid downloaded bills found matching the selected IDs.");
            return $results;
        }

        foreach ($records as $idx => $record) {
            $num = $idx + 1;
            $ca = $record->ca_number;
            $pdfFullPath = Storage::disk('local')->path($record->pdf_path);

            if (!File::exists($pdfFullPath) || File::size($pdfFullPath) === 0) {
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

                if (!$masterAccount) {
                    $masterAccount = ConsumerAccount::create([
                        'user_id' => $userId,
                        'ca_number' => $ca,
                        'mru_id' => $record->mru_id,
                        'consumer_name' => $extracted['consumer_name'] ?: "Consumer {$ca}",
                        'father_name' => $extracted['father_name'] ?? null,
                        'meter_no' => $extracted['meter_no'],
                        'tariff_category' => $extracted['tariff_category'] ?? null,
                        'status' => 'active',
                    ]);
                } else {
                    $changed = false;
                    if (!empty($extracted['consumer_name']) && 
                        strlen($extracted['consumer_name']) >= 3 && 
                        !str_starts_with($extracted['consumer_name'], 'Consumer ') && 
                        $masterAccount->consumer_name !== $extracted['consumer_name']) {
                        $masterAccount->consumer_name = $extracted['consumer_name'];
                        $changed = true;
                    }
                    if (!empty($extracted['father_name']) && $masterAccount->father_name !== $extracted['father_name']) {
                        $masterAccount->father_name = $extracted['father_name'];
                        $changed = true;
                    }
                    if (!empty($extracted['meter_no']) && $masterAccount->meter_no !== $extracted['meter_no']) {
                        $masterAccount->meter_no = $extracted['meter_no'];
                        $changed = true;
                    }
                    if (!empty($extracted['tariff_category']) && $masterAccount->tariff_category !== $extracted['tariff_category']) {
                        $masterAccount->tariff_category = $extracted['tariff_category'];
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

                $finalConsumerName = (!empty($masterAccount->consumer_name) && !str_starts_with($masterAccount->consumer_name, 'Consumer '))
                    ? $masterAccount->consumer_name
                    : ($extracted['consumer_name'] ?: ($record->consumer_name ?: "Consumer {$ca}"));

                $finalMeterNo = !empty($masterAccount->meter_no)
                    ? $masterAccount->meter_no
                    : ($extracted['meter_no'] ?: $record->meter_no);

                $finalTariff = !empty($masterAccount->tariff_category)
                    ? $masterAccount->tariff_category
                    : ($extracted['tariff_category'] ?? $record->tariff_category);

                $initialWorking = $record->working_reading;
                if (empty($initialWorking) && !empty($extracted['current_reading'])) {
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
                    app(\App\Services\BillingBasisTrackingService::class)->recordFromBillRecord($record);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("BillingBasisTrackingService hook failed for CA {$ca}: " . $e->getMessage());
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
                $this->appendLog($userId, "[{$num}/{$results['total']}] ❌ CA: {$ca} — Parse error: " . $e->getMessage());
            }
        }

        $this->appendLog($userId, "==================================================");
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
        $text = $pdf->getText();

        $data = [
            'consumer_name' => null,
            'father_name' => null,
            'bill_month' => null,
            'bill_date' => null,
            'due_date' => null,
            'current_reading' => null,
            'previous_reading' => null,
            'units_consumed' => 0,
            'total_amount' => 0.0,
            'meter_no' => null,
            'tariff_category' => null,
            'billing_basis' => 'OK',
            'mru' => null,
        ];

        // 1. Consumer Name: (matches uppercase English name line before miHkks)
        if (preg_match('/\n([^\n\r]+?)\s*[\t\s]+miHkks/u', $text, $m)) {
            $raw = trim(preg_replace('/[^A-Za-z0-9\s\.\,\/\-\&\(\)]/u', '', $m[1]));
            $data['consumer_name'] = preg_replace('/\s+/', ' ', $raw);
        } elseif (preg_match('/miHkksDrk dk uke[^\n\r]*\n\s*([^\n\r]+)/u', $text, $m)) {
            $data['consumer_name'] = trim($m[1]);
        }

        // 2. Father / Relative Name:
        if (preg_match('/\n([A-Z0-9\s\.\,\/\-]+?)\s*[\t\s]+,e vkj ;q/u', $text, $mFather)) {
            $rawFather = trim(preg_replace('/[^A-Za-z0-9\s\.\,\/\-\&\(\)]/u', '', $mFather[1]));
            if (!empty($rawFather) && !str_contains($rawFather, 'VILL') && strlen($rawFather) >= 3) {
                $data['father_name'] = preg_replace('/\s+/', ' ', $rawFather);
            }
        }

        // 3. Bill Month:
        if (preg_match('/fcy ekg\s*\n?\s*([A-Z]{3},\s*\d{4})/i', $text, $m)) {
            $data['bill_month'] = trim($m[1]);
        }

        // 4. Total Amount:
        if (preg_match('/\d{2}-\d{2}-\d{4}\s+rd ns; jkf\'k\s*\n\s*(-?\s*[\d,]+\.?\d*)/u', $text, $m)) {
            $data['total_amount'] = (float) str_replace([' ', ','], '', $m[1]);
        } elseif (preg_match_all('/dqy jkf\'k\s+(-?\s*[\d.]+)/u', $text, $amounts)) {
            $data['total_amount'] = (float) str_replace(' ', '', end($amounts[1]));
        }

        // 5. Due Date:
        if (preg_match('/(\d{2}-\d{2}-\d{4})\s+rd ns; jkf\'k/u', $text, $m)) {
            $d = \DateTime::createFromFormat('d-m-Y', $m[1]);
            $data['due_date'] = $d ? $d->format('Y-m-d') : null;
        }

        // 6. Bill Date:
        if (preg_match('/fcy frfFk\s*\n\s*(\d{2}-\d{2}-\d{4})/u', $text, $m)) {
            $d = \DateTime::createFromFormat('d-m-Y', $m[1]);
            $data['bill_date'] = $d ? $d->format('Y-m-d') : null;
        }

        // 7. Meter Readings & Units:
        $readingPattern = '/(\d+)\s+(\d{2}-\d{2}-\d{4})\s*(\d+)\s+(\d{2}-[A-Z]{3}-\d{2})\s*(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/i';
        if (preg_match($readingPattern, $text, $m)) {
            $data['meter_no'] = $m[1];
            $data['current_reading'] = (int) $m[3];
            $data['previous_reading'] = (int) $m[5];
            $data['units_consumed'] = (int) $m[6];
        } elseif (preg_match('/dqy \[kir\s*\n\s*(\d+)/u', $text, $m)) {
            $data['units_consumed'] = (int) $m[1];
        }

        // 8. Tariff Category (under Js.kh)
        if (preg_match('/Js\.kh\s*\n\s*([A-Za-z0-9\(\)\/\-\_]+)/u', $text, $m)) {
            $data['tariff_category'] = trim($m[1]);
        }

        // 9. Billing Basis (under fcy dk vkèkkj)
        if (preg_match('/fcy dk vkèkkj\s*\n\s*([A-Za-z0-9\(\)\/\-\_]+)/u', $text, $m)) {
            $rawBasis = trim($m[1]);
            if (stripos($rawBasis, 'MD') !== false) {
                $data['billing_basis'] = 'MD';
            } elseif (stripos($rawBasis, 'LK') !== false) {
                $data['billing_basis'] = 'LK';
            } elseif (stripos($rawBasis, 'PL') !== false) {
                $data['billing_basis'] = 'PL';
            } elseif (stripos($rawBasis, 'RN') !== false) {
                $data['billing_basis'] = 'RN';
            } elseif (stripos($rawBasis, 'Normal') !== false || stripos($rawBasis, 'OK') !== false) {
                $data['billing_basis'] = 'OK';
            } else {
                $data['billing_basis'] = strtoupper(substr($rawBasis, 0, 4));
            }
        }

        // 10. MRU:
        if (preg_match('/,e vkj ;q\s*\n\s*([A-Za-z0-9_\-\s]+?)(?=\n\d|\n[A-Z]|\nrd)/u', $text, $m)) {
            $data['mru'] = trim(str_replace(["\r", "\n", " "], "", $m[1]));
        }

        return $data;
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
