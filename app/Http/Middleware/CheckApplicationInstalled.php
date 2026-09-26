<?php

namespace App\Http\Middleware;

use App\Services\Installation\InstallerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApplicationInstalled
{
    public function __construct(
        protected InstallerService $installerService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isInstalled = $this->installerService->isInstalled();

        if (! $isInstalled) {
            $this->installerService->ensureInstallerPrerequisites();

            if (empty(config('app.key'))) {
                config(['app.key' => 'base64:'.base64_encode('temp_installer_key_32_bytes_!!')]);
            }

            // Temporary session & cache safety: fallback to file if uninstalled
            config([
                'session.driver' => 'file',
                'cache.default' => 'file',
            ]);

            // Allow installer routes, storage assets, and system health/docs endpoints
            if ($request->is('install*') || $request->is('documentation*') || $request->is('up') || $request->is('build/*') || $request->is('favicon.ico') || $request->is('storage/*')) {
                return $next($request);
            }

            // Redirect all other web requests to the installer wizard
            return redirect()->route('install.index');
        }

        // If already installed and user attempts to access installer, block and redirect
        if ($request->is('install*')) {
            return redirect()->route('login')->with('info', 'Application is already installed and locked.');
        }

        return $next($request);
    }
}
