<!-- Metrics Overview -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-md">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Active Subscribers</span>
            <span class="p-2 bg-indigo-500/10 rounded-xl text-indigo-400 text-lg">👥</span>
        </div>
        <div class="text-2xl font-black text-white mt-2">{{ number_format($totalSubscribers) }}</div>
        <div class="text-[11px] text-slate-500 mt-1">Active billing agents</div>
    </div>

    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-md">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Overage Revenue</span>
            <span class="p-2 bg-emerald-500/10 rounded-xl text-emerald-400 text-lg">💰</span>
        </div>
        <div class="text-2xl font-black text-emerald-400 mt-2">₹{{ number_format($totalOverageRevenue, 2) }}</div>
        <div class="text-[11px] text-slate-500 mt-1">MRU & Consumer overages</div>
    </div>

    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-md">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Locked MRUs</span>
            <span class="p-2 bg-amber-500/10 rounded-xl text-amber-400 text-lg">🔒</span>
        </div>
        <div class="text-2xl font-black text-amber-400 mt-2">{{ number_format($lockedMrusCount) }}</div>
        <div class="text-[11px] text-slate-500 mt-1">Pending unlock / renewal</div>
    </div>

    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-md">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Available Plans</span>
            <span class="p-2 bg-purple-500/10 rounded-xl text-purple-400 text-lg">📦</span>
        </div>
        <div class="text-2xl font-black text-white mt-2">{{ $plans->whereNull('deleted_at')->count() }}</div>
        <div class="text-[11px] text-slate-500 mt-1">{{ $plans->whereNotNull('deleted_at')->count() }} deactivated</div>
    </div>
</div>
