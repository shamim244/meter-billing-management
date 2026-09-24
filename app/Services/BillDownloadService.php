<?php

namespace App\Services;

use App\Models\BillRecord;
use App\Models\Mru;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BillDownloadService
{
    /**
     * Download bills for a list of CA numbers using native high-concurrency multi-cURL.
     * Supports multiple drivers: 'auto' (smart WSS with legacy fallback), 'wss', and 'legacy'.
     */
    public function download(array $caNumbers, int $userId, int $month, int $year, ?int $mruId = null, ?int $concurrency = null): array
    {
        $driver = SystemSetting::get('nbpdcl_download_driver', config('nbpdcl.download_driver', 'auto'));
        $concurrency = $concurrency ?: (int) SystemSetting::get('nbpdcl_concurrency', config('nbpdcl.concurrency', 10));
        $wssUrl = SystemSetting::get('nbpdcl_wss_url', config('nbpdcl.wss_url', 'https://wss.nbpdcl.co.in/fgweb/web/json/plugin/com.fluentgrid.cp.api.NscUploadBridgeService/service?&rtype=DOWNLOAD'));
        $aesKey = SystemSetting::get('nbpdcl_aes_key', config('nbpdcl.aes_key', 'fgwebcp@2020'));
        $legacyUrl = SystemSetting::get('nbpdcl_legacy_url', config('nbpdcl.api_url', 'https://api.bsphcl.co.in/nbWSMobileApp/ViewBill.asmx/GetViewBill?strCANumber='));
        $timeout = (int) SystemSetting::get('nbpdcl_timeout', config('nbpdcl.timeout', 45));

        $this->appendLog($userId, '==================================================');
        $this->appendLog($userId, sprintf(
            'Initiating task: Bill Downloader (Period: %02d/%04d, Accounts: %d, Driver: %s)...',
            $month,
            $year,
            count($caNumbers),
            strtoupper($driver)
        ));

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
            $this->appendLog($userId, 'No accounts to download.');

            return $results;
        }

        // Each queue item: ['ca' => string, 'driver' => 'wss'|'legacy', 'attempt' => int]
        $initialDriver = ($driver === 'legacy') ? 'legacy' : 'wss';
        $queue = array_map(function ($ca) use ($initialDriver) {
            return [
                'ca' => trim((string) $ca),
                'driver' => $initialDriver,
                'attempt' => 1,
            ];
        }, array_values($caNumbers));

        $total = count($queue);
        $processed = 0;

        $mh = curl_multi_init();
        $activeRequests = [];

        $monthStr = sprintf('%02d', $month);
        $yearStr = (string) $year;

        $addHandle = function (array $item) use ($mh, &$activeRequests, $wssUrl, $aesKey, $legacyUrl, $monthStr, $yearStr, $timeout) {
            $ca = $item['ca'];
            $driverMode = $item['driver'];
            $ch = curl_init();

            if ($driverMode === 'legacy') {
                $url = $legacyUrl.urlencode($ca);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ]);
            } else {
                $payload = [
                    'req' => [
                        'billMonth' => $monthStr,
                        'billYear' => $yearStr,
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

                $encryptedBody = $this->encryptCryptoJS(json_encode($payload), $aesKey);

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

            curl_multi_add_handle($mh, $ch);
            $activeRequests[(int) $ch] = [
                'item' => $item,
                'handle' => $ch,
            ];
        };

        // Populate initial batch
        while (count($activeRequests) < $concurrency && ! empty($queue)) {
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
                    $item = $activeRequests[$id]['item'];
                    $ca = $item['ca'];
                    $driverMode = $item['driver'];

                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $content = curl_multi_getcontent($ch);
                    $curlErr = curl_error($ch);

                    // Sanitize PDF content (strip chunked transfer encoding headers)
                    if ($content && ($pos = strpos($content, '%PDF')) !== false) {
                        $content = substr($content, $pos);
                    }

                    $isValidPdf = ($httpCode === 200 && ! empty($content) && str_starts_with($content, '%PDF'));

                    if ($isValidPdf) {
                        $processed++;
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
                        $driverBadge = strtoupper($driverMode);
                        $this->appendLog($userId, "[{$processed}/{$total}] ✅ CA: {$ca} — Downloaded ({$sizeKb} KB via {$driverBadge})");
                    } else {
                        // If driver is 'auto' and WSS failed, fall back to Legacy ASMX driver
                        if ($driver === 'auto' && $driverMode === 'wss') {
                            $this->appendLog($userId, "⚠️ CA: {$ca} — WSS download yielded no PDF. Attempting Legacy BSPHCL fallback...");
                            $queue[] = [
                                'ca' => $ca,
                                'driver' => 'legacy',
                                'attempt' => 2,
                            ];
                        } else {
                            $processed++;
                            $results['failed']++;
                            $errMsg = $curlErr ?: "HTTP {$httpCode} (No valid PDF content via {$driverMode})";
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
                    }

                    curl_multi_remove_handle($mh, $ch);
                    curl_close($ch);
                    unset($activeRequests[$id]);

                    // Feed next handle from queue
                    if (! empty($queue)) {
                        $addHandle(array_shift($queue));
                    }
                }
            }
        } while ($running || ! empty($activeRequests));

        curl_multi_close($mh);

        $this->appendLog($userId, '==================================================');
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

    /**
     * Encrypt plaintext into CryptoJS AES-256-CBC compatible format with OpenSSL EVP_BytesToKey.
     */
    public function encryptCryptoJS(string $plaintext, string $passphrase): string
    {
        $salt = openssl_random_pseudo_bytes(8);
        $hash1 = md5($passphrase.$salt, true);
        $hash2 = md5($hash1.$passphrase.$salt, true);
        $hash3 = md5($hash2.$passphrase.$salt, true);

        $key = $hash1.$hash2;
        $iv = substr($hash3, 0, 16);

        $encrypted = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return base64_encode('Salted__'.$salt.$encrypted);
    }
}
