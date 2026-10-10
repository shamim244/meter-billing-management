<div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
    <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
        <span>📊</span> Transaction Status Breakdown
    </h3>
    <p class="text-xs text-slate-400">Total pipeline count of all payments processed on the platform.</p>

    <div class="grid grid-cols-2 gap-3">
        <div class="p-4 bg-slate-900 rounded-xl border border-slate-800">
            <span class="text-[11px] text-slate-400 block mb-1">Successful (Credited)</span>
            <div class="text-xl font-black text-emerald-400 font-mono">{{ $successCount }}</div>
        </div>

        <div class="p-4 bg-slate-900 rounded-xl border border-slate-800">
            <span class="text-[11px] text-slate-400 block mb-1">Pending Verification</span>
            <div class="text-xl font-black text-amber-400 font-mono">{{ $pendingCount }}</div>
        </div>

        <div class="p-4 bg-slate-900 rounded-xl border border-slate-800">
            <span class="text-[11px] text-slate-400 block mb-1">Rejected Manual Claims</span>
            <div class="text-xl font-black text-rose-400 font-mono">{{ $rejectedCount }}</div>
        </div>

        <div class="p-4 bg-slate-900 rounded-xl border border-slate-800">
            <span class="text-[11px] text-slate-400 block mb-1">Gateway Failed / Dropped</span>
            <div class="text-xl font-black text-slate-400 font-mono">{{ $failedCount }}</div>
        </div>
    </div>

    <!-- Purpose Breakdown -->
    <div class="pt-2 border-t border-slate-900 space-y-2">
        <span class="text-xs font-bold text-slate-300 block">Revenue by Purpose</span>
        <div class="grid grid-cols-2 gap-3 text-xs">
            <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800/80">
                <span class="text-[10px] text-slate-400 block">👛 Wallet Top-Ups</span>
                <span class="font-mono font-bold text-white">₹{{ number_format($purposeBreakdown['wallet_topup']['amount'], 2) }}</span>
                <span class="text-[10px] text-slate-500 block">({{ $purposeBreakdown['wallet_topup']['count'] }} orders)</span>
            </div>
            <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800/80">
                <span class="text-[10px] text-slate-400 block">⭐ Direct Subscriptions</span>
                <span class="font-mono font-bold text-white">₹{{ number_format($purposeBreakdown['direct_subscription']['amount'], 2) }}</span>
                <span class="text-[10px] text-slate-500 block">({{ $purposeBreakdown['direct_subscription']['count'] }} orders)</span>
            </div>
        </div>
    </div>
</div>
