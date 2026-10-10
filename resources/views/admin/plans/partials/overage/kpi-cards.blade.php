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
