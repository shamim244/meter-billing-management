<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnsureCompressedResponse
{
    /**
     * Minimum byte size threshold for non-API / non-JSON responses to be compressed.
     */
    protected const MIN_COMPRESSION_SIZE = 1024;

    /**
     * Non-compressible MIME type patterns (already compressed or raw binary formats).
     */
    protected const NON_COMPRESSIBLE_PATTERNS = [
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
    ];

    /**
     * Handle an incoming request and compress JSON/API responses when supported.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! extension_loaded('zlib')) {
            return $response;
        }

        if (filter_var(ini_get('zlib.output_compression'), FILTER_VALIDATE_BOOLEAN)) {
            return $response;
        }

        // Skip streamed or binary file downloads
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return $response;
        }

        // Skip empty, informational, or redirect responses
        if ($response->isEmpty() || $response->isInformational() || $response->isRedirection() || in_array($response->getStatusCode(), [204, 304], true)) {
            return $response;
        }

        // Do not double-compress if already encoded
        if ($response->headers->has('Content-Encoding')) {
            return $response;
        }

        $encoding = $this->determineAcceptableEncoding($request);
        if ($encoding === null) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || $content === '') {
            return $response;
        }

        $contentType = strtolower(trim((string) $response->headers->get('Content-Type', '')));

        // Check if content-type is non-compressible (e.g. image, video, audio, zip, pdf)
        foreach (self::NON_COMPRESSIBLE_PATTERNS as $pattern) {
            if (str_contains($contentType, $pattern)) {
                return $response;
            }
        }

        $isApiRoute = $request->is('api/*');
        $isJson = str_contains($contentType, 'json');
        $isLargeResponse = strlen($content) >= self::MIN_COMPRESSION_SIZE;

        // Compress API/JSON responses or any response exceeding minimum size threshold
        if (! $isApiRoute && ! $isJson && ! $isLargeResponse) {
            return $response;
        }

        $compressed = match ($encoding) {
            'gzip' => gzencode($content, 6),
            'deflate' => gzcompress($content, 6),
            default => false,
        };

        if ($compressed === false) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', $encoding);
        $response->headers->set('Content-Length', (string) strlen($compressed));
        $response->setVary('Accept-Encoding', false);

        return $response;
    }

    /**
     * Determine best acceptable encoding according to RFC 7231 / RFC 9110 quality values.
     */
    protected function determineAcceptableEncoding(Request $request): ?string
    {
        $acceptEncoding = (string) $request->header('Accept-Encoding', '');
        if ($acceptEncoding === '') {
            return null;
        }

        $qValues = [];
        $parts = explode(',', $acceptEncoding);

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $segments = explode(';', $part);
            $enc = strtolower(trim($segments[0]));
            $q = 1.0;

            if (isset($segments[1]) && preg_match('/q\s*=\s*([0-9.]+)/i', $segments[1], $matches)) {
                $q = (float) $matches[1];
            }

            $qValues[$enc] = $q;
        }

        // Check wildcard * quality
        $wildcardQ = $qValues['*'] ?? null;

        // Determine effective quality for gzip and deflate
        $qGzip = $qValues['gzip'] ?? ($wildcardQ !== null ? $wildcardQ : 0.0);
        $qDeflate = $qValues['deflate'] ?? ($wildcardQ !== null ? $wildcardQ : 0.0);

        // If explicitly specified with q <= 0, enforce 0.0
        if (isset($qValues['gzip']) && $qValues['gzip'] <= 0.0) {
            $qGzip = 0.0;
        }
        if (isset($qValues['deflate']) && $qValues['deflate'] <= 0.0) {
            $qDeflate = 0.0;
        }

        // Neither acceptable
        if ($qGzip <= 0.0 && $qDeflate <= 0.0) {
            return null;
        }

        // Choose higher quality; prefer gzip on tie
        if ($qGzip >= $qDeflate && $qGzip > 0.0) {
            return 'gzip';
        }

        if ($qDeflate > 0.0) {
            return 'deflate';
        }

        return null;
    }
}
