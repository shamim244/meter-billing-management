<x-admin-layout>
    <x-slot name="header">
        Billing Agents & User Management
    </x-slot>

    <div class="space-y-6" x-data="{
        selectedUsers: [],
        selectAll: false,
        toggleAll() {
            if (this.selectAll) {
                this.selectedUsers = Array.from(document.querySelectorAll('.user-checkbox')).map(cb => parseInt(cb.value));
            } else {
                this.selectedUsers = [];
            }
        },
        bulkAction: '',
        bulkPlanTier: 'pro'
    }">
        <!-- Top Stats Banner -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Accounts</div>
                    <div class="text-xl font-black text-white font-mono mt-0.5">{{ number_format($stats['total_users']) }}</div>
                </div>
                <span class="p-2 bg-indigo-500/10 rounded-xl text-indigo-400 text-base">👥</span>
            </div>

            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Operators</div>
                    <div class="text-xl font-black text-emerald-400 font-mono mt-0.5">{{ number_format($stats['active_users']) }}</div>
                </div>
                <span class="p-2 bg-emerald-500/10 rounded-xl text-emerald-400 text-base">✓</span>
            </div>

            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Subscribed Agents</div>
                    <div class="text-xl font-black text-cyan-400 font-mono mt-0.5">{{ number_format($stats['subscribed_users']) }}</div>
                </div>
                <span class="p-2 bg-cyan-500/10 rounded-xl text-cyan-400 text-base">⚡</span>
            </div>

            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Suspended</div>
                    <div class="text-xl font-black text-rose-400 font-mono mt-0.5">{{ number_format($stats['suspended_users']) }}</div>
                </div>
                <span class="p-2 bg-rose-500/10 rounded-xl text-rose-400 text-base">🚫</span>
            </div>
        </div>

        <!-- Top Toolbar & Filters -->
        <div class="bg-slate-950 p-4 rounded-3xl border border-slate-800 shadow-lg flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-center gap-2 flex-1">
                <div class="flex-1 min-w-[180px]">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, email, or phone..." class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl px-3.5 py-2 text-white placeholder-slate-500 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <select name="role" class="text-xs bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-white focus:ring-indigo-500">
                    <option value="all" {{ $roleFilter === 'all' ? 'selected' : '' }}>All Roles</option>
                    <option value="admin" {{ $roleFilter === 'admin' ? 'selected' : '' }}>Administrators</option>
                    <option value="user" {{ $roleFilter === 'user' ? 'selected' : '' }}>Billing Operators</option>
                </select>

                <select name="status" class="text-xs bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-white focus:ring-indigo-500">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="suspended" {{ $statusFilter === 'suspended' ? 'selected' : '' }}>Suspended Only</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition shrink-0">
                    Filter
                </button>

                @if(!empty($search) || $roleFilter !== 'all' || $statusFilter !== 'all')
                    <a href="{{ route('admin.users.index') }}" class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-slate-400 rounded-xl text-xs font-medium transition shrink-0">
                        Clear
                    </a>
                @endif
            </form>

            <div class="flex items-center gap-2 shrink-0 flex-wrap">
                <!-- Export to CSV Button -->
                <a href="{{ route('admin.users.export', request()->query()) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 hover:border-slate-600 rounded-xl text-xs font-bold transition">
                    <span>📥</span> Export CSV
                </a>

                <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/20 transition text-center shrink-0">
                    <span>+</span> Add New User
                </a>
            </div>
        </div>

        <!-- Bulk Action Floating Bar -->
        <div x-show="selectedUsers.length > 0" x-cloak class="bg-indigo-950/90 border border-indigo-500/40 p-4 rounded-2xl shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 animate-in fade-in slide-in-from-bottom-2 duration-150">
            <div class="flex items-center gap-2 text-xs font-bold text-indigo-200">
                <span class="px-2 py-0.5 bg-indigo-600 text-white rounded-md font-mono" x-text="selectedUsers.length"></span>
                <span>user(s) selected</span>
            </div>

            <form method="POST" action="{{ route('admin.users.bulk-action') }}" class="flex flex-wrap items-center gap-2">
                @csrf
                <template x-for="id in selectedUsers" :key="id">
                    <input type="hidden" name="user_ids[]" :value="id">
                </template>

                <select name="bulk_action" x-model="bulkAction" required class="text-xs bg-slate-950 border border-indigo-400/40 rounded-xl px-3 py-1.5 text-white">
                    <option value="">-- Choose Bulk Action --</option>
                    <option value="activate">✓ Activate Accounts</option>
                    <option value="suspend">🚫 Suspend Accounts</option>
                    <option value="change_plan_tier">⚡ Change Plan Tier</option>
                    <option value="delete">🗑️ Purge Accounts & Files</option>
                </select>

                <select x-show="bulkAction === 'change_plan_tier'" name="plan_tier" x-model="bulkPlanTier" class="text-xs bg-slate-950 border border-indigo-400/40 rounded-xl px-3 py-1.5 text-white">
                    <option value="free">Free Tier</option>
                    <option value="starter">Starter</option>
                    <option value="pro">Pro Operator</option>
                    <option value="enterprise">Enterprise Hub</option>
                </select>

                <button type="submit" @click="if (bulkAction === 'delete' && !confirm('Permanently purge selected users and all their storage PDFs?')) { $event.preventDefault(); }" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow transition">
                    Apply Action
                </button>
            </form>
        </div>

        <!-- Users Table -->
        <div class="bg-slate-950 rounded-3xl border border-slate-800 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/90 border-b border-slate-800 text-[11px] uppercase font-bold text-slate-400 tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4 text-center w-10">
                                <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="py-3.5 px-4">User / Contact</th>
                            <th class="py-3.5 px-4">Role</th>
                            <th class="py-3.5 px-4 text-center">Subscription Plan</th>
                            <th class="py-3.5 px-4 text-center">MRUs & Base</th>
                            <th class="py-3.5 px-4 text-center">Wallet Balance</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-center">Joined</th>
                            <th class="py-3.5 px-5 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70 font-medium">
                        @forelse($users as $user)
                            <tr class="hover:bg-slate-900/40 transition">
                                <!-- Checkbox -->
                                <td class="py-3.5 px-4 text-center">
                                    <input type="checkbox" value="{{ $user->id }}" x-model="selectedUsers" class="user-checkbox rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                                </td>

                                <!-- User / Contact -->
                                <td class="py-3.5 px-4">
                                    <a href="{{ route('admin.users.show', $user) }}" class="font-bold text-white hover:text-indigo-400 text-sm flex items-center gap-1.5 transition">
                                        <span>{{ $user->name }}</span>
                                        @if($user->email_verified_at)
                                            <span class="text-[10px] text-emerald-400" title="Email verified">✓</span>
                                        @endif
                                    </a>
                                    <div class="text-[11px] text-slate-400">{{ $user->email }}</div>
                                    @if($user->phone)
                                        <div class="text-[10px] text-slate-500 font-mono mt-0.5">📞 {{ $user->phone }}</div>
                                    @endif
                                </td>

                                <!-- Role -->
                                <td class="py-3.5 px-4">
                                    @foreach($user->roles as $role)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $role->name === 'admin' ? 'bg-purple-950 text-purple-300 border border-purple-500/40' : 'bg-slate-800 text-slate-300' }}">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                </td>

                                <!-- Subscription Plan -->
                                <td class="py-3.5 px-4 text-center">
                                    @if($user->activeSubscription)
                                        <div class="font-bold text-white text-xs">
                                            {{ $user->activeSubscription->plan->name ?? 'Subscribed' }}
                                        </div>
                                        <div class="text-[10px] text-emerald-400 font-bold uppercase">
                                            {{ $user->activeSubscription->lifecycle_status }}
                                        </div>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-900 text-slate-400 border border-slate-800">
                                            {{ $user->plan_tier ?? 'free' }}
                                        </span>
                                    @endif
                                </td>

                                <!-- MRUs & Consumers -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="font-mono font-bold text-cyan-400">
                                        {{ $user->mrus_count }} <span class="text-[10px] font-sans font-medium text-slate-500">MRUs</span>
                                    </div>
                                    <div class="text-[10px] font-mono text-slate-400">
                                        {{ number_format($user->consumer_accounts_count) }} CAs
                                    </div>
                                </td>

                                <!-- Wallet Balance -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="font-mono font-bold text-emerald-400">
                                        ₹{{ number_format($user->wallet?->balance ?? 0, 2) }}
                                    </div>
                                    @if($user->isWalletFrozen())
                                        <span class="text-[9px] font-bold uppercase text-rose-400">Frozen</span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $user->status === 'active' ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/30' : 'bg-rose-950 text-rose-300 border border-rose-500/30' }}">
                                        {{ $user->status }}
                                    </span>
                                </td>

                                <!-- Joined -->
                                <td class="py-3.5 px-4 text-center text-[11px] text-slate-500">
                                    {{ $user->created_at->format('M d, Y') }}
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-5 text-center">
                                    <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                        <!-- View Dossier -->
                                        <a href="{{ route('admin.users.show', $user) }}" class="px-2 py-1 rounded-lg text-xs font-bold bg-slate-900 hover:bg-slate-800 text-indigo-300 border border-indigo-500/30 transition" title="View 360° User Dossier">
                                            👁️ View
                                        </a>

                                        <!-- Edit -->
                                        <a href="{{ route('admin.users.edit', $user) }}" class="px-2 py-1 rounded-lg text-xs font-bold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 transition" title="Edit Profile & Password">
                                            ✏️ Edit
                                        </a>

                                        <!-- Impersonate -->
                                        @if($user->id !== auth()->id() && !$user->hasRole('admin'))
                                            <form method="POST" action="{{ route('admin.users.impersonate', $user) }}" class="inline">
                                                @csrf
                                                <button type="submit" onclick="return confirm('Log in as {{ $user->name }}?');" class="px-2 py-1 rounded-lg text-xs font-bold bg-amber-950/70 hover:bg-amber-900/90 text-amber-300 border border-amber-500/30 transition" title="Login as this operator">
                                                    🎭 Login
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-slate-500">
                                    No users found matching query.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-slate-800 bg-slate-900/60">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
