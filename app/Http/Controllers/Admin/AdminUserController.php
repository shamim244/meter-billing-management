<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $query = User::with('roles')
            ->withCount(['consumerAccounts', 'billRecords']);

        if (!empty($search)) {
            $escaped = addcslashes($search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                  ->orWhere('email', 'like', "%{$escaped}%")
                  ->orWhere('phone', 'like', "%{$escaped}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.users.index', compact('users', 'search'));
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
            'storage_limit_mb' => 'required|integer|min:10|max:1048576', // 10MB to 1TB
            'plan_tier' => 'required|string|in:free,starter,pro,enterprise',
        ]);

        $user->update([
            'storage_limit_mb' => $request->storage_limit_mb,
            'plan_tier' => $request->plan_tier,
        ]);

        return back()->with('success', "Storage quota for '{$user->name}' updated to {$user->storage_limit_mb} MB ({$user->plan_tier} plan).");
    }
}
