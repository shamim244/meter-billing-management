<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NBPDCL / BSPHCL Billing API Endpoint (Legacy)
    |--------------------------------------------------------------------------
    |
    | Legacy endpoint used to pull monthly consumer bill PDFs.
    |
    */
    'api_url' => env('NBPDCL_API_URL', 'https://api.bsphcl.co.in/nbWSMobileApp/ViewBill.asmx/GetViewBill?strCANumber='),

    /*
    |--------------------------------------------------------------------------
    | NBPDCL FluentGrid / WSS API Endpoint & Encryption Key (Latest Live Bills)
    |--------------------------------------------------------------------------
    |
    | Official WSS endpoint used to pull latest 2-page Unicode JasperReports bill PDFs.
    |
    */
    'wss_url' => env('NBPDCL_WSS_URL', 'https://wss.nbpdcl.co.in/fgweb/web/json/plugin/com.fluentgrid.cp.api.NscUploadBridgeService/service?&rtype=DOWNLOAD'),
    'aes_key' => env('NBPDCL_AES_KEY', 'fgwebcp@2020'),

    /*
    |--------------------------------------------------------------------------
    | Active Download Driver & Extraction Engine Configuration
    |--------------------------------------------------------------------------
    |
    | download_driver: 'auto' (smart fallback), 'wss' (FluentGrid), 'legacy' (BSPHCL ASMX)
    | extraction_engine: 'auto' (layout signature detect), 'jasper_unicode', 'legacy_krutidev'
    |
    */
    'download_driver' => env('NBPDCL_DOWNLOAD_DRIVER', 'auto'),
    'extraction_engine' => env('NBPDCL_EXTRACTION_ENGINE', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Network Timeout & Concurrency Settings
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('NBPDCL_TIMEOUT', 45),
    'concurrency' => (int) env('NBPDCL_CONCURRENCY', 10),
];
