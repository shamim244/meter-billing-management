<!-- Summary KPI Cards -->
<div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Wallets</span>
        <div class="text-2xl font-black font-mono text-white">{{ $stats['total_wallets'] }}</div>
        <span class="text-[10px] text-slate-500">Registered agent accounts</span>
    </div>

    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total System Balance</span>
        <div class="text-2xl font-black font-mono text-emerald-400">₹{{ number_format($stats['total_balance'], 2) }}</div>
        <span class="text-[10px] text-slate-500">Combined agent liabilities</span>
    </div>

    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Frozen Wallets</span>
        <div class="text-2xl font-black font-mono {{ $stats['frozen_wallets'] > 0 ? 'text-rose-400' : 'text-slate-400' }}">
            {{ $stats['frozen_wallets'] }}
        </div>
        <span class="text-[10px] text-slate-500">Restricted debit status</span>
    </div>

    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Admin Adjustments</span>
        <div class="text-2xl font-black font-mono text-indigo-400">{{ $stats['total_adjustments'] }}</div>
        <span class="text-[10px] text-slate-500">Total manual adjustments logged</span>
    </div>
</div>
