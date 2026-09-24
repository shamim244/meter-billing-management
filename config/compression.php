<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HTTP Response Compression Master Toggle
    |--------------------------------------------------------------------------
    |
    | When enabled, API and web text responses will be compressed using the
    | best mutually supported algorithm between the server and the client.
    |
    */
    'enabled' => env('COMPRESSION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Minimum Response Size Threshold (Bytes)
    |--------------------------------------------------------------------------
    |
    | Responses smaller than this threshold are sent uncompressed to avoid
    | CPU overhead and header overhead where compression yields negligible gains.
    |
    */
    'min_size' => (int) env('COMPRESSION_MIN_SIZE', 1024),

    /*
    |--------------------------------------------------------------------------
    | Debug Response Headers
    |--------------------------------------------------------------------------
    |
    | When enabled (or when APP_DEBUG=true), adds X-Compression-Algorithm and
    | X-Compression-Ratio headers to inspected responses for easy auditing.
    |
    */
    'debug_header' => env('COMPRESSION_DEBUG_HEADER', false),

    /*
    |--------------------------------------------------------------------------
    | Fallback Priority Order (Speed First -> Ratio -> Universal Default)
    |--------------------------------------------------------------------------
    |
    | Ordered hierarchy of supported algorithms. If the content-preferred
    | algorithm is not accepted by the client or not loaded on the server,
    | the negotiator falls through this cascading chain.
    |
    */
    'priority' => [
        'zstd',
        'br',
        'gzip',
        'deflate',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content-Type Optimal Algorithm Preferences
    |--------------------------------------------------------------------------
    |
    | Maps specific MIME subtypes to their optimal compression algorithm:
    | - json: Zstandard delivers ultra-fast compression suitable for high-throughput APIs.
    | - html: Brotli excels with pre-trained web dictionary for markup.
    | - csv:  Zstandard efficiently compresses repetitive tabular text records.
    | - text: Brotli provides maximum ratio for prose and strings.
    | - xml:  Brotli gives best ratio on verbose tag-heavy documents.
    |
    */
    'content_preferences' => [
        'json' => 'zstd',
        'html' => 'br',
        'csv' => 'zstd',
        'text' => 'br',
        'xml' => 'br',
    ],

    /*
    |--------------------------------------------------------------------------
    | Compression Levels per Algorithm
    |--------------------------------------------------------------------------
    |
    | - brotli: 0 to 11 (4 gives optimal ratio-to-speed balance for dynamic responses)
    | - zstd:   1 to 22 (3 is the official balanced default for real-time web traffic)
    | - gzip:   1 to 9  (6 is standard balance for zlib)
    |
    */
    'brotli_level' => (int) env('COMPRESSION_BROTLI_LEVEL', 4),
    'zstd_level' => (int) env('COMPRESSION_ZSTD_LEVEL', 3),
    'gzip_level' => (int) env('COMPRESSION_GZIP_LEVEL', 6),

    /*
    |--------------------------------------------------------------------------
    | Non-Compressible MIME Type Patterns
    |--------------------------------------------------------------------------
    |
    | Patterns for media and archives that are already compressed internally.
    | Attempting to re-compress these burns server CPU with negative/zero gain.
    |
    */
    'non_compressible_patterns' => [
        'image/',
        'video/',
        'audio/',
        'application/pdf',
        'application/zip',
        'application/gzip',
        'application/x-gzip',
        'application/x-bzip',
        'application/x-bzip2',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'application/octet-stream',
    ],
];
