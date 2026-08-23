<x-admin-layout>
    <x-slot name="header">
        System Overview & Analytics
    </x-slot>

    <div class="space-y-8">
        <!-- KPI Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Agents/Users -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-lg">
                <span class="text-xs uppercase font-bold text-slate-500 tracking-wider">Registered Agents</span>
                <div class="text-3xl font-extrabold text-white mt-2">{{ number_format($totalUsers) }}</div>
                <div class="text-xs text-emerald-400 mt-1 font-semibold">{{ $activeUsers }} Active</div>
            </div>

            <!-- Total System Bills -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-lg">
                <span class="text-xs uppercase font-bold text-slate-500 tracking-wider">All-Time Bills Processed</span>
                <div class="text-3xl font-extrabold text-indigo-400 mt-2">{{ number_format($totalBills) }}</div>
                <div class="text-xs text-slate-500 mt-1">Across all agents</div>
            </div>

            <!-- Total Consumers -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-lg">
                <span class="text-xs uppercase font-bold text-slate-500 tracking-wider">Consumer Accounts</span>
                <div class="text-3xl font-extrabold text-cyan-400 mt-2">{{ number_format($totalConsumers) }}</div>
                <div class="text-xs text-slate-500 mt-1">Unique CA records</div>
            </div>

            <!-- Total MRUs -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-lg">
                <span class="text-xs uppercase font-bold text-slate-500 tracking-wider">Discovered MRUs</span>
                <div class="text-3xl font-extrabold text-amber-400 mt-2">{{ number_format($totalMrus) }}</div>
                <div class="text-xs text-slate-500 mt-1">Village & Area Units</div>
            </div>
        </div>

        <!-- Global Shortcut Management Banner -->
        <div class="bg-gradient-to-r from-indigo-950/80 via-slate-950 to-slate-950 p-6 rounded-3xl border border-indigo-500/30 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 flex items-center justify-center text-2xl shrink-0">
                    ⌨️
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">System-Wide Keyboard Shortcut Defaults</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Configure and enforce the global default keybindings for Working Reading, Auto-Fill, Submit & Card navigation across all operators.</p>
                </div>
            </div>
            <a href="{{ route('admin.shortcuts.index') }}" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition shrink-0 flex items-center gap-2">
                <span>⚙️ Configure Shortcuts</span>
                <span>→</span>
            </a>
        </div>

        <!-- Recent Activity & Agents Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Recent Agents -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-lg space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Recent Agents</h3>
                    <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-indigo-400 hover:underline">View All →</a>
                </div>

                <div class="divide-y divide-slate-800/80">
                    @forelse($recentUsers as $user)
                        <div class="py-3.5 flex items-center justify-between">
                            <div>
                                <div class="text-sm font-bold text-white">{{ $user->name }}</div>
                                <div class="text-xs text-slate-500">{{ $user->email }}</div>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $user->status === 'active' ? 'bg-emerald-950 text-emerald-400 border border-emerald-500/20' : 'bg-rose-950 text-rose-400 border border-rose-500/20' }}">
                                    {{ ucfirst($user->status) }}
                                </span>
                                <div class="text-[11px] text-slate-500 mt-1">{{ $user->bill_records_count }} bills</div>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-500 text-xs">No users registered yet.</div>
                    @endforelse
                </div>
            </div>

            <!-- Recent Bills Downloaded Across Platform -->
            <div class="lg:col-span-2 bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-lg space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Latest Downloaded Bills (Platform-Wide)</h3>
                    <a href="{{ route('admin.bills.index') }}" class="text-xs font-semibold text-indigo-400 hover:underline">Inspect All Bills →</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-400">
                        <thead class="bg-slate-900 border-b border-slate-800 text-[11px] uppercase font-bold text-slate-500">
                            <tr>
                                <th class="py-2.5 px-3">CA Number</th>
                                <th class="py-2.5 px-3">Consumer Name</th>
                                <th class="py-2.5 px-3">Agent</th>
                                <th class="py-2.5 px-3">MRU</th>
                                <th class="py-2.5 px-3 text-right">Amount</th>
                                <th class="py-2.5 px-3 text-center">Period</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-medium">
                            @forelse($recentBills as $bill)
                                <tr class="hover:bg-slate-900/40">
                                    <td class="py-3 px-3 font-mono font-bold text-indigo-300">{{ $bill->ca_number }}</td>
                                    <td class="py-3 px-3 text-slate-200 truncate max-w-[150px]">{{ $bill->consumer_name ?: '—' }}</td>
                                    <td class="py-3 px-3 text-slate-400">{{ $bill->user ? $bill->user->name : 'Unknown' }}</td>
                                    <td class="py-3 px-3 font-mono text-[11px]">{{ $bill->mru ? $bill->mru->code : 'UNKNOWN' }}</td>
                                    <td class="py-3 px-3 text-right font-bold text-white">₹{{ number_format($bill->total_amount, 2) }}</td>
                                    <td class="py-3 px-3 text-center text-slate-500">{{ $bill->billing_month }}/{{ $bill->billing_year }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-500">No bills recorded in database yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
