<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 backdrop-blur-md">
        <div class="flex items-center justify-between text-xs font-bold text-slate-400 uppercase tracking-wider">
            <span>Total Contracts</span>
            <span>📑</span>
        </div>
        <div class="text-2xl font-black text-white mt-2">{{ number_format($counts['total']) }}</div>
    </div>

    <div class="bg-slate-900/60 border border-emerald-500/20 rounded-2xl p-4 backdrop-blur-md">
        <div class="flex items-center justify-between text-xs font-bold text-emerald-400 uppercase tracking-wider">
            <span>Active</span>
            <span>✅</span>
        </div>
        <div class="text-2xl font-black text-emerald-400 mt-2">{{ number_format($counts['active']) }}</div>
        <div class="text-[10px] text-slate-500 mt-0.5">Full access</div>
    </div>

    <div class="bg-slate-900/60 border border-amber-500/20 rounded-2xl p-4 backdrop-blur-md">
        <div class="flex items-center justify-between text-xs font-bold text-amber-400 uppercase tracking-wider">
            <span>Renewal Due</span>
            <span>⏰</span>
        </div>
        <div class="text-2xl font-black text-amber-400 mt-2">{{ number_format($counts['renewal_due']) }}</div>
        <div class="text-[10px] text-slate-500 mt-0.5">Full access + banner</div>
    </div>

    <div class="bg-slate-900/60 border border-orange-500/20 rounded-2xl p-4 backdrop-blur-md">
        <div class="flex items-center justify-between text-xs font-bold text-orange-400 uppercase tracking-wider">
            <span>Grace Period</span>
            <span>⏳</span>
        </div>
        <div class="text-2xl font-black text-orange-400 mt-2">{{ number_format($counts['grace_period']) }}</div>
        <div class="text-[10px] text-slate-500 mt-0.5">Countdown active</div>
    </div>

    <div class="bg-slate-900/60 border border-rose-500/20 rounded-2xl p-4 backdrop-blur-md">
        <div class="flex items-center justify-between text-xs font-bold text-rose-400 uppercase tracking-wider">
            <span>Suspended</span>
            <span>🔒</span>
        </div>
        <div class="text-2xl font-black text-rose-400 mt-2">{{ number_format($counts['suspended']) }}</div>
        <div class="text-[10px] text-slate-500 mt-0.5">Read-only mode</div>
    </div>
</div>
