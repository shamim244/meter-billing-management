<div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
    <!-- Available Balance -->
    <div class="bg-gradient-to-br from-indigo-900/90 to-slate-900 text-white p-5 rounded-3xl border border-indigo-500/30 shadow-lg shadow-indigo-950/30 space-y-2 relative overflow-hidden">
        <div class="flex items-center justify-between text-xs text-indigo-200 font-bold uppercase tracking-wider">
            <span>Available Balance</span>
            <span>👛</span>
        </div>
        <div class="text-3xl font-black font-mono tracking-tight text-white">
            ₹{{ number_format((float)$balance, 2) }}
        </div>
        <div class="flex items-center gap-1.5 text-[11px] {{ $balance > 200 ? 'text-emerald-400' : 'text-amber-400 font-bold' }}">
            @if($balance <= 0)
                <span>⚠️ Zero Balance (Top-up required)</span>
            @elseif($balance < 200)
                <span>⚠️ Low Balance Warning</span>
            @else
                <span>✓ Active & Ready for Tasks</span>
            @endif
        </div>
    </div>

    <!-- Total Credited -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-1">
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Credited</span>
        <div class="text-xl font-black font-mono text-emerald-600 dark:text-emerald-400">
            ₹{{ number_format($stats['total_credited'], 2) }}
        </div>
        <span class="text-[10px] text-slate-400">Lifetime top-ups & bonuses</span>
    </div>

    <!-- Total Debited -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-1">
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Debited</span>
        <div class="text-xl font-black font-mono text-slate-800 dark:text-slate-200">
            ₹{{ number_format($stats['total_debited'], 2) }}
        </div>
        <span class="text-[10px] text-slate-400">Processed downloads & fees</span>
    </div>

    <!-- Total Transactions -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-1">
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Ledger Count</span>
        <div class="text-xl font-black font-mono text-slate-800 dark:text-slate-200">
            {{ number_format($stats['transaction_count']) }}
        </div>
        <span class="text-[10px] text-slate-400">Immutable ledger entries</span>
    </div>
</div>
