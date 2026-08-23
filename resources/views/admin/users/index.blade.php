<x-admin-layout>
    <x-slot name="header">
        Billing Agents & User Management
    </x-slot>

    <div class="space-y-6">
        <!-- Top Toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center gap-2 max-w-md w-full">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, email, or phone..." class="w-full text-xs sm:text-sm bg-slate-950 border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:ring-indigo-500 focus:border-indigo-500">
                <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-semibold transition shrink-0">
                    Search
                </button>
            </form>

            <a href="{{ route('admin.users.create') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white rounded-xl text-xs sm:text-sm font-bold shadow-lg shadow-indigo-600/20 transition text-center">
                <span>+</span> Add New User
            </a>
        </div>

        <!-- Users Table -->
        <div class="bg-slate-950 rounded-3xl border border-slate-800 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-900 border-b border-slate-800 text-xs uppercase font-bold text-slate-500 tracking-wider">
                        <tr>
                            <th class="py-4 px-6">User</th>
                            <th class="py-4 px-6">Role</th>
                            <th class="py-4 px-6 text-center">Storage Quota</th>
                            <th class="py-4 px-6 text-center">Consumers</th>
                            <th class="py-4 px-6 text-center">Bills Processed</th>
                            <th class="py-4 px-6 text-center">Status</th>
                            <th class="py-4 px-6 text-center">Joined</th>
                            <th class="py-4 px-6 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80 font-medium">
                        @forelse($users as $user)
                            <tr class="hover:bg-slate-900/40 transition">
                                <td class="py-4 px-6">
                                    <div class="font-bold text-white text-base">{{ $user->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $user->email }}</div>
                                    @if($user->phone)
                                        <div class="text-xs text-slate-500 font-mono mt-0.5">📞 {{ $user->phone }}</div>
                                    @endif
                                </td>
                                <td class="py-4 px-6">
                                    @foreach($user->roles as $role)
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase {{ $role->name === 'admin' ? 'bg-purple-950 text-purple-300 border border-purple-500/30' : 'bg-slate-800 text-slate-300' }}">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="py-4 px-6 text-center" x-data="{ editingQuota: false }">
                                    <div x-show="!editingQuota" class="space-y-1">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-brand-950 text-brand-300 border border-brand-800/80">
                                                {{ $user->plan_tier ?? 'free' }}
                                            </span>
                                            <span class="font-mono text-xs font-bold text-slate-200">
                                                {{ $user->storage_limit_mb ?? 100 }} MB
                                            </span>
                                        </div>
                                        <div class="text-[11px] font-mono text-slate-500">
                                            {{ round($user->getStorageUsedBytes() / (1024 * 1024), 1) }} MB used ({{ $user->getStorageUsagePercent() }}%)
                                        </div>
                                        <button type="button" @click="editingQuota = true" class="text-[10px] text-brand-400 hover:text-brand-300 underline font-semibold">
                                            Edit Quota
                                        </button>
                                    </div>

                                    <form x-show="editingQuota" x-cloak method="POST" action="{{ route('admin.users.update-quota', $user) }}" class="space-y-2 p-2 bg-slate-900 rounded-xl border border-slate-800">
                                        @csrf
                                        @method('PATCH')
                                        <div class="flex items-center gap-1">
                                            <select name="plan_tier" class="text-[11px] bg-slate-950 border border-slate-700 rounded-lg text-white py-1 px-1.5 focus:ring-brand-500">
                                                <option value="free" {{ ($user->plan_tier ?? 'free') === 'free' ? 'selected' : '' }}>Free</option>
                                                <option value="starter" {{ ($user->plan_tier ?? '') === 'starter' ? 'selected' : '' }}>Starter</option>
                                                <option value="pro" {{ ($user->plan_tier ?? '') === 'pro' ? 'selected' : '' }}>Pro</option>
                                                <option value="enterprise" {{ ($user->plan_tier ?? '') === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                                            </select>
                                            <input type="number" name="storage_limit_mb" value="{{ $user->storage_limit_mb ?? 100 }}" class="w-16 text-[11px] bg-slate-950 border border-slate-700 rounded-lg text-white py-1 px-1.5 font-mono" placeholder="MB">
                                        </div>
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" @click="editingQuota = false" class="px-2 py-0.5 text-[10px] rounded bg-slate-800 text-slate-400">Cancel</button>
                                            <button type="submit" class="px-2 py-0.5 text-[10px] rounded bg-brand-600 text-white font-bold">Save</button>
                                        </div>
                                    </form>
                                </td>
                                <td class="py-4 px-6 text-center font-mono font-bold text-cyan-400">
                                    {{ number_format($user->consumer_accounts_count) }}
                                </td>
                                <td class="py-4 px-6 text-center font-mono font-bold text-indigo-400">
                                    {{ number_format($user->bill_records_count) }}
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase {{ $user->status === 'active' ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/30' : 'bg-rose-950 text-rose-300 border border-rose-500/30' }}">
                                        {{ $user->status }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center text-xs text-slate-500">
                                    {{ $user->created_at->format('M d, Y') }}
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.wallets.show', $user->id) }}" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-950/60 hover:bg-indigo-900/80 text-indigo-300 border border-indigo-500/30 transition flex items-center gap-1">
                                            <span>👛</span> Wallet
                                        </a>
                                        @if($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="px-3 py-1 rounded-lg text-xs font-bold transition {{ $user->status === 'active' ? 'bg-rose-950 hover:bg-rose-900 text-rose-300 border border-rose-500/30' : 'bg-emerald-950 hover:bg-emerald-900 text-emerald-300 border border-emerald-500/30' }}">
                                                    {{ $user->status === 'active' ? 'Suspend' : 'Activate' }}
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-600 italic">Self</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-500">
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
