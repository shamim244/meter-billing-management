<!-- Metric KPI Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="glass-card p-4 rounded-2xl border border-slate-800/80">
        <p class="text-xs text-slate-400 font-medium">Total Referred Signups</p>
        <p class="text-2xl font-black text-white mt-1">{{ number_format($totalReferrals) }}</p>
        <p class="text-[11px] text-slate-500 mt-0.5">Across all platform agents</p>
    </div>
    <div class="glass-card p-4 rounded-2xl border border-slate-800/80">
        <p class="text-xs text-slate-400 font-medium">Active Referrers</p>
        <p class="text-2xl font-black text-cyan-400 mt-1">{{ number_format($activeReferrers) }}</p>
        <p class="text-[11px] text-slate-500 mt-0.5">Agents with $\ge 1$ referral</p>
    </div>
    <div class="glass-card p-4 rounded-2xl border border-slate-800/80">
        <p class="text-xs text-slate-400 font-medium">Pending Rewards (In Hold)</p>
        <p class="text-2xl font-black text-amber-400 mt-1">₹{{ number_format($pendingPayouts, 2) }}</p>
        <p class="text-[11px] text-slate-500 mt-0.5">Clearing hold period</p>
    </div>
    <div class="glass-card p-4 rounded-2xl border border-slate-800/80">
        <p class="text-xs text-slate-400 font-medium">Total Paid Rewards</p>
        <p class="text-2xl font-black text-emerald-400 mt-1">₹{{ number_format($totalPaidPayouts, 2) }}</p>
        <p class="text-[11px] text-slate-500 mt-0.5">Credited to agent wallets</p>
    </div>
</div>
