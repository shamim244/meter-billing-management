{{-- Top Metrics Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
    <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Coupons</div>
            <div class="text-xl font-black text-white font-mono mt-0.5">{{ number_format($stats['total_coupons']) }}</div>
        </div>
        <span class="p-2 bg-indigo-500/10 rounded-xl text-indigo-400 text-base">🎟️</span>
    </div>

    <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Campaigns</div>
            <div class="text-xl font-black text-emerald-400 font-mono mt-0.5">{{ number_format($stats['active_campaigns']) }}</div>
        </div>
        <span class="p-2 bg-emerald-500/10 rounded-xl text-emerald-400 text-base">✓</span>
    </div>

    <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Redemptions</div>
            <div class="text-xl font-black text-cyan-400 font-mono mt-0.5">{{ number_format($stats['total_redemptions']) }}</div>
        </div>
        <span class="p-2 bg-cyan-500/10 rounded-xl text-cyan-400 text-base">⚡</span>
    </div>

    <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Discount / Bonus</div>
            <div class="text-xl font-black text-amber-400 font-mono mt-0.5">₹{{ number_format($stats['total_discount_given'], 2) }}</div>
        </div>
        <span class="p-2 bg-amber-500/10 rounded-xl text-amber-400 text-base">🎁</span>
    </div>
</div>
