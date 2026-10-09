{{-- Recent Simulated Transactions Feed --}}
<div class="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-sm space-y-3 p-5">
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
            <span>📋</span> Recent Payment Activity Feed
        </h3>
        <a href="{{ route('admin.payments.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-bold">
            View Master Ledger →
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-900/80 text-[10px] uppercase text-slate-400 border-b border-slate-800 font-bold">
                <tr>
                    <th class="py-2.5 px-3">Payment ID</th>
                    <th class="py-2.5 px-3">User / Agent</th>
                    <th class="py-2.5 px-3">Mode</th>
                    <th class="py-2.5 px-3">Amount</th>
                    <th class="py-2.5 px-3">Status</th>
                    <th class="py-2.5 px-3 text-right">Audit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 font-mono">
                @forelse($recentPayments as $rp)
                    <tr class="hover:bg-slate-900/40">
                        <td class="py-2.5 px-3 text-white font-bold">#{{ $rp->id }}</td>
                        <td class="py-2.5 px-3 font-sans text-slate-300">{{ $rp->user->name ?? 'User #' . $rp->user_id }}</td>
                        <td class="py-2.5 px-3 font-sans">
                            @if($rp->mode->value === 'pg')
                                <span class="text-cyan-400 font-bold">⚡ Online PG</span>
                            @elseif($rp->mode->value === 'manual_upi')
                                <span class="text-purple-400 font-bold">📱 Manual UPI</span>
                            @else
                                <span class="text-blue-400 font-bold">🏦 Bank Transfer</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-3 text-white font-black">₹{{ number_format((float)$rp->amount, 2) }}</td>
                        <td class="py-2.5 px-3 font-sans">
                            @if($rp->status->value === 'success')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">SUCCESS</span>
                            @elseif($rp->status->value === 'pending_verification')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">PENDING</span>
                            @elseif($rp->status->value === 'rejected')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">REJECTED</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700">{{ strtoupper($rp->status->value) }}</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-3 text-right font-sans">
                            <a href="{{ route('admin.payments.show', $rp->id) }}" class="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-[11px] font-semibold transition">
                                Inspect →
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-slate-500 font-sans">No payment records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
