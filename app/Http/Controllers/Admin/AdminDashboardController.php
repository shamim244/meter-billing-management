<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Display the Admin overview dashboard with system-wide analytics.
     */
    public function index(): View
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $totalMrus = Mru::count();
        
        // System-wide counts (Admin bypasses BelongsToUser automatically)
        $totalConsumers = ConsumerAccount::withoutGlobalScope('belongs_to_user')->count();
        $totalBills = BillRecord::withoutGlobalScope('belongs_to_user')->count();
        $totalAmount = BillRecord::withoutGlobalScope('belongs_to_user')->sum('total_amount');

        // Recent users
        $recentUsers = User::withCount(['consumerAccounts', 'billRecords'])
            ->latest()
            ->take(5)
            ->get();

        // Recent bill activity across all agents/users
        $recentBills = BillRecord::withoutGlobalScope('belongs_to_user')
            ->with(['user', 'mru'])
            ->latest('updated_at')
            ->take(10)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'activeUsers',
            'totalMrus',
            'totalConsumers',
            'totalBills',
            'totalAmount',
            'recentUsers',
            'recentBills'
        ));
    }
}
