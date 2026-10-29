<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AppDownloadController extends Controller
{
    /**
     * Display the mobile app download landing page.
     */
    public function index()
    {
        $apkPath = public_path('downloads/nbpdcl-field-reader.apk');
        $hasApk = File::exists($apkPath);
        $fileSizeMb = $hasApk ? round(File::size($apkPath) / (1024 * 1024), 2) : 0;
        $modifiedAt = $hasApk ? date('d M Y, h:i A', File::lastModified($apkPath)) : null;

        return view('download.app', [
            'hasApk' => $hasApk,
            'fileSizeMb' => $fileSizeMb,
            'modifiedAt' => $modifiedAt,
            'version' => '1.0.0',
        ]);
    }

    /**
     * Download the release APK file.
     */
    public function downloadApk(): BinaryFileResponse|RedirectResponse
    {
        $apkPath = public_path('downloads/nbpdcl-field-reader.apk');
        if (! File::exists($apkPath)) {
            return redirect()->route('download.app')->with('error', 'APK package is being prepared. Please try again shortly.');
        }

        return response()->download($apkPath, 'nbpdcl-field-reader-v1.0.0.apk', [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }
}
