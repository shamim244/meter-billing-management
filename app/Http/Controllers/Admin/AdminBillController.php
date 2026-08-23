<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillRecord;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBillController extends Controller
{
    /**
     * Display a listing of all bills across all billing agents / users.
     */
    public function index(Request $request): View
    {
        $userId = $request->get('user_id');
        $mruId = $request->get('mru_id');
        $month = $request->get('month');
        $year = $request->get('year');
        $search = trim($request->get('search', ''));

        $query = BillRecord::withoutGlobalScope('belongs_to_user')
            ->with(['user', 'mru']);

        if (!empty($userId)) {
            $query->where('user_id', $userId);
        }

        if (!empty($mruId)) {
            $query->where('mru_id', $mruId);
        }

        if (!empty($month)) {
            $query->where('billing_month', (int)$month);
        }

        if (!empty($year)) {
            $query->where('billing_year', (int)$year);
        }

        if (!empty($search)) {
            $escaped = addcslashes($search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('ca_number', 'like', "%{$escaped}%")
                  ->orWhere('consumer_name', 'like', "%{$escaped}%")
                  ->orWhere('meter_no', 'like', "%{$escaped}%");
            });
        }

        $bills = $query->orderBy('created_at', 'desc')->paginate(25);

        $users = User::orderBy('name')->get();
        $mrus = Mru::orderBy('code')->get();

        $periods = BillRecord::withoutGlobalScope('belongs_to_user')
            ->select('billing_month', 'billing_year')
            ->distinct()
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get();

        return view('admin.bills.index', compact(
            'bills',
            'users',
            'mrus',
            'periods',
            'userId',
            'mruId',
            'month',
            'year',
            'search'
        ));
    }
}
