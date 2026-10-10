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
