<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <a href="{{ route('admin.subscriptions.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1 mb-2">
                    ← Back to Subscriptions
                </a>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>🔁</span> Renewal Attempts History
                </h1>
                <p class="text-sm text-slate-400 mt-1">Audit trail of auto-renewal scheduled debits and manual renewal clicks via Wallet.</p>
            </div>
        </div>

        <!-- KPI Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 backdrop-blur-md">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Attempts</div>
                <div class="text-2xl font-black text-white mt-2">{{ number_format($counts['total']) }}</div>
            </div>

            <div class="bg-slate-900/60 border border-emerald-500/20 rounded-2xl p-4 backdrop-blur-md">
                <div class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Successful Renewals</div>
                <div class="text-2xl font-black text-emerald-400 mt-2">{{ number_format($counts['success']) }}</div>
            </div>

            <div class="bg-slate-900/60 border border-amber-500/20 rounded-2xl p-4 backdrop-blur-md">
                <div class="text-xs font-bold text-amber-400 uppercase tracking-wider">Insufficient Balance</div>
                <div class="text-2xl font-black text-amber-400 mt-2">{{ number_format($counts['insufficient_balance']) }}</div>
            </div>

            <div class="bg-slate-900/60 border border-rose-500/20 rounded-2xl p-4 backdrop-blur-md">
                <div class="text-xs font-bold text-rose-400 uppercase tracking-wider">Wallet Frozen</div>
                <div class="text-2xl font-black text-rose-400 mt-2">{{ number_format($counts['wallet_frozen']) }}</div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="p-5 border-b border-slate-800/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">Renewal History</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Every attempt is recorded with failure reasons and transaction IDs.</p>
                </div>

                <form method="GET" action="{{ route('admin.subscriptions.renewal_attempts') }}" class="flex items-center gap-2.5">
                    <select name="status" onchange="this.form.submit()" class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-1.5 px-3 focus:ring-indigo-500">
                        <option value="">All Statuses</option>
                        <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
                        <option value="insufficient_balance" {{ request('status') === 'insufficient_balance' ? 'selected' : '' }}>Insufficient Balance</option>
                        <option value="wallet_frozen" {{ request('status') === 'wallet_frozen' ? 'selected' : '' }}>Wallet Frozen</option>
                    </select>

                    <select name="attempt_type" onchange="this.form.submit()" class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-1.5 px-3 focus:ring-indigo-500">
                        <option value="">All Types</option>
                        <option value="auto" {{ request('attempt_type') === 'auto' ? 'selected' : '' }}>Auto Scheduled</option>
                        <option value="manual" {{ request('attempt_type') === 'manual' ? 'selected' : '' }}>Manual Click</option>
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
                            <th class="py-3.5 px-4 font-semibold">Amount</th>
                            <th class="py-3.5 px-4 font-semibold">Status & Details</th>
                            <th class="py-3.5 px-4 font-semibold">Wallet Tx ID</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Attempted At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        @forelse($attempts as $a)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-3.5 px-4 font-mono text-slate-500">#{{ $a->id }}</td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white">{{ $a->user?->name ?? 'User #' . $a->user_id }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono">{{ $a->user?->email }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($a->attempt_type === 'auto')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                            🤖 AUTO
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-blue-500/20 text-blue-300 border border-blue-500/30">
                                            👤 MANUAL
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-bold text-white">
                                    ₹{{ number_format($a->amount_charged, 2) }}
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($a->status === 'success')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                            SUCCESS
                                        </span>
                                    @elseif($a->status === 'insufficient_balance')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                            INSUFFICIENT BALANCE
                                        </span>
                                        <div class="text-[10px] text-slate-400 mt-0.5">{{ $a->failure_reason }}</div>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                            {{ strtoupper($a->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-400">
                                    {{ $a->wallet_transaction_id ? '#' . $a->wallet_transaction_id : '—' }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-[11px] text-slate-400">
                                    {{ $a->attempted_at?->format('d M Y, h:i A') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-500 italic">
                                    No renewal attempts recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($attempts->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $attempts->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
