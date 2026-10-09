<!-- ========================================== -->
<!-- TAB 5: ALL ISSUED API KEYS LEDGER          -->
<!-- ========================================== -->
<div x-show="activeTab === 'keys'" class="space-y-6" x-cloak>
    <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <span>📋</span> System-Wide API Keys Ledger
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Audit every issued key across all users with 1-click emergency revocation.</p>
            </div>

            <!-- Search Input -->
            <form method="GET" action="{{ route('admin.api_hub.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="keys">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by name, prefix, user..."
                    class="bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-indigo-500">
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition">
                    Search
                </button>
            </form>
        </div>

        <div class="overflow-x-auto pt-2">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="text-[10px] uppercase font-bold text-slate-500 bg-slate-900/60 rounded-xl">
                    <tr>
                        <th class="p-3 rounded-l-xl">Owner / User</th>
                        <th class="p-3">Key Label</th>
                        <th class="p-3">Prefix</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Created</th>
                        <th class="p-3">Last Active</th>
                        <th class="p-3">Last IP</th>
                        <th class="p-3 text-right rounded-r-xl">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-mono">
                    @forelse ($issuedKeys as $key)
                        @php
                            $isExpired = $key->expires_at && $key->expires_at->isPast();
                        @endphp
                        <tr class="hover:bg-slate-900/40 transition font-sans">
                            <td class="p-3">
                                <div class="font-bold text-white">{{ $key->user ? $key->user->name : 'Deleted User' }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">{{ $key->user ? $key->user->email : '-' }}</div>
                            </td>
                            <td class="p-3 font-semibold text-cyan-300">{{ $key->name }}</td>
                            <td class="p-3 font-mono text-slate-400">{{ $key->key_prefix }}••••</td>
                            <td class="p-3">
                                @if ($isExpired)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/15 text-rose-400 border border-rose-500/30">Expired</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">Active</span>
                                @endif
                            </td>
                            <td class="p-3 text-slate-400 text-[11px]">{{ $key->created_at?->format('M d, Y') }}</td>
                            <td class="p-3 text-slate-400 text-[11px]">
                                {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Never used' }}
                            </td>
                            <td class="p-3 font-mono text-slate-500 text-[11px]">{{ $key->last_ip ?: '—' }}</td>
                            <td class="p-3 text-right">
                                <button type="button" @click="revokingKey = {{ Js::from(['id' => $key->id, 'name' => $key->name, 'user' => $key->user ? $key->user->name : 'User']) }}"
                                    class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-rose-950/40 hover:bg-rose-900/60 text-rose-400 hover:text-rose-300 border border-rose-500/30 transition">
                                    Revoke
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-slate-500 font-sans">
                                No API keys found. When users generate keys, they will appear in this ledger.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($issuedKeys->hasPages())
            <div class="pt-4 border-t border-slate-800">
                {{ $issuedKeys->links() }}
            </div>
        @endif
    </div>
</div>
