{{-- Queue Summary Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Manual Queue</span>
        <div class="text-2xl font-black text-amber-400 font-mono">{{ $pendingPayments->total() }}</div>
        <span class="text-[11px] text-slate-500">Requires bank statement verification</span>
    </div>

    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Volume Value</span>
        <div class="text-2xl font-black text-cyan-400 font-mono">₹{{ number_format($totalPendingAmount, 2) }}</div>
        <span class="text-[11px] text-slate-500">Total claimed unverified balance</span>
    </div>

    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Channel Distribution</span>
        <div class="flex items-center gap-3 text-xs font-bold pt-1">
            <span class="text-purple-400">📱 UPI: {{ $upiPendingCount }}</span>
            <span class="text-slate-600">•</span>
            <span class="text-blue-400">🏦 Bank: {{ $bankPendingCount }}</span>
        </div>
        <span class="text-[11px] text-slate-500">Breakdown by payment mode</span>
    </div>
</div>
