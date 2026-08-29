<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentSubscription;
use App\Models\BillRecord;
use App\Models\Mru;
use App\Models\PlanUpgradeLog;
use App\Models\User;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    /**
     * Display a listing of all users/billing agents.
     */
    public function index(Request $request): View
    {
        $search = trim($request->get('search', ''));
        $roleFilter = $request->get('role', 'all');
        $statusFilter = $request->get('status', 'all');

        $query = User::with(['roles', 'activeSubscription.plan', 'wallet'])
            ->withCount(['consumerAccounts', 'billRecords', 'mrus']);

        if (!empty($search)) {
            $escaped = addcslashes($search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                  ->orWhere('email', 'like', "%{$escaped}%")
                  ->orWhere('phone', 'like', "%{$escaped}%");
            });
        }

        if ($roleFilter !== 'all') {
            $query->role($roleFilter);
        }

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'subscribed_users' => AgentSubscription::where('status', 'active')
                ->whereIn('lifecycle_status', ['active', 'renewal_due', 'grace_period'])
                ->distinct('user_id')
                ->count('user_id'),
        ];

        return view('admin.users.index', compact('users', 'search', 'roleFilter', 'statusFilter', 'stats'));
    }

    /**
     * Show form to create a new user.
     */
    public function create(): View
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|exists:roles,name',
            'status' => 'required|string|in:active,suspended',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'status' => $request->status,
        ]);

        $user->assignRole($request->role);

        return redirect()->route('admin.users.index')->with('success', "User '{$user->name}' created successfully.");
    }

    /**
     * Display a 360° User Dossier Inspector.
     */
    public function show(User $user): View
    {
        $user->load(['roles', 'wallet', 'activeSubscription.plan']);

        $mrus = Mru::where('user_id', $user->id)
            ->withCount(['consumerAccounts', 'billingCycles'])
            ->orderBy('created_at', 'desc')
            ->get();

        $billStats = [
            'total' => BillRecord::where('user_id', $user->id)->count(),
            'submitted' => BillRecord::where('user_id', $user->id)->where('review_status', 'submitted')->count(),
            'doubt' => BillRecord::where('user_id', $user->id)->where('review_status', 'doubt')->count(),
            'critical' => BillRecord::where('user_id', $user->id)->where('review_status', 'critical')->count(),
            'downloaded_pdfs' => BillRecord::where('user_id', $user->id)->where('download_status', 'downloaded')->count(),
        ];

        $recentTransactions = $user->transactions()
            ->latest('id')
            ->take(6)
            ->get();

        $subscriptionHistory = AgentSubscription::where('user_id', $user->id)
            ->with('plan')
            ->latest('id')
            ->take(6)
            ->get();

        $transitions = \App\Models\PlanUpgradeLog::where('user_id', $user->id)
            ->with(['fromPlan', 'toPlan'])
            ->latest('id')
            ->take(6)
            ->get();

        $storageMetrics = [
            'used_bytes' => $user->getStorageUsedBytes(),
            'used_mb' => round($user->getStorageUsedBytes() / (1024 * 1024), 2),
            'limit_mb' => $user->storage_limit_mb ?? 100,
            'percent' => $user->getStorageUsagePercent(),
        ];

        return view('admin.users.show', compact(
            'user',
            'mrus',
            'billStats',
            'recentTransactions',
            'subscriptionHistory',
            'transitions',
            'storageMetrics'
        ));
    }

    /**
     * Show form to edit user profile and account details.
     */
    public function edit(User $user): View
    {
        $roles = Role::all();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update user profile information.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|exists:roles,name',
            'status' => 'required|string|in:active,suspended',
            'storage_limit_mb' => 'required|integer|min:10|max:1048576',
            'plan_tier' => 'required|string|in:free,starter,pro,enterprise',
            'email_verified' => 'nullable|boolean',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->status = $request->status;
        $user->storage_limit_mb = $request->storage_limit_mb;
        $user->plan_tier = $request->plan_tier;

        if ($request->boolean('email_verified') && !$user->email_verified_at) {
            $user->email_verified_at = now();
        } elseif (!$request->boolean('email_verified') && $user->email_verified_at) {
            $user->email_verified_at = null;
        }

        $user->save();
        $user->syncRoles([$request->role]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', "Profile for user '{$user->name}' updated successfully.");
    }

    /**
     * Reset/Update password for a user.
     */
    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('admin.users.show', $user)
            ->with('success', "Password for '{$user->name}' has been updated successfully.");
    }

    /**
     * Impersonate an operator/user account.
     */
    public function impersonate(User $user): RedirectResponse
    {
        $admin = Auth::user();

        if ($admin->id === $user->id) {
            return back()->with('error', 'You cannot impersonate your own administrator account.');
        }

        if ($user->hasRole('admin')) {
            return back()->with('error', 'You cannot impersonate another administrator.');
        }

        // Store original admin id in session
        session(['impersonated_by' => $admin->id]);

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', "You are now logged in as {$user->name}. You can return to Admin anytime using the top bar.");
    }

    /**
     * Exit impersonation session and return to Administrator account.
     */
    public function leaveImpersonation(): RedirectResponse
    {
        if (!session()->has('impersonated_by')) {
            return redirect()->route('dashboard');
        }

        $adminId = session()->pull('impersonated_by');
        $admin = User::find($adminId);

        if ($admin) {
            Auth::login($admin);
            return redirect()->route('admin.users.index')
                ->with('success', 'Exited impersonation. Returned to Administrator account.');
        }

        return redirect()->route('login');
    }

    /**
     * Toggle active/suspended status for a user.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        // Don't allow suspending self
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot change your own account status.');
        }

        $user->status = $user->status === 'active' ? 'suspended' : 'active';
        $user->save();

        return back()->with('success', "User '{$user->name}' status updated to '{$user->status}'.");
    }

    /**
     * Update storage quota limit and plan tier for a user.
     */
    public function updateQuota(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'storage_limit_mb' => 'required|integer|min:10|max:1048576',
            'plan_tier' => 'required|string|in:free,starter,pro,enterprise',
        ]);

        $user->update([
            'storage_limit_mb' => $request->storage_limit_mb,
            'plan_tier' => $request->plan_tier,
        ]);

        return back()->with('success', "Storage quota for '{$user->name}' updated to {$user->storage_limit_mb} MB ({$user->plan_tier} plan).");
    }
}
