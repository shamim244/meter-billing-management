<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="glass-panel p-5 rounded-2xl border border-slate-800">
        <span class="text-xs text-slate-400 font-medium">Referred Agents</span>
        <p class="text-2xl font-black text-white mt-1">{{ number_format($stats['total_referred']) }}</p>
        <p class="text-[11px] text-slate-500 mt-0.5">Friends who joined via your link</p>
    </div>
    <div class="glass-panel p-5 rounded-2xl border border-slate-800">
        <span class="text-xs text-slate-400 font-medium">Pending Rewards</span>
        <p class="text-2xl font-black text-amber-400 mt-1">₹{{ number_format($stats['pending_rewards'], 2) }}</p>
        <p class="text-[11px] text-slate-500 mt-0.5">In hold period (matures soon)</p>
    </div>
    <div class="glass-panel p-5 rounded-2xl border border-slate-800">
        <span class="text-xs text-slate-400 font-medium">Paid Rewards</span>
        <p class="text-2xl font-black text-emerald-400 mt-1">₹{{ number_format($stats['paid_rewards'], 2) }}</p>
        <p class="text-[11px] text-slate-500 mt-0.5">Credited directly to your wallet</p>
    </div>
    <div class="glass-panel p-5 rounded-2xl border border-slate-800">
        <span class="text-xs text-slate-400 font-medium">Lifetime Earned</span>
        <p class="text-2xl font-black text-purple-400 mt-1">₹{{ number_format($stats['total_earned'], 2) }}</p>
        <p class="text-[11px] text-slate-500 mt-0.5">Total earnings from program</p>
    </div>
</div>
