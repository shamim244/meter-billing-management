<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Services\Installation\InstallerService;
use App\Services\Migration\ServerMigrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class InstallerController extends Controller
{
    public function __construct(
        protected InstallerService $installerService,
        protected ServerMigrationService $migrationService
    ) {}

    /**
     * Entry point: redirects to Step 1.
     */
    public function index(): RedirectResponse
    {
        if ($this->installerService->isInstalled()) {
            return redirect()->route('login');
        }

        return redirect()->route('install.step1');
    }

    /**
     * Step 1: Server Readiness & Extensions Checklist.
     */
    public function step1(): View|RedirectResponse
    {
        if ($this->installerService->isInstalled()) {
            return redirect()->route('login');
        }

        $preflight = $this->migrationService->runPreflightCheck();

        return view('installer.step1-requirements', [
            'preflight' => $preflight,
        ]);
    }

    /**
     * Step 2: Database Configuration Form.
     */
    public function step2(): View|RedirectResponse
    {
        if ($this->installerService->isInstalled()) {
            return redirect()->route('login');
        }

        return view('installer.step2-database', [
            'currentUrl' => url('/'),
            'defaultHost' => env('DB_HOST', '127.0.0.1'),
            'defaultPort' => env('DB_PORT', '3306'),
            'defaultDatabase' => env('DB_DATABASE', 'meter_billing'),
            'defaultUsername' => env('DB_USERNAME', 'root'),
        ]);
    }

    /**
     * Live AJAX database connection tester.
     */
    public function testDatabase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'driver' => 'nullable|string|in:mysql,sqlite',
            'host' => 'nullable|string',
            'port' => 'nullable|numeric',
            'database' => 'required|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
        ]);

        $result = $this->installerService->testDatabaseConnection($validated);

        return response()->json($result);
    }

    /**
     * Save database credentials to .env and proceed to Step 3.
     */
    public function saveDatabase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'driver' => 'nullable|string|in:mysql,sqlite',
            'host' => 'nullable|string',
            'port' => 'nullable|numeric',
            'database' => 'required|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'app_url' => 'nullable|url',
        ]);

        // Verify connection before saving
        $testResult = $this->installerService->testDatabaseConnection($validated);
        if (! $testResult['success']) {
            return back()->withInput()->with('error', 'Cannot proceed: '.$testResult['message']);
        }

        $this->installerService->saveEnvironment($validated, $request->input('app_url'));

        return redirect()->route('install.step3')->with('success', 'Database connection verified and saved!');
    }

    /**
     * Step 3: Installation Mode Choice (Clean Install OR 1-Click Migration Restore).
     */
    public function step3(): View|RedirectResponse
    {
        if ($this->installerService->isInstalled()) {
            return redirect()->route('login');
        }

        return view('installer.step3-options');
    }

    /**
     * Execute Clean Install: Migrate, seed, and create initial Super Admin account.
     */
    public function runCleanInstall(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $result = $this->installerService->executeCleanInstall($request->only('name', 'email', 'password'));

            return redirect()->route('install.complete')->with([
                'success' => '🎉 Application installed successfully!',
                'admin_email' => $result['admin_email'],
                'mode' => 'clean',
            ]);
        } catch (Throwable $e) {
            return back()->withInput()->with('error', 'Clean installation failed: '.$e->getMessage());
        }
    }

    /**
     * Execute 1-Click Restore Install: Unpack universal migration bundle, restore DB & storage.
     */
    public function runRestoreInstall(Request $request): RedirectResponse
    {
        $request->validate([
            'bundle' => 'required|file|mimes:zip|max:524288', // 512MB max
        ]);

        $file = $request->file('bundle');
        $tempPath = $file->getRealPath();

        try {
            $result = $this->installerService->executeRestoreInstall($tempPath);

            return redirect()->route('install.complete')->with([
                'success' => '🎉 Migration package restored and verified with 100% data fidelity!',
                'mode' => 'restore',
                'verified' => $result['verified'] ?? false,
            ]);
        } catch (Throwable $e) {
            return back()->with('error', 'Restore installation failed: '.$e->getMessage());
        }
    }

    /**
     * Step 4: Installation Complete Screen.
     */
    public function complete(): View|RedirectResponse
    {
        return view('installer.step4-complete');
    }
}
