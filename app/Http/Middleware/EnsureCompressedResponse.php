<?php

namespace App\Http\Middleware;

use App\Services\CompressionNegotiator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnsureCompressedResponse
{
    /**
     * Minimum byte size threshold for responses to be compressed.
     */
    protected const MIN_COMPRESSION_SIZE = 1024;

    /**
     * Handle an incoming request and compress responses using adaptive negotiation.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Skip streamed or binary file downloads
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return $response;
        }

        // Skip empty, informational, or redirect responses
        if ($response->isEmpty() || $response->isInformational() || $response->isRedirection() || in_array($response->getStatusCode(), [204, 304], true)) {
            return $response;
        }

        // Skip if client did not request compression
        $acceptEncoding = (string) $request->header('Accept-Encoding', '');
        if (trim($acceptEncoding) === '') {
            return $response;
        }

        // Do not double-compress if already encoded
        if ($response->headers->has('Content-Encoding')) {
            return $response;
        }

        // Skip if output compression is already active at php.ini level
        if (filter_var(ini_get('zlib.output_compression'), FILTER_VALIDATE_BOOLEAN)) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || $content === '') {
            return $response;
        }

        $contentType = strtolower(trim((string) $response->headers->get('Content-Type', '')));
        $isApiRoute = $request->is('api/*');
        $isJson = str_contains($contentType, 'json');
        $originalLength = strlen($content);
        $minSize = (int) config('compression.min_size', self::MIN_COMPRESSION_SIZE);
        $isLargeResponse = $originalLength >= $minSize;

        // Only compress API/JSON responses or responses exceeding the minimum size threshold
        if (! $isApiRoute && ! $isJson && ! $isLargeResponse) {
            return $response;
        }

        // Negotiate optimal algorithm based on server capabilities, client preferences, and content type
        $encoding = CompressionNegotiator::negotiate($request, $response);
        if ($encoding === null) {
            return $response;
        }

        $compressed = CompressionNegotiator::compress($content, $encoding);
        if ($compressed === false) {
            return $response;
        }

        $compressedLength = strlen($compressed);

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', $encoding);
        $response->headers->set('Content-Length', (string) $compressedLength);
        $response->setVary('Accept-Encoding', false);

        // Optional diagnostic debug headers
        if (config('app.debug') || config('compression.debug_header', false)) {
            $response->headers->set('X-Compression-Algorithm', $encoding);
            if ($originalLength > 0) {
                $savedRatio = round((1 - ($compressedLength / $originalLength)) * 100, 1);
                $response->headers->set('X-Compression-Ratio', "{$savedRatio}%");
            }
        }

        return $response;
    }
}
