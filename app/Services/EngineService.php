<?php

namespace App\Services;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class EngineService
{
    /**
     * Download and parse bills using the legacy engine under an isolated temp process.
     */
    public function downloadAndParseBills(array $caNumbers, int $userId, ?int $targetMonth = null, ?int $targetYear = null, ?int $targetMruId = null): array
    {
        $defaultMonth = $targetMonth ?: now()->month;
        $defaultYear = $targetYear ?: now()->year;

        $runId = uniqid('run_' . time() . '_');
        $tempPath = storage_path('app/temp_runs/' . $runId);
        
        // Create temp directories
        File::ensureDirectoryExists($tempPath);
        File::ensureDirectoryExists($tempPath . '/bills');
        File::ensureDirectoryExists($tempPath . '/logs');
        File::ensureDirectoryExists($tempPath . '/vendor');

        // Write ca.txt
        File::put($tempPath . '/ca.txt', implode("\n", $caNumbers));

        // Create vendor/autoload.php to point to original vendor autoload
        $originalVendorAutoload = base_path('../vendor/autoload.php');
        File::put($tempPath . '/vendor/autoload.php', "<?php\nrequire_once '" . str_replace('\\', '/', $originalVendorAutoload) . "';\n");

        // Write config.php
        $configContent = sprintf(
            "<?php\nreturn [\n" .
            "    'ca_file' => '%s',\n" .
            "    'bills_dir' => '%s',\n" .
            "    'bill_data_file' => '%s',\n" .
            "    'status_file' => '%s',\n" .
            "    'api_url' => 'https://api.bsphcl.co.in/nbWSMobileApp/ViewBill.asmx/GetViewBill?strCANumber=',\n" .
            "    'log_file' => '%s',\n" .
            "];\n",
            str_replace('\\', '/', $tempPath . '/ca.txt'),
            str_replace('\\', '/', $tempPath . '/bills'),
            str_replace('\\', '/', $tempPath . '/bill_data.json'),
            str_replace('\\', '/', $tempPath . '/statuses.json'),
            str_replace('\\', '/', $tempPath . '/logs/process.log')
        );
        File::put($tempPath . '/config.php', $configContent);

        // Copy main.php and info.php
        File::copy(base_path('../main.php'), $tempPath . '/main.php');
        File::copy(base_path('../info.php'), $tempPath . '/info.php');

        $results = [
            'total' => count($caNumbers),
            'success' => 0,
            'failed_download' => 0,
            'failed_parse' => 0,
            'details' => [],
        ];

        // Preload consumer MRU mapping if any
        $consumerMruMap = ConsumerAccount::where('user_id', $userId)
            ->whereIn('ca_number', $caNumbers)
            ->pluck('mru_id', 'ca_number')
            ->toArray();

        try {
            // Run main.php (downloader)
            $downloaderProcess = new Process(['php', $tempPath . '/main.php']);
            $downloaderProcess->setTimeout(300);
            $downloaderProcess->run();

            // Run info.php (parser)
            $parserProcess = new Process(['php', $tempPath . '/info.php']);
            $parserProcess->setTimeout(300);
            $parserProcess->run();

            // Read parsed data
            $parsedData = [];
            if (File::exists($tempPath . '/bill_data.json')) {
                $parsedData = json_decode(File::get($tempPath . '/bill_data.json'), true) ?: [];
            }

            foreach ($caNumbers as $ca) {
                $pdfFile = $tempPath . '/bills/' . $ca . '.pdf';
                $assignedMruId = $targetMruId ?: ($consumerMruMap[$ca] ?? null);
                
                if (!File::exists($pdfFile) || File::size($pdfFile) === 0) {
                    // Download failed
                    $results['failed_download']++;
                    $results['details'][$ca] = [
                        'status' => 'failed_download',
                        'error' => 'PDF was not downloaded or is empty',
                    ];

                    BillRecord::updateOrCreate([
                        'user_id' => $userId,
                        'ca_number' => $ca,
                        'billing_month' => $defaultMonth,
                        'billing_year' => $defaultYear,
                    ], [
                        'mru_id' => $assignedMruId,
                        'download_status' => 'failed',
                        'parse_status' => 'pending',
                        'error_message' => 'Download failed',
                        'processing_date' => now(),
                    ]);
                    continue;
                }

                // Check if parse data exists
                if (!isset($parsedData[$ca]) || empty($parsedData[$ca]['consumer_name'])) {
                    // Try to extract at least some raw data or mark as failed parse
                    $results['failed_parse']++;
                    $results['details'][$ca] = [
                        'status' => 'failed_parse',
                        'error' => 'PDF parsing failed to extract consumer details',
                    ];

                    BillRecord::updateOrCreate([
                        'user_id' => $userId,
                        'ca_number' => $ca,
                        'billing_month' => $defaultMonth,
                        'billing_year' => $defaultYear,
                    ], [
                        'mru_id' => $assignedMruId,
                        'download_status' => 'downloaded',
                        'parse_status' => 'failed',
                        'error_message' => 'Parsing failed',
                        'processing_date' => now(),
                    ]);
                    continue;
                }

                $data = $parsedData[$ca];

                // Extract MRU from PDF text
                $mruCode = 'UNKNOWN';
                try {
                    require_once base_path('../vendor/autoload.php');
                    $pdfParser = new \Smalot\PdfParser\Parser();
                    $pdfObj = $pdfParser->parseFile($pdfFile);
                    $pdfText = $pdfObj->getText();
                    if (preg_match('/,e vkj ;q\s*\n\s*([A-Z_]+(?:\s*\n\s*[A-Z_]+)*)/u', $pdfText, $mruMatches)) {
                        $mruCode = str_replace(["\r", "\n", " "], "", $mruMatches[1]);
                    }
                } catch (\Exception $e) {
                    // fallback
                }

                // Create or get MRU
                $mruName = str_replace('_', ' ', $mruCode);
                $mru = null;
                if ($targetMruId) {
                    $mru = Mru::find($targetMruId);
                }
                if (!$mru) {
                    $mru = Mru::firstOrCreate(
                        ['user_id' => $userId, 'code' => $mruCode],
                        ['name' => $mruName, 'full_identifier' => $mruCode, 'status' => 'active']
                    );
                }

                // Update consumer account with MRU and name
                $consumerAccount = ConsumerAccount::updateOrCreate(
                    ['ca_number' => $ca, 'user_id' => $userId],
                    [
                        'mru_id' => $mru->id,
                        'consumer_name' => $data['consumer_name'] ?? null,
                        'status' => 'active'
                    ]
                );

                // Parse billing period month and year from string (e.g. "APR, 2026")
                $billMonthStr = $data['bill_month'] ?? '';
                $month = now()->month;
                $year = now()->year;
                if (preg_match('/([A-Z]+),\s*(\d{4})/i', $billMonthStr, $periodMatches)) {
                    $monthName = strtoupper(trim($periodMatches[1]));
                    $year = (int)$periodMatches[2];
                    $monthMap = [
                        'JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4, 'MAY' => 5, 'JUN' => 6,
                        'JUL' => 7, 'AUG' => 8, 'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DEC' => 12
                    ];
                    $month = $monthMap[substr($monthName, 0, 3)] ?? $month;
                }

                // Save PDF to user's organized folder
                $storageDir = "users/{$userId}/pdfs/{$year}/{$month}/{$mruCode}";
                $storageFilename = "{$ca}.pdf";
                $storagePath = "{$storageDir}/{$storageFilename}";
                
                Storage::disk('local')->makeDirectory($storageDir);
                Storage::disk('local')->put($storagePath, File::get($pdfFile));

                // Save/update bill record
                BillRecord::updateOrCreate([
                    'user_id' => $userId,
                    'ca_number' => $ca,
                    'billing_month' => $month,
                    'billing_year' => $year,
                ], [
                    'mru_id' => $mru->id,
                    'bill_month_label' => $billMonthStr,
                    'consumer_name' => $data['consumer_name'] ?? null,
                    'total_amount' => $data['total_amount'] ?? null,
                    'current_reading' => $data['current_reading'] ?? null,
                    'previous_reading' => $data['previous_reading'] ?? null,
                    'units_consumed' => $data['units_consumed'] ?? null,
                    'meter_no' => $data['meter_no'] ?? null,
                    'bill_date' => isset($data['bill_date']) && strtotime($data['bill_date']) !== false ? date('Y-m-d', strtotime($data['bill_date'])) : null,
                    'pdf_path' => $storagePath,
                    'pdf_filename' => basename($pdfFile),
                    'download_status' => 'downloaded',
                    'parse_status' => 'parsed',
                    'error_message' => null,
                    'processing_date' => now(),
                ]);

                $results['success']++;
                $results['details'][$ca] = [
                    'status' => 'success',
                    'consumer_name' => $data['consumer_name'] ?? '',
                    'total_amount' => $data['total_amount'] ?? 0,
                    'billing_period' => "{$month}/{$year}",
                    'mru' => $mruCode,
                ];
            }
        } finally {
            // Clean up temp run directory
            File::deleteDirectory($tempPath);
        }

        return $results;
    }
}
