# NBPDCL Billing Download & Dual Extraction Engine: Architecture & Technical Specification

> **Audience**: Main Agent, Subagents, and Core Maintainers  
> **Last Updated**: September 24, 2026  
> **Status**: Production Ready & Fully Tested

---

## 1. System Context & Background

In May 2026, NBPDCL (North Bihar Power Distribution Company Limited) migrated its central billing infrastructure from legacy SOAP/ASMX systems to the **FluentGrid Customer Information System (CIS)**. 

### Why This Architecture Was Built:
1. **Old API Deprecation**: The legacy endpoint (`api.bsphcl.co.in/nbWSMobileApp/ViewBill.asmx`) stopped issuing new monthly bills (it now returns empty or historical records only).
2. **New Layout**: Recent bills (September 2026+) are 2-page Unicode JasperReports PDFs instead of the older 1-page Kruti-Dev 010 encoded PDFs.
3. **Resilience & Fallback Requirement**: The system must **never** hard-code a single point of failure. If the new WSS server is under maintenance, the Admin can instantly switch back to the Legacy API. Additionally, an automatic fallback mode (`auto`) guarantees zero downtime if a specific CA fails on WSS.
4. **Dual PDF Extraction**: Because older and newer PDFs use entirely different fonts and coordinate structures (Kruti-Dev glyph mapping vs Devanagari UTF-8), the extraction pipeline employs two distinct engines orchestrated by an algorithmic layout signature classifier.

---

## 2. Download System Architecture (Multi-Driver)

### 2.1 Driver Modes
The active driver is resolved dynamically at runtime via `\App\Models\SystemSetting::get('nbpdcl_download_driver', 'auto')`:

| Driver Mode | Primary Mechanism | Fallback Behavior | Use Case |
|---|---|---|---|
| **`auto`** *(Default)* | Modern WSS FluentGrid API | Transparently falls back to Legacy BSPHCL ASMX for any CA that fails or yields non-PDF | Recommended production mode for maximum resilience. |
| **`wss`** | Modern WSS FluentGrid API | None (Fails if WSS fails) | Forces all downloads through the new 2026+ encrypted pipeline. |
| **`legacy`** | Legacy BSPHCL ASMX API | None | Emergency manual switch if WSS upstream experiences downtime. |

---

### 2.2 Modern FluentGrid WSS API Protocol
* **Endpoint**:  
  `POST https://wss.nbpdcl.co.in/fgweb/web/json/plugin/com.fluentgrid.cp.api.NscUploadBridgeService/service?&rtype=DOWNLOAD`
* **HTTP Headers**:  
  ```http
  Content-Type: text/plain
  User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
  ```
* **Plaintext JSON Payload** (Before Encryption):
  ```json
  {
    "req": {
      "billMonth": "09",
      "billYear": "2026",
      "scno": "10230041576",
      "lang": "H",
      "printtype": "PDF",
      "modulename": "WSS",
      "genPDF": "N",
      "finalflag": "X"
    },
    "action": "billing/getviewbillprint",
    "method": "POST",
    "auth": "TOKEN"
  }
  ```
* **Cryptography & Encryption Routine**:
  * **Algorithm**: AES-256-CBC
  * **Key Derivation**: OpenSSL `EVP_BytesToKey` with MD5 and an 8-byte random salt.
  * **Passphrase**: `fgwebcp@2020`
  * **Encoding**: Sent as a raw base64 string prefixed with `Salted__<8-byte-salt><ciphertext>`.
  * **PHP Implementation** ([`BillDownloadService::encryptCryptoJS`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/app/Services/BillDownloadService.php)):
    ```php
    public function encryptCryptoJS(string $plaintext, string $passphrase): string
    {
        $salt = openssl_random_pseudo_bytes(8);
        $hash1 = md5($passphrase . $salt, true);
        $hash2 = md5($hash1 . $passphrase . $salt, true);
        $hash3 = md5($hash2 . $passphrase . $salt, true);

        $key = $hash1 . $hash2;
        $iv = substr($hash3, 0, 16);

        $encrypted = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return base64_encode('Salted__' . $salt . $encrypted);
    }
    ```
* **Response Sanitization**:
  The response body is binary PDF. Because reverse proxy or chunked transfer encoding can insert header bytes before the `%PDF` magic marker, the service always strips preceding bytes:
  ```php
  if ($content && ($pos = strpos($content, '%PDF')) !== false) {
      $content = substr($content, $pos);
  }
  ```

---

### 2.3 Legacy BSPHCL ASMX API Protocol
* **Endpoint**:  
  `GET https://api.bsphcl.co.in/nbWSMobileApp/ViewBill.asmx/GetViewBill?strCANumber={ca_number}`
