<?php

namespace App\Http\Controllers\UserPanel;

use App\Http\Controllers\Controller;
use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Services\Backup\AgentWorkspaceExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgentBackupController extends Controller
{
    public function __construct(
        protected AgentWorkspaceExportService $exportService
    ) {}

    /**
     * Display Agent Workspace Data Export Page.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $stats = [
            'total_mrus' => Mru::where('user_id', $user->id)->count(),
            'total_consumers' => ConsumerAccount::where('user_id', $user->id)->count(),
            'total_bills' => BillRecord::where('user_id', $user->id)->count(),
            'wallet_balance' => $user->wallet ? $user->wallet->balance / 100 : 0,
        ];

        return view('user-panel.backup', compact('stats', 'user'));
    }

    /**
     * Generate and download the full agent workspace package.
     */
    public function download(Request $request)
    {
        $user = $request->user();
        $tempZip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'workspace_export_' . $user->id . '_' . time() . '.zip';

        try {
            $this->exportService->export($user, $tempZip);

            $filename = 'nbpdcl_workspace_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $user->name) . '_' . date('Ymd_His') . '.zip';

            return response()->download($tempZip, $filename, [
                'Content-Type' => 'application/zip',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            @unlink($tempZip);
            return back()->with('error', 'Export generation failed: ' . $e->getMessage());
        }
    }
}
