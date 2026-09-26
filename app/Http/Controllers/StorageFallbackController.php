<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageFallbackController extends Controller
{
    /**
     * Fallback file server for storage assets on shared hosting environments
     * where symbolic links cannot be created or are restricted.
     */
    public function show(Request $request, string $path): BinaryFileResponse
    {
        // 1. Path traversal & security sanitization (including URL-decoded checks)
        $decodedPath = rawurldecode($path);
        if (str_contains($decodedPath, '..') || str_contains($decodedPath, "\0") || str_contains($decodedPath, '\\')) {
            abort(404, 'Invalid asset path');
        }

        // Block access to hidden files and directories (e.g. .git, .env, .htaccess)
        $segments = explode('/', str_replace('\\', '/', $decodedPath));
        foreach ($segments as $segment) {
            if (str_starts_with($segment, '.')) {
                abort(404, 'Invalid asset path');
            }
        }

        $cleanPath = ltrim($decodedPath, '/');
        $filePath = storage_path('app/public/'.$cleanPath);

        // 2. Ensure target exists and is a regular file
        if (! File::exists($filePath) || ! is_file($filePath)) {
            abort(404, 'Asset not found');
        }

        // 3. Verify realpath stays strictly inside storage/app/public directory
        $basePublic = realpath(storage_path('app/public'));
        $realFile = realpath($filePath);

        if (! $realFile || ! $basePublic) {
            abort(404, 'Asset not found');
        }

        $baseWithSeparator = rtrim($basePublic, '\\/').DIRECTORY_SEPARATOR;
        if (! str_starts_with($realFile, $baseWithSeparator) && $realFile !== $basePublic) {
            abort(404, 'Access denied');
        }

        // 4. Secure MIME type detection with reliable web asset mapping
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'pdf' => 'application/pdf',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
        ];

        $mimeType = $mimeTypes[$extension] ?? (File::mimeType($filePath) ?: 'application/octet-stream');

        // 5. Binary file response with cache headers
        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