* **Response**: Binary PDF directly or XML envelope.

---

## 3. Dual Extraction Architecture & Layout Detection

Managed by [`\App\Services\Extraction\BillExtractionManager`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/app/Services/Extraction/BillExtractionManager.php).

### 3.1 Algorithmic Classifier (`detectFormat`)
The manager inspects the raw text stream extracted by `Smalot\PdfParser\Parser`:
1. **Legacy Kruti-Dev Check**:
   If text contains Kruti-Dev markers (`miHkks`, `fcy ekg`, `rd ns; jkf`, `,e vkj ;q`, `[kir fooj`), format is identified as `legacy_krutidev`.
2. **Modern JasperReports Check**:
   If text contains Devanagari Hindi markers (`विधुत`, `विद्युत`, `बिल माह`, `स्मार्ट प्रीपेड`, `JasperReports`, `विपत्र`, or Devanagari character frequency > 10), format is identified as `jasper_unicode`.
3. **Cross-Engine Fallback**:
   If the primary extractor produces an empty consumer name, meter number, or bill month, the manager automatically attempts extraction with the secondary engine.

### 3.2 Modern Jasper Unicode Extractor
* **Class**: [`\App\Services\Extraction\JasperUnicodeExtractor`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/app/Services/Extraction/JasperUnicodeExtractor.php)
* **UTF-32 Sanitization**:
  To prevent PCRE `PREG_BAD_UTF8_ERROR` caused by PDF surrogate code points, text is sanitized before running regular expressions:
  ```php
  mb_substitute_character('none');
  $cleanText = mb_convert_encoding($rawText, 'UTF-32', 'UTF-8');
  $cleanText = mb_convert_encoding($cleanText, 'UTF-8', 'UTF-32');
  $cleanText = str_replace(["\xc2\xa0", "\xc2\xad"], [' ', '-'], $cleanText);
  ```
* **Extracted Fields**:
  * `consumer_name`: English name or Hindi label parsing.
  * `meter_no`: Numeric serial or Smart Meter format (`KT...`).
  * `current_reading`, `previous_reading`, `units_consumed`.
  * `total_amount`: Net amount payable.
  * `bill_month`, `bill_date`, `due_date`.
  * `tariff_category`: e.g. `DS-II D`, `Kutir Jyoti Rural`.
  * `billing_basis`: e.g. `OK`, `LK`, `MD`.
  * `mru`: e.g. `1002/BARSOI_NEW`.
  * `consumption_history`: Array of up to 12 previous months containing `[month, year, units, basis]`.

### 3.3 Legacy Kruti-Dev Extractor
* **Class**: [`\App\Services\Extraction\LegacyKrutiDevExtractor`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/app/Services/Extraction/LegacyKrutiDevExtractor.php)
* Decodes Kruti-Dev 010 table structures for historical bills downloaded prior to May 2026.

---

## 4. Orchestration Services

### 4.1 `BillDownloadService`
* **Path**: [`app/Services/BillDownloadService.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/app/Services/BillDownloadService.php)
* **Method**: `download(array $caNumbers, int $userId, int $month, int $year, ?int $mruId = null, ?int $concurrency = null): array`
* Handles multi-handle cURL queue, automatic fallback, disk quota checks, and updates `BillRecord` statuses (`download_status: 'downloaded'|'failed'`).

### 4.2 `BillParseService`
* **Path**: [`app/Services/BillParseService.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/app/Services/BillParseService.php)
* **Methods**:
  * `parse(int $userId, int $month, int $year, ?int $mruId = null, bool $pendingOnly = false): array`
  * `parseSpecificBills(int $userId, array $billIds): array`
* Integrates `BillExtractionManager`, updates `BillRecord`, registers/updates `ConsumerAccount`, hooks into `BillingBasisTrackingService` and `MeterReadingHistoryService`.

