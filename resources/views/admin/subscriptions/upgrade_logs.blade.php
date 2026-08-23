<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <a href="{{ route('admin.subscriptions.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1 mb-2">
                    ← Back to Subscriptions
                </a>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>⚖️</span> Plan Upgrade & Downgrade Proration Audit Log
                </h1>
                <p class="text-sm text-slate-400 mt-1">Complete audit log of mid-cycle plan switches, proration breakdowns, wallet charges, and credits.</p>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 backdrop-blur-md">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Mid-Cycle Changes</div>
                <div class="text-2xl font-black text-white mt-2">{{ number_format($logs->total()) }}</div>
            </div>

            <div class="bg-slate-900/60 border border-emerald-500/20 rounded-2xl p-4 backdrop-blur-md">
                <div class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Upgrade Revenue (Debits)</div>
                <div class="text-2xl font-black text-emerald-400 mt-2">₹{{ number_format($totalUpgradeRevenue, 2) }}</div>
            </div>

            <div class="bg-slate-900/60 border border-indigo-500/20 rounded-2xl p-4 backdrop-blur-md">
                <div class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Downgrade Credits (Wallet)</div>
                <div class="text-2xl font-black text-indigo-400 mt-2">₹{{ number_format($totalDowngradeCredits, 2) }}</div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="p-5 border-b border-slate-800/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">Proration Math Breakdown</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Calculated based on days remaining in the billing cycle.</p>
                </div>

                <form method="GET" action="{{ route('admin.subscriptions.upgrade_logs') }}" class="flex items-center gap-2.5">
                    <select name="action_type" onchange="this.form.submit()" class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-1.5 px-3 focus:ring-indigo-500">
                        <option value="">All Actions</option>
                        <option value="upgrade" {{ request('action_type') === 'upgrade' ? 'selected' : '' }}>Upgrades Only</option>
                        <option value="downgrade" {{ request('action_type') === 'downgrade' ? 'selected' : '' }}>Downgrades Only</option>
                    </select>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-950/80 border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                            <th class="py-3.5 px-4 font-semibold">ID</th>
                            <th class="py-3.5 px-4 font-semibold">Agent</th>
                            <th class="py-3.5 px-4 font-semibold">Action</th>
                            <th class="py-3.5 px-4 font-semibold">Plan Transition</th>
                            <th class="py-3.5 px-4 font-semibold">Proration Breakdown</th>
                            <th class="py-3.5 px-4 font-semibold">Net Charged / Credited</th>
                            <th class="py-3.5 px-4 font-semibold">Wallet Tx</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        @forelse($logs as $l)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-3.5 px-4 font-mono text-slate-500">#{{ $l->id }}</td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white">{{ $l->user?->name ?? 'User #' . $l->user_id }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono">{{ $l->user?->email }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($l->action_type === 'upgrade')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                            ⬆️ UPGRADE
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                            ⬇️ DOWNGRADE
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="text-slate-400">{{ $l->fromPlan?->name ?? 'Previous' }}</span>
                                    <span class="text-indigo-400 font-bold mx-1">→</span>
                                    <span class="text-white font-bold">{{ $l->toPlan?->name ?? 'Target' }}</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-[11px]">
                                        Credit: <span class="text-slate-400">₹{{ number_format($l->old_plan_credit, 2) }}</span> | Cost: <span class="text-slate-400">₹{{ number_format($l->new_plan_cost, 2) }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-500">
                                        {{ $l->days_remaining_at_upgrade }} / {{ $l->total_days_in_cycle }} days remaining
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($l->action_type === 'upgrade')
                                        <span class="font-bold text-emerald-400">₹{{ number_format($l->amount_charged, 2) }} (Paid)</span>
                                    @else
                                        <span class="font-bold text-indigo-400">₹{{ number_format(abs($l->amount_charged), 2) }} (Credited)</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-400">
                                    {{ $l->wallet_transaction_id ? '#' . $l->wallet_transaction_id : '—' }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-[11px] text-slate-400">
                                    {{ $l->created_at?->format('d M Y, h:i A') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-500 italic">
                                    No plan upgrades or downgrades logged yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
