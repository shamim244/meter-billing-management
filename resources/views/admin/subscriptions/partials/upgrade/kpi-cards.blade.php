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
