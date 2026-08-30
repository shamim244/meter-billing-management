<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentSubscription;
use App\Models\BillRecord;
use App\Models\Mru;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\PlanUpgradeLog;
use App\Models\User;
use App\Services\Plan\PlanService;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserController extends Controller
{
    public function __construct(
        protected PlanService $planService
    ) {}

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
     * Export users list as CSV with active search & filter scoping.
     */
    public function exportCsv(Request $request): StreamedResponse
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

        $users = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="users_export_' . now()->format('Y_m_d_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            // CSV Header Row
            fputcsv($file, [
                'User ID',
                'Name',
                'Email',
                'Phone',
                'Email Verified',
                'Roles',
                'Account Status',
                'Plan Tier',
                'Active Subscription Plan',
                'Subscription Lifecycle Status',
                'Subscription End Date',
                'MRUs Count',
                'Consumers Count',
                'Bills Processed',
                'Wallet Balance (INR)',
                'Storage Limit (MB)',
                'Registered Date',
            ]);

            foreach ($users as $user) {
                $sub = $user->activeSubscription;
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone ?? 'N/A',
                    $user->email_verified_at ? 'Yes' : 'No',
                    $user->roles->pluck('name')->implode(', '),
                    $user->status,
                    $user->plan_tier ?? 'free',
                    $sub ? ($sub->plan->name ?? 'Subscribed') : 'None',
                    $sub ? $sub->lifecycle_status : 'N/A',
                    $sub && $sub->billing_end ? $sub->billing_end->format('Y-m-d H:i:s') : 'N/A',
                    $user->mrus_count,
                    $user->consumer_accounts_count,
                    $user->bill_records_count,
                    number_format($user->wallet?->balance ?? 0, 2, '.', ''),
                    $user->storage_limit_mb ?? 100,
                    $user->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Perform bulk operations on selected users.
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'bulk_action' => 'required|string|in:activate,suspend,change_plan_tier,delete',
            'plan_tier' => 'nullable|string|in:free,starter,pro,enterprise',
        ]);

        $currentAdminId = auth()->id();
        // Remove current admin from target ids to prevent accidental self-lockout or self-deletion
        $targetIds = array_values(array_filter($request->user_ids, fn($id) => (int)$id !== $currentAdminId));

        if (empty($targetIds)) {
            return back()->with('error', 'Cannot perform bulk action on your own administrator account.');
        }

        $count = count($targetIds);

        switch ($request->bulk_action) {
            case 'activate':
                User::whereIn('id', $targetIds)->update(['status' => 'active']);
                return back()->with('success', "Successfully activated {$count} user account(s).");

            case 'suspend':
                User::whereIn('id', $targetIds)->update(['status' => 'suspended']);
                return back()->with('success', "Successfully suspended {$count} user account(s).");

            case 'change_plan_tier':
                if (!$request->plan_tier) {
                    return back()->with('error', 'Please select a plan tier for the bulk change.');
                }
                User::whereIn('id', $targetIds)->update(['plan_tier' => $request->plan_tier]);
                return back()->with('success', "Successfully updated plan tier to '{$request->plan_tier}' for {$count} user(s).");

            case 'delete':
                foreach ($targetIds as $id) {
                    $u = User::find($id);
                    if ($u && !$u->hasRole('admin')) {
                        // Purge private storage directory
                        $userStoragePath = "users/{$u->id}";
                        if (Storage::disk('private')->exists($userStoragePath)) {
                            Storage::disk('private')->deleteDirectory($userStoragePath);
                        }
                        $u->delete();
                    }
                }
                return back()->with('success', "Successfully purged {$count} selected user account(s) and their storage files.");
        }

        return back()->with('error', 'Invalid bulk action requested.');
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

        $transitions = PlanUpgradeLog::where('user_id', $user->id)
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

        $availablePlans = Plan::with('activeDurations')->where('is_active', true)->get();

        return view('admin.users.show', compact(
            'user',
            'mrus',
            'billStats',
            'recentTransactions',
            'subscriptionHistory',
            'transitions',
            'storageMetrics',
            'availablePlans'
        ));
    }

    /**
     * Manually grant a plan or extend validity for a user (Offline / VIP Override).
     */
    public function grantPlan(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'grant_mode' => 'required|string|in:new_plan,extend_validity',
            'plan_id' => 'required_if:grant_mode,new_plan|nullable|exists:plans,id',
            'duration_id' => 'required_if:grant_mode,new_plan|nullable|exists:plan_durations,id',
            'days_to_add' => 'required_if:grant_mode,extend_validity|nullable|integer|min:1|max:365',
            'admin_note' => 'nullable|string|max:500',
        ]);

        if ($request->grant_mode === 'new_plan') {
            $plan = Plan::findOrFail($request->plan_id);
            $duration = PlanDuration::where('id', $request->duration_id)->where('plan_id', $plan->id)->firstOrFail();

            // Deactivate any existing active subscriptions
            AgentSubscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->update(['status' => 'superseded', 'lifecycle_status' => 'superseded']);

            $months = $duration->duration_unit === 'month' ? $duration->duration_value : max(1, (int) ceil($duration->duration_value / 30));

            $subscription = AgentSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'duration_unit' => $duration->duration_unit,
                'duration_value' => $duration->duration_value,
                'duration_months' => $months,
                'base_price_paid' => 0.00, // Manually granted by admin
                'included_mrus_locked' => $plan->included_mrus,
                'included_consumers_locked' => $plan->included_consumers,
                'extra_mru_rate_locked' => $plan->extra_mru_rate,
                'extra_consumer_rate_locked' => $plan->extra_consumer_rate,
                'billing_start' => now(),
                'billing_end' => $duration->calculateBillingEnd(now()),
                'status' => 'active',
                'lifecycle_status' => 'active',
                'grace_period_days' => $plan->grace_period_days ?? 3,
                'auto_renewal_enabled' => false,
                'last_state_change_at' => now(),
            ]);

            $user->plan_tier = strtolower($plan->name);
            $user->save();

            return back()->with('success', "Successfully granted plan '{$plan->name}' ({$duration->formatted_duration}) to {$user->name}. Active until {$subscription->billing_end->format('M d, Y')}.");
        }

        if ($request->grant_mode === 'extend_validity') {
            $days = (int) $request->days_to_add;
            $activeSub = $user->activeSubscription;

            if ($activeSub) {
                $baseStart = ($activeSub->billing_end && $activeSub->billing_end > now()) ? $activeSub->billing_end : now();
                $activeSub->billing_end = $baseStart->copy()->addDays($days);
                $activeSub->status = 'active';
                $activeSub->lifecycle_status = 'active';
                $activeSub->suspended_at = null;
                $activeSub->save();

                return back()->with('success', "Extended validity by {$days} day(s) for {$user->name}. New expiration: {$activeSub->billing_end->format('M d, Y')}.");
            }

            // No active subscription: fallback to extending default free/custom access
            return back()->with('error', 'User has no active subscription to extend. Please grant a new plan first.');
        }

        return back()->with('error', 'Invalid grant mode.');
    }

    /**
     * Override locked MRU & Consumer allowances for an active subscription.
     */
    public function overrideQuotas(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'included_mrus_locked' => 'required|integer|min:1|max:1000',
            'included_consumers_locked' => 'required|integer|min:10|max:1000000',
            'extra_mru_rate_locked' => 'nullable|numeric|min:0',
            'extra_consumer_rate_locked' => 'nullable|numeric|min:0',
        ]);

        $sub = $user->activeSubscription;
        if (!$sub) {
            return back()->with('error', 'User has no active subscription to override quotas for. Please grant a plan first.');
        }

        $sub->update([
            'included_mrus_locked' => $request->included_mrus_locked,
            'included_consumers_locked' => $request->included_consumers_locked,
            'extra_mru_rate_locked' => $request->extra_mru_rate_locked ?? $sub->extra_mru_rate_locked,
            'extra_consumer_rate_locked' => $request->extra_consumer_rate_locked ?? $sub->extra_consumer_rate_locked,
        ]);

        return back()->with('success', "Quotas updated for {$user->name}: {$request->included_mrus_locked} MRUs & " . number_format($request->included_consumers_locked) . " Consumers included.");
    }

    /**
     * Send direct targeted notification and optional email to user.
     */
    public function sendDirectNotification(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'priority' => 'required|string|in:routine,critical,urgent',
            'send_email' => 'nullable|boolean',
        ]);

        $notification = Notification::create([
            'user_id' => $user->id,
            'event_type' => 'admin.direct_message',
            'priority' => $request->priority,
            'title' => $request->title,
            'body' => $request->body,
            'data' => [
                'sent_by_admin_id' => auth()->id(),
                'sent_by_admin_name' => auth()->user()->name,
            ],
            'read_at' => null,
            'created_at' => now(),
        ]);

        // Record in-app delivery
        NotificationDelivery::create([
            'notification_id' => $notification->id,
            'channel' => 'in_app',
            'status' => 'delivered',
            'recipient' => (string) $user->id,
            'delivered_at' => now(),
            'created_at' => now(),
        ]);

        if ($request->boolean('send_email') && $user->email) {
            try {
                Mail::raw("Hello {$user->name},\n\n{$request->body}\n\nBest regards,\nNBPDCL Administration Hub", function ($msg) use ($user, $request) {
                    $msg->to($user->email)
                        ->subject($request->title);
                });

                NotificationDelivery::create([
                    'notification_id' => $notification->id,
                    'channel' => 'email',
                    'status' => 'delivered',
                    'recipient' => $user->email,
                    'delivered_at' => now(),
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Log failed email delivery
                NotificationDelivery::create([
                    'notification_id' => $notification->id,
                    'channel' => 'email',
                    'status' => 'failed',
                    'recipient' => $user->email,
                    'error_message' => $e->getMessage(),
                    'created_at' => now(),
                ]);
            }
        }

        return back()->with('success', "Direct notification successfully dispatched to '{$user->name}'.");
    }

    /**
     * Safe user purge & disk cleanup console.
     */
    public function purgeUser(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'confirm_text' => 'required|string|in:DELETE',
        ]);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot purge your own administrator account.');
        }

        if ($user->hasRole('admin')) {
            return back()->with('error', 'You cannot purge another administrator account.');
        }

        $userName = $user->name;

        DB::transaction(function () use ($user) {
            // 1. Purge private PDF files from storage disk
            $userStoragePath = "users/{$user->id}";
            if (Storage::disk('private')->exists($userStoragePath)) {
                Storage::disk('private')->deleteDirectory($userStoragePath);
            }

            // 2. Refer & Earn: Cancel any pending referral payouts for this deleted referrer
            try {
                app(\App\Services\Referral\ReferralService::class)->handleReferrerAccountDeleted($user->id);
            } catch (\Throwable $e) {
                Log::error("[UserPurge] Error cancelling referral payouts for user #{$user->id}: " . $e->getMessage());
            }

            // 3. Cascade delete records
            $user->delete();
        });

        return redirect()->route('admin.users.index')
            ->with('success', "User '{$userName}' and all associated storage files have been permanently purged.");
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
