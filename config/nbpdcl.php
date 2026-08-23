<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NBPDCL / BSPHCL Billing API Endpoint
    |--------------------------------------------------------------------------
    |
    | Official endpoint used to pull monthly consumer bill PDFs.
    |
    */
    'api_url' => env('NBPDCL_API_URL', 'https://api.bsphcl.co.in/nbWSMobileApp/ViewBill.asmx/GetViewBill?strCANumber='),

    /*
    |--------------------------------------------------------------------------
    | Network Timeout & Concurrency Settings
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('NBPDCL_TIMEOUT', 45),
    'concurrency' => (int) env('NBPDCL_CONCURRENCY', 10),
];
