<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillRecord;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MruApiController extends Controller
{
    /**
     * List all MRUs assigned to the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $mrus = Mru::where('user_id', $user->id)
            ->withCount('consumerAccounts')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'full_identifier', 'status'])
            ->map(fn ($m) => [
                'id' => $m->id,
                'code' => $m->code,
                'name' => $m->name,
                'full_identifier' => $m->full_identifier,
                'status' => $m->status,
                'is_locked' => $m->isLocked(),
                'consumer_count' => $m->consumer_accounts_count,
            ]);

        return response()->json([
            'success' => true,
            'mrus' => $mrus,
        ]);
    }

    /**
     * List distinct billing cycles available for an MRU or user.
     */
    public function cycles(Request $request, ?int $mruId = null): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = BillRecord::where('user_id', $user->id);
        if ($mruId) {
            $query->where('mru_id', $mruId);
        }

        $cycles = $query->select(['billing_month', 'billing_year'])
            ->distinct()
            ->orderByDesc('billing_year')
            ->orderByDesc('billing_month')
            ->get()
            ->map(fn ($c) => [
                'month' => $c->billing_month,
                'year' => $c->billing_year,
                'label' => sprintf('%s-%d', strtoupper(date('M', mktime(0, 0, 0, $c->billing_month, 1))), $c->billing_year),
            ]);

        return response()->json([
            'success' => true,
            'cycles' => $cycles,
        ]);
    }
}
