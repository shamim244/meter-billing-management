{{-- Metric KPI Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-slate-950 p-5 rounded-2xl border border-amber-500/30 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Pending Verification</span>
            <span class="text-lg">⏳</span>
        </div>
        <div class="text-2xl font-black text-amber-400 mt-2">{{ number_format($pendingCount) }}</div>
        <span class="text-[11px] text-slate-400">Manual UPI & Bank Transfers awaiting review</span>
    </div>

    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Collected</span>
            <span class="text-lg">💰</span>
        </div>
        <div class="text-2xl font-black text-emerald-400 mt-2">₹{{ number_format($totalCollected, 2) }}</div>
        <span class="text-[11px] text-slate-400">Across all successful payments</span>
    </div>

    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Successful Payments</span>
            <span class="text-lg">✅</span>
        </div>
        <div class="text-2xl font-black text-white mt-2">{{ number_format($successCount) }}</div>
        <span class="text-[11px] text-slate-400">PG + Approved manual payments</span>
    </div>

    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rejected Payments</span>
            <span class="text-lg">❌</span>
        </div>
        <div class="text-2xl font-black text-rose-400 mt-2">{{ number_format($rejectedCount) }}</div>
        <span class="text-[11px] text-slate-400">Invalid UTRs / mismatched transfers</span>
    </div>
</div>
