<?php

namespace App\Services;

use App\Models\BillRecord;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BillDownloadService
{
    /**
     * Download bills for a list of CA numbers using native high-concurrency multi-cURL.
     */
    public function download(array $caNumbers, int $userId, int $month, int $year, ?int $mruId = null, ?int $concurrency = null): array
    {
        $concurrency = $concurrency ?: (int) config('nbpdcl.concurrency', 10);
        $apiUrl = config('nbpdcl.api_url', 'https://api.bsphcl.co.in/nbWSMobileApp/ViewBill.asmx/GetViewBill?strCANumber=');

        $this->appendLog($userId, "==================================================");
        $this->appendLog($userId, sprintf("Initiating task: Bill Downloader (Period: %02d/%04d, Accounts: %d)...", $month, $year, count($caNumbers)));

        $results = [
            'total' => count($caNumbers),
            'success' => 0,
            'failed' => 0,
            'details' => [],
        ];

        // Quota Guard: Check if user exceeded allocated PDF storage limit
        $user = User::find($userId);
        if ($user && $user->isStorageLimitExceeded()) {
            $limitMb = $user->storage_limit_mb ?? 100;
            $msg = "❌ Storage Quota Exceeded ({$limitMb} MB Limit). Please purge old cycle PDFs in PDF Manager or upgrade your subscription plan.";
            $this->appendLog($userId, $msg);
            $results['failed'] = count($caNumbers);
            $results['error'] = $msg;
            return $results;
        }

        $mru = $mruId ? Mru::find($mruId) : null;
        $mruCode = $mru ? $mru->code : 'GENERAL';

        $storageDir = "users/{$userId}/pdfs/{$year}/{$month}/{$mruCode}";
        Storage::disk('local')->makeDirectory($storageDir);

        if (empty($caNumbers)) {
            $this->appendLog($userId, "No accounts to download.");
            return $results;
        }

        $queue = array_values($caNumbers);
        $total = count($queue);
        $processed = 0;

        $mh = curl_multi_init();
        $activeRequests = [];

        $addHandle = function (string $ca) use ($mh, &$activeRequests, $apiUrl) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl . urlencode(trim($ca)));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64)");

            curl_multi_add_handle($mh, $ch);
            $activeRequests[(int) $ch] = [
                'ca' => $ca,
                'handle' => $ch,
            ];
        };

        // Populate initial batch
        while (count($activeRequests) < $concurrency && !empty($queue)) {
            $addHandle(array_shift($queue));
        }

        $running = null;
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) {
                curl_multi_select($mh, 0.5);
            }

            while ($info = curl_multi_info_read($mh)) {
                $ch = $info['handle'];
                $id = (int) $ch;

                if (isset($activeRequests[$id])) {
                    $ca = $activeRequests[$id]['ca'];
                    $processed++;

                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $content = curl_multi_getcontent($ch);
                    $curlErr = curl_error($ch);

                    // Sanitize PDF content (strip chunked transfer encoding headers)
                    if ($content && ($pos = strpos($content, '%PDF')) !== false) {
                        $content = substr($content, $pos);
                    }

                    $isValidPdf = ($httpCode === 200 && !empty($content) && str_starts_with($content, '%PDF'));

                    if ($isValidPdf) {
                        $results['success']++;
                        $storagePath = "{$storageDir}/{$ca}.pdf";
                        Storage::disk('local')->put($storagePath, $content);

                        BillRecord::updateOrCreate([
                            'user_id' => $userId,
                            'ca_number' => $ca,
                            'billing_month' => $month,
                            'billing_year' => $year,
                        ], [
                            'mru_id' => $mruId,
                            'pdf_path' => $storagePath,
                            'pdf_filename' => "{$ca}.pdf",
                            'download_status' => 'downloaded',
                            'error_message' => null,
                            'processing_date' => now(),
                        ]);

                        $sizeKb = round(strlen($content) / 1024, 1);
                        $this->appendLog($userId, "[{$processed}/{$total}] ✅ CA: {$ca} — Downloaded ({$sizeKb} KB)");
                    } else {
                        $results['failed']++;
                        $errMsg = $curlErr ?: "HTTP {$httpCode} (No valid PDF content)";
                        $results['details'][$ca] = ['status' => 'failed', 'error' => $errMsg];

                        BillRecord::updateOrCreate([
                            'user_id' => $userId,
                            'ca_number' => $ca,
                            'billing_month' => $month,
                            'billing_year' => $year,
                        ], [
                            'mru_id' => $mruId,
                            'download_status' => 'failed',
                            'error_message' => $errMsg,
                            'processing_date' => now(),
                        ]);

                        $this->appendLog($userId, "[{$processed}/{$total}] ❌ CA: {$ca} — Download Failed ({$errMsg})");
                    }

                    curl_multi_remove_handle($mh, $ch);
                    curl_close($ch);
                    unset($activeRequests[$id]);

                    // Feed next handle from queue
                    if (!empty($queue)) {
                        $addHandle(array_shift($queue));
                    }
                }
            }
        } while ($running || !empty($activeRequests));

        curl_multi_close($mh);

        $this->appendLog($userId, "==================================================");
        $this->appendLog($userId, "Task Completed: {$results['success']} downloaded, {$results['failed']} failed.");

        return $results;
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
