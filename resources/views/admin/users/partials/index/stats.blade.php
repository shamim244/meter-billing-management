<div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
    <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Accounts</div>
            <div class="text-xl font-black text-white font-mono mt-0.5">{{ number_format($stats['total_users']) }}</div>
        </div>
        <span class="p-2 bg-indigo-500/10 rounded-xl text-indigo-400 text-base">👥</span>
    </div>

    <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Operators</div>
            <div class="text-xl font-black text-emerald-400 font-mono mt-0.5">{{ number_format($stats['active_users']) }}</div>
        </div>
        <span class="p-2 bg-emerald-500/10 rounded-xl text-emerald-400 text-base">✓</span>
    </div>

    <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Subscribed Agents</div>
            <div class="text-xl font-black text-cyan-400 font-mono mt-0.5">{{ number_format($stats['subscribed_users']) }}</div>
        </div>
        <span class="p-2 bg-cyan-500/10 rounded-xl text-cyan-400 text-base">⚡</span>
    </div>

    <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Suspended</div>
            <div class="text-xl font-black text-rose-400 font-mono mt-0.5">{{ number_format($stats['suspended_users']) }}</div>
        </div>
        <span class="p-2 bg-rose-500/10 rounded-xl text-rose-400 text-base">🚫</span>
    </div>
</div>
