<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <a href="{{ route('admin.plans.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1 mb-2">
                    ← Back to Plans
                </a>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>⚡</span> Overage Charges & Pay-Gate Audit
                </h1>
                <p class="text-sm text-slate-400 mt-1">Complete financial audit log of MRU and Consumer overage fees debited via Wallet.</p>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Overage Charged</span>
                    <span class="p-2 bg-emerald-500/10 rounded-xl text-emerald-400 text-lg">💰</span>
                </div>
                <div class="text-2xl font-black text-emerald-400 mt-2">₹{{ number_format($totalAmount, 2) }}</div>
                <div class="text-[11px] text-slate-500 mt-1">Cumulative overage revenue</div>
            </div>

            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">MRU Creation Fees</span>
                    <span class="p-2 bg-indigo-500/10 rounded-xl text-indigo-400 text-lg">📁</span>
                </div>
                <div class="text-2xl font-black text-white mt-2">₹{{ number_format($mruCreationTotal, 2) }}</div>
                <div class="text-[11px] text-slate-500 mt-1">Pay-gate creation charges</div>
            </div>

            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">MRU Renewal Overages</span>
                    <span class="p-2 bg-purple-500/10 rounded-xl text-purple-400 text-lg">🔄</span>
                </div>
                <div class="text-2xl font-black text-white mt-2">₹{{ number_format($mruRenewalTotal, 2) }}</div>
                <div class="text-[11px] text-slate-500 mt-1">Recurring extra MRUs</div>
            </div>

            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Consumer Cycle Overages</span>
                    <span class="p-2 bg-amber-500/10 rounded-xl text-amber-400 text-lg">👥</span>
                </div>
                <div class="text-2xl font-black text-amber-400 mt-2">₹{{ number_format($consumerTotal, 2) }}</div>
                <div class="text-[11px] text-slate-500 mt-1">Per-cycle extra CA charges</div>
            </div>
        </div>

        <!-- Filters & History Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="p-5 border-b border-slate-800/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">Overage Ledger</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Tied directly to immutable Wallet Transactions.</p>
                </div>

                <form method="GET" action="{{ route('admin.plans.overage_charges') }}" class="flex items-center gap-2.5">
                    <select name="charge_type" onchange="this.form.submit()" class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-1.5 px-3 focus:ring-indigo-500">
                        <option value="">All Charge Types</option>
                        <option value="mru_creation" {{ request('charge_type') === 'mru_creation' ? 'selected' : '' }}>MRU Creation</option>
                        <option value="mru_renewal" {{ request('charge_type') === 'mru_renewal' ? 'selected' : '' }}>MRU Renewal</option>
                        <option value="mru_unlock" {{ request('charge_type') === 'mru_unlock' ? 'selected' : '' }}>MRU Unlock</option>
                        <option value="consumer_cycle" {{ request('charge_type') === 'consumer_cycle' ? 'selected' : '' }}>Consumer Cycle</option>
                    </select>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-950/80 border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                            <th class="py-3.5 px-4 font-semibold">ID</th>
                            <th class="py-3.5 px-4 font-semibold">Agent</th>
                            <th class="py-3.5 px-4 font-semibold">Type</th>
                            <th class="py-3.5 px-4 font-semibold">Reference</th>
                            <th class="py-3.5 px-4 font-semibold">Amount Charged</th>
                            <th class="py-3.5 px-4 font-semibold">Wallet Tx ID</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        @forelse($charges as $c)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-3.5 px-4 font-mono text-slate-500">#{{ $c->id }}</td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white">{{ $c->user?->name ?? 'User #' . $c->user_id }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono">{{ $c->user?->email }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    @php
                                        $badgeColor = match($c->charge_type) {
                                            'mru_creation' => 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
                                            'mru_renewal' => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
                                            'mru_unlock' => 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30',
                                            default => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $badgeColor }}">
                                        {{ str_replace('_', ' ', $c->charge_type) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-400">
                                    {{ $c->reference_type }}:{{ $c->reference_id }}
                                </td>
                                <td class="py-3.5 px-4 font-bold text-emerald-400">
                                    ₹{{ number_format($c->amount, 2) }}
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-400">
                                    {{ $c->wallet_transaction_id ? '#' . $c->wallet_transaction_id : 'N/A' }}
                                </td>
                                <td class="py-3.5 px-4 text-right text-slate-400 font-mono text-[11px]">
                                    {{ $c->created_at?->format('d M Y, h:i A') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-500 italic">
                                    No overage charges recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($charges->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $charges->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
