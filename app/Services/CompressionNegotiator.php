<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressionNegotiator
{
    /**
     * In-memory cache of detected server capabilities during request lifecycle.
     *
     * @var array<string, bool>|null
     */
    protected static ?array $serverCapabilities = null;

    /**
     * Clear runtime cached capabilities (useful for tests and dynamic reloads).
     */
    public static function clearRuntimeCache(): void
    {
        static::$serverCapabilities = null;
    }

    /**
     * Detect which compression extensions and functions are actively loaded on this PHP server.
     *
     * @return array<string, bool>
     */
    public static function detectServerCapabilities(): array
    {
        if (static::$serverCapabilities !== null) {
            return static::$serverCapabilities;
        }

        static::$serverCapabilities = [
            'zstd' => extension_loaded('zstd') && function_exists('zstd_compress'),
            'br' => extension_loaded('brotli') && function_exists('brotli_compress'),
            'gzip' => extension_loaded('zlib') && function_exists('gzencode'),
            'deflate' => extension_loaded('zlib') && function_exists('gzcompress'),
        ];

        return static::$serverCapabilities;
    }

    /**
     * Parse client Accept-Encoding header into normalized algorithm => quality pairs (RFC 9110).
     *
     * @return array<string, float>
     */
    public static function parseClientEncodings(string $acceptEncoding): array
    {
        if (trim($acceptEncoding) === '') {
            return [];
        }

        $qValues = [];
        $parts = explode(',', $acceptEncoding);

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $segments = explode(';', $part);
            $encoding = strtolower(trim($segments[0]));
            $quality = 1.0;

            if (isset($segments[1]) && preg_match('/q\s*=\s*([0-9.]+)/i', $segments[1], $matches)) {
                $quality = (float) $matches[1];
            }

            $qValues[$encoding] = $quality;
        }

        // Apply wildcard (*) quality to standard algorithms if not explicitly defined
        $wildcardQuality = $qValues['*'] ?? null;
        if ($wildcardQuality !== null) {
            foreach (['zstd', 'br', 'gzip', 'deflate'] as $algo) {
                if (! array_key_exists($algo, $qValues)) {
                    $qValues[$algo] = $wildcardQuality;
                }
            }
        }

        // Filter out explicitly forbidden encodings (q=0 or less)
        return array_filter($qValues, fn (float $q) => $q > 0.0);
    }

    /**
     * Resolve the optimal compression algorithm for a given HTTP request/response exchange.
     */
    public static function negotiate(Request $request, Response $response): ?string
    {
        // 1. Parse client Accept-Encoding first (zero DB queries if client sends no encoding)
        $clientEncodings = static::parseClientEncodings((string) $request->header('Accept-Encoding', ''));
        if (empty($clientEncodings)) {
            return null;
        }

        // 2. Check if compression is globally enabled (admin override takes priority over config)
        $isEnabled = (bool) SystemSetting::get('compression_enabled', config('compression.enabled', true));
        if (! $isEnabled) {
            return null;
        }

        // 3. Detect server capabilities
        $serverCapabilities = static::detectServerCapabilities();

        // 4. Exclude algorithms disabled by system administrator
        $disabledByAdmin = (array) SystemSetting::get('compression_disabled_algorithms', []);
        $availableOnServer = [];
        foreach ($serverCapabilities as $algo => $isAvailable) {
            if ($isAvailable && ! in_array($algo, $disabledByAdmin, true)) {
                $availableOnServer[$algo] = true;
            }
        }

        if (empty($availableOnServer)) {
            return null;
        }

        // 5. Check if content type is non-compressible
        $contentType = strtolower(trim((string) $response->headers->get('Content-Type', '')));
        $nonCompressible = config('compression.non_compressible_patterns', []);
        foreach ($nonCompressible as $pattern) {
            if (str_contains($contentType, $pattern)) {
                return null;
            }
        }

        // 6. Check content type preference (e.g. JSON -> Zstd, HTML -> Brotli)
        $preferredAlgo = static::getPreferredAlgorithmForContentType($contentType);

        if ($preferredAlgo !== null
            && isset($availableOnServer[$preferredAlgo])
            && isset($clientEncodings[$preferredAlgo])
            && $clientEncodings[$preferredAlgo] > 0.0
        ) {
            return $preferredAlgo;
        }

        // 7. Cascade through fallback priority hierarchy
        $priorityChain = (array) SystemSetting::get('compression_priority', config('compression.priority', [
            'zstd',
            'br',
            'gzip',
            'deflate',
        ]));

        // Sort candidates by client quality first, then server priority
        $bestCandidate = null;
        $highestClientQ = -1.0;

        foreach ($priorityChain as $candidate) {
            if (! isset($availableOnServer[$candidate]) || ! isset($clientEncodings[$candidate])) {
                continue;
            }

            $clientQ = $clientEncodings[$candidate];
            if ($clientQ > $highestClientQ) {
                $highestClientQ = $clientQ;
                $bestCandidate = $candidate;
            }
        }

        return $bestCandidate;
    }

    /**
     * Map MIME content-type to preferred compression algorithm.
     */
    public static function getPreferredAlgorithmForContentType(string $contentType): ?string
    {
        $preferences = config('compression.content_preferences', [
            'json' => 'zstd',
            'html' => 'br',
            'csv' => 'zstd',
            'text' => 'br',
            'xml' => 'br',
        ]);

        foreach ($preferences as $subtype => $algo) {
            if (str_contains($contentType, $subtype)) {
                return $algo;
            }
        }

        return null;
    }

    /**
     * Compress the given raw string content using the negotiated algorithm.
     */
    public static function compress(string $content, string $algorithm): string|false
    {
        return match ($algorithm) {
            'zstd' => function_exists('zstd_compress')
                ? zstd_compress($content, (int) config('compression.zstd_level', 3))
                : false,
            'br' => function_exists('brotli_compress')
                ? brotli_compress($content, (int) config('compression.brotli_level', 4))
                : false,
            'gzip' => function_exists('gzencode')
                ? gzencode($content, (int) config('compression.gzip_level', 6))
                : false,
            'deflate' => function_exists('gzcompress')
                ? gzcompress($content, (int) config('compression.gzip_level', 6))
                : false,
            default => false,
        };
    }

    /**
     * Return comprehensive diagnostics and status for the administrative dashboard.
     *
     * @return array<string, mixed>
     */
    public static function getStatus(): array
    {
        $capabilities = static::detectServerCapabilities();
        $disabledByAdmin = (array) SystemSetting::get('compression_disabled_algorithms', []);
        $isEnabled = (bool) SystemSetting::get('compression_enabled', config('compression.enabled', true));
        $priority = (array) SystemSetting::get('compression_priority', config('compression.priority', ['zstd', 'br', 'gzip', 'deflate']));

        $extensionVersions = [
            'zstd' => extension_loaded('zstd') ? (phpversion('zstd') ?: 'Loaded') : 'Not Installed',
            'brotli' => extension_loaded('brotli') ? (phpversion('brotli') ?: 'Loaded') : 'Not Installed',
            'zlib' => extension_loaded('zlib') ? (phpversion('zlib') ?: 'Loaded') : 'Not Installed',
        ];

        $activeChain = array_values(array_filter($priority, function ($algo) use ($capabilities, $disabledByAdmin) {
            return ($capabilities[$algo] ?? false) && ! in_array($algo, $disabledByAdmin, true);
        }));

        return [
            'enabled' => $isEnabled,
            'capabilities' => $capabilities,
            'extension_versions' => $extensionVersions,
            'disabled_by_admin' => $disabledByAdmin,
            'priority' => $priority,
            'active_chain' => $activeChain,
            'content_preferences' => config('compression.content_preferences', []),
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'min_size' => config('compression.min_size', 1024),
            'debug_header' => config('compression.debug_header', false),
        ];
    }

    /**
     * Run a live compression diagnostic benchmark across all available algorithms on sample data.
     *
     * @return array<string, mixed>
     */
    public static function runDiagnosticBenchmark(string $sampleData, ?string $label = null): array
    {
        $originalSize = strlen($sampleData);
        $results = [];
        $algorithms = ['zstd', 'br', 'gzip', 'deflate'];

        foreach ($algorithms as $algo) {
            $isCapable = match ($algo) {
                'zstd' => extension_loaded('zstd') && function_exists('zstd_compress'),
                'br' => extension_loaded('brotli') && function_exists('brotli_compress'),
                'gzip' => extension_loaded('zlib') && function_exists('gzencode'),
                'deflate' => extension_loaded('zlib') && function_exists('gzcompress'),
                default => false,
            };

            if (! $isCapable) {
                $results[$algo] = [
                    'available' => false,
                    'compressed_size' => null,
                    'ratio_percent' => null,
                    'duration_microseconds' => null,
                    'note' => 'Extension or function not loaded',
                ];

                continue;
            }

            $startTime = microtime(true);
            $compressed = static::compress($sampleData, $algo);
            $duration = (microtime(true) - $startTime) * 1000000; // microseconds

            if ($compressed === false) {
                $results[$algo] = [
                    'available' => true,
                    'compressed_size' => null,
                    'ratio_percent' => null,
                    'duration_microseconds' => round($duration, 2),
                    'note' => 'Compression failed',
                ];

                continue;
            }

            $compressedSize = strlen($compressed);
            $ratio = $originalSize > 0
                ? round((1 - ($compressedSize / $originalSize)) * 100, 2)
                : 0.0;

            $results[$algo] = [
                'available' => true,
                'compressed_size' => $compressedSize,
                'ratio_percent' => $ratio,
                'duration_microseconds' => round($duration, 2),
                'duration_ms' => round($duration / 1000, 3),
                'note' => 'OK',
            ];
        }

        return [
            'label' => $label ?: 'Benchmark Payload',
            'original_size_bytes' => $originalSize,
            'algorithms' => $results,
        ];
    }
}
