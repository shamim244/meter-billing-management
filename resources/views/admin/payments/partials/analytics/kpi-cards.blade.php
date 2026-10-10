<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- All Time Collections -->
    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Revenue</span>
            <span class="text-lg">💰</span>
        </div>
        <div class="text-2xl font-black text-emerald-400 font-mono">₹{{ number_format($totalCollected, 2) }}</div>
        <span class="text-[11px] text-slate-500">All-time successful collections</span>
    </div>

    <!-- This Month Collections -->
    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">This Month</span>
            <span class="text-lg">📈</span>
        </div>
        <div class="text-2xl font-black text-cyan-400 font-mono">₹{{ number_format($monthCollected, 2) }}</div>
        <span class="text-[11px] text-slate-500">Month-to-date collections</span>
    </div>

    <!-- Today Collections -->
    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Today</span>
            <span class="text-lg">⚡</span>
        </div>
        <div class="text-2xl font-black text-indigo-400 font-mono">₹{{ number_format($todayCollected, 2) }}</div>
        <span class="text-[11px] text-slate-500">Processed in the last 24h</span>
    </div>

    <!-- Success Rate -->
    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Success Rate</span>
            <span class="text-lg">🎯</span>
        </div>
        <div class="text-2xl font-black text-purple-400 font-mono">{{ $successRate }}%</div>
        <span class="text-[11px] text-slate-500">Avg ticket: ₹{{ number_format($avgTicketSize, 2) }}</span>
    </div>
</div>
