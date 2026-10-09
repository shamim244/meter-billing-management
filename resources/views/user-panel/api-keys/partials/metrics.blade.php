<!-- Key Usage Metrics -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Total Issued</span>
        <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">{{ $stats['total'] }}</div>
        <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 block">Lifetime generated</span>
    </div>

    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 block">Active Keys</span>
        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-1">{{ $stats['active'] }}</div>
        <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 block">Ready for API requests</span>
    </div>

    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-rose-500 dark:text-rose-400 block">Expired Keys</span>
        <div class="text-2xl font-black text-rose-500 dark:text-rose-400 font-mono mt-1">{{ $stats['expired'] }}</div>
        <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 block">Past expiration date</span>
    </div>

    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-brand-600 dark:text-cyan-400 block">Last Active</span>
        <div class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-2 truncate">
            {{ $stats['last_used'] && $stats['last_used']->last_used_at ? $stats['last_used']->last_used_at->diffForHumans() : 'Never used' }}
        </div>
        <span class="text-[10px] font-mono text-slate-400 mt-1 block truncate">
            {{ $stats['last_used'] && $stats['last_used']->last_ip ? 'IP: '.$stats['last_used']->last_ip : 'Awaiting first call' }}
        </span>
    </div>
</div>
