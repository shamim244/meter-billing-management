<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class DeveloperDocumentationController extends Controller
{
    /**
     * Serve developer documentation assets and markdown files.
     */
    public function show(Request $request, ?string $file = null): Response
    {
        $baseDir = base_path('documentation');

        if (empty($file) || $file === '/') {
            $file = 'index.html';
        }

        // Prevent directory traversal attacks
        $normalized = str_replace(['../', '..\\'], '', $file);
        $normalized = ltrim($normalized, '/\\');
        $fullPath = $baseDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $normalized);

        // Verify that path stays inside base documentation directory
        $realBase = realpath($baseDir);
        $realFile = realpath($fullPath);

        if ($realFile !== false && ! str_starts_with($realFile, $realBase)) {
            abort(403, 'Unauthorized directory traversal.');
        }

        if (File::isDirectory($fullPath)) {
            if (File::exists($fullPath.DIRECTORY_SEPARATOR.'README.md')) {
                $fullPath = $fullPath.DIRECTORY_SEPARATOR.'README.md';
            } elseif (File::exists($fullPath.DIRECTORY_SEPARATOR.'index.html')) {
                $fullPath = $fullPath.DIRECTORY_SEPARATOR.'index.html';
            } else {
                $fullPath = $baseDir.DIRECTORY_SEPARATOR.'index.html';
            }
        }

        // Fallback nested sidebar requests to root _sidebar.md
        if (str_ends_with($fullPath, '_sidebar.md') && ! File::exists($fullPath)) {
            $fullPath = $baseDir.DIRECTORY_SEPARATOR.'_sidebar.md';
        }

        if (! File::exists($fullPath)) {
            // Check if Docsify requested a .txt file with .md appended
            if (str_ends_with($fullPath, '.txt.md') && File::exists(substr($fullPath, 0, -3))) {
                $fullPath = substr($fullPath, 0, -3);
            } elseif (File::exists($fullPath.'.md')) {
                // Check if requested file exists with .md extension
                $fullPath = $fullPath.'.md';
            } elseif (File::exists($baseDir.DIRECTORY_SEPARATOR.'index.html')) {
                // SPA fallback: return index.html for Docsify client routing
                $fullPath = $baseDir.DIRECTORY_SEPARATOR.'index.html';
            } else {
                abort(404, 'Documentation file not found.');
            }
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        $mimeType = match ($extension) {
            'html' => 'text/html; charset=UTF-8',
            'md', 'txt' => 'text/markdown; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'css' => 'text/css; charset=UTF-8',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'json' => 'application/json',
            default => 'text/plain; charset=UTF-8',
        };

        // ETag verification for fast 304 revalidation
        $mtime = filemtime($fullPath);
        $etag = '"'.md5($fullPath.$mtime).'"';
        $clientEtag = $request->header('If-None-Match');

        if ($clientEtag && trim($clientEtag, '"') === trim($etag, '"')) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=86400, must-revalidate',
            ]);
        }

        // Static vendor assets: cache aggressively for speed
        if (str_contains($fullPath, DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR) || in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'ico', 'webp', 'woff2', 'woff', 'ttf'])) {
            $response = in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'ico', 'webp', 'woff2', 'woff', 'ttf'])
                ? response()->file($fullPath)
                : response(File::get($fullPath), 200);

            return $response->withHeaders([
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=604800, immutable',
                'ETag' => $etag,
            ]);
        }

        return response(File::get($fullPath), 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=300, must-revalidate',
            'ETag' => $etag,
        ]);
    }
}