### 4.3 `EngineService`
* **Path**: [`app/Services/EngineService.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/app/Services/EngineService.php)
* Replaces legacy subprocess CLI execution with clean, in-memory dependency injection calling `BillDownloadService` and `BillParseService`.

---

## 5. Admin Control Center & System Settings

### 5.1 System Settings Keys (`SystemSetting`)
* `nbpdcl_download_driver`: `'auto'`, `'wss'`, `'legacy'`
* `nbpdcl_extraction_engine`: `'auto'`, `'jasper_unicode'`, `'legacy_krutidev'`
* `nbpdcl_wss_url`: FluentGrid bridge service URL
* `nbpdcl_aes_key`: AES passphrase (`fgwebcp@2020`)
* `nbpdcl_legacy_url`: Legacy ASMX URL
* `nbpdcl_timeout`: Execution timeout (default `45`)
* `nbpdcl_concurrency`: Simultaneous multi-cURL handles (default `10`)

### 5.2 Routes & Controllers
* **Controller**: [`AdminBillController.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/app/Http/Controllers/Admin/AdminBillController.php)
* **Routes**:
  * `GET  /admin/bills/engine-settings` (`admin.bills.engine-settings`): UI for configuring drivers & parameters.
  * `POST /admin/bills/engine-settings` (`admin.bills.engine-settings.update`): Saves updated settings.
  * `POST /admin/bills/engine-settings/reset` (`admin.bills.engine-settings.reset`): Factory reset.
  * `POST /admin/bills/engine-settings/diagnostic` (`admin.bills.engine-settings.diagnostic`): In-flight read-only download and extraction test without modifying database records.
* **View**: [`resources/views/admin/bills/engine-settings.blade.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/resources/views/admin/bills/engine-settings.blade.php).

---

## 6. Standalone Testing Tool (`php/test-tool/`)

A dedicated sandbox built for manual or automated verification without touching the main SaaS database or user accounts:
* **Directory**: [`php/test-tool/`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/test-tool)
* **Files**:
  * `index.php`: Self-contained web interface with live downloader, Unicode extractor, JSON export button, PDF viewer, and API documentation tab.
  * `pdfs/`: Directory where downloaded test PDFs are stored (`pdfs/{ca}_{month}_{year}.pdf`).
  * `README.md`: Usage guide.
* **How to Run**:
  ```powershell
  cd C:\Users\bccbo\Desktop\NBPDCL\tool\bill-downlod\php\test-tool
  php -S 127.0.0.1:8080
  ```
  Visit: `http://127.0.0.1:8080`.

---

## 7. Master File Map

| Purpose | File Path |
|---|---|
| **Contract** | [`app/Services/Extraction/BillExtractorInterface.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/app/Services/Extraction/BillExtractorInterface.php) |
| **Manager / Classifier** | [`app/Services/Extraction/BillExtractionManager.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/app/Services/Extraction/BillExtractionManager.php) |
| **Unicode Extractor** | [`app/Services/Extraction/JasperUnicodeExtractor.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/app/Services/Extraction/JasperUnicodeExtractor.php) |
| **Legacy Extractor** | [`app/Services/Extraction/LegacyKrutiDevExtractor.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/app/Services/Extraction/LegacyKrutiDevExtractor.php) |
| **Download Service** | [`app/Services/BillDownloadService.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/app/Services/BillDownloadService.php) |
| **Parsing Service** | [`app/Services/BillParseService.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/app/Services/BillParseService.php) |
| **Engine Orchestrator** | [`app/Services/EngineService.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/app/Services/EngineService.php) |
| **Config** | [`config/nbpdcl.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/config/nbpdcl.php) |
| **Admin Controller** | [`app/Http/Controllers/Admin/AdminBillController.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/app/Http/Controllers/Admin/AdminBillController.php) |
| **Admin View** | [`resources/views/admin/bills/engine-settings.blade.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/resources/views/admin/bills/engine-settings.blade.php) |
| **Standalone Tool** | [`php/test-tool/index.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/test-tool/index.php) |
| **Feature Tests (Engines)** | [`tests/Feature/BillExtractionEngineTest.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/tests/Feature/BillExtractionEngineTest.php) |
| **Feature Tests (Admin)** | [`tests/Feature/AdminBillingEngineSettingsTest.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/tests/Feature/AdminBillingEngineSettingsTest.php) |

---

## 8. Verification & Test Commands

To verify full suite integrity at any time, run:

```bash
# Run dual-engine extraction tests
php artisan test --compact --filter=BillExtractionEngineTest

# Run admin billing settings tests
php artisan test --compact --filter=AdminBillingEngineSettingsTest

# Run full download & processing center suite
php artisan test --compact --filter="BillExtractionEngineTest|AdminBillingEngineSettingsTest|DownloadManagementTest|ProcessingCenterTest"

# Run code style formatter (dirty files only)
vendor/bin/pint --dirty --format agent
```

---

## 9. Permanent Safety Invariant
> **CRITICAL**: Real user data, existing working consumer numbers (CAs), and User ID 9 (`shamim244d@gmail.com`) are **strictly protected**. Never modify, delete, reseed, or use real accounts for destructive testing. Always run tests against isolated test CAs or the diagnostic sandbox.
