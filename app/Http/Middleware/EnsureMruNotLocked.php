<?php

namespace App\Http\Middleware;

use App\Models\Mru;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMruNotLocked
{
    /**
     * Handle an incoming request to verify that the target MRU is not locked for restricted operations.
     * Enforces PRD Section 5.1 Step 3:
     * Allowed: view, rename, delete, add consumer, remove consumer
     * Blocked: modify consumer details, create cycle, process/download PDF
     */
    public function handle(Request $request, Closure $next, ?string $action = null): Response
    {
        $mru = $request->route('mru') ?? $request->route('mru_id') ?? $request->input('mru_id');

        if (!$mru) {
            return $next($request);
        }

        if (is_numeric($mru) || is_string($mru)) {
            $mru = Mru::find($mru);
        }

        if ($mru instanceof Mru && $mru->isLocked()) {
            // Determine action if not explicitly passed
            $actionName = $action ?? $this->detectAction($request);

            $allowedActions = ['view', 'read', 'rename', 'delete', 'add_consumer', 'remove_consumer'];

            if (!in_array(strtolower($actionName), $allowedActions, true)) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'error' => 'mru_locked',
                        'message' => "MRU '{$mru->name} ({$mru->code})' is currently locked ({$mru->locked_reason}). Creating cycles, processing PDFs, and modifying consumer details are disabled until unlocked.",
                        'mru_id' => $mru->id,
                        'locked_reason' => $mru->locked_reason,
                        'is_locked' => true,
                    ], 403);
                }

                return redirect()->route('mrus.show', $mru)->with('error', "MRU '{$mru->name} ({$mru->code})' is currently locked. Please unlock the MRU to process bills or create cycles.");
            }
        }

        return $next($request);
    }

    /**
     * Auto-detect restricted actions from HTTP route/method if not provided in middleware parameter.
     */
    protected function detectAction(Request $request): string
    {
        $routeName = $request->route()?->getName() ?? '';
        $method = $request->method();

        if (str_contains($routeName, 'process') || str_contains($routeName, 'download') || str_contains($routeName, 'extract')) {
            return 'process_pdf';
        }

        if (str_contains($routeName, 'cycle') || str_contains($routeName, 'create-cycle')) {
            return 'create_cycle';
        }

        if ($method === 'PUT' || $method === 'PATCH') {
            if (str_contains($routeName, 'consumer') && !str_contains($routeName, 'add') && !str_contains($routeName, 'remove')) {
                return 'modify_consumer_details';
            }
            if (str_contains($routeName, 'rename') || str_contains($routeName, 'update')) {
                return 'rename';
            }
        }

        if ($method === 'DELETE') {
            return 'delete';
        }

        return 'view';
    }
}
