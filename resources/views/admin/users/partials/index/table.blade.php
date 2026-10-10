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
