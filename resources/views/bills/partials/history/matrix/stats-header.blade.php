<div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2">
            <h2 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span>📊</span> Dedicated Monthly Meter Reading History (2D Matrix)
            </h2>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-50 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                Dual-Source History
            </span>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Direct side-by-side comparison of Official NBPDCL PDF bill extractions vs Operator field entries. Working readings take calculation precedence for monthly averages.
        </p>
    </div>

    <div class="flex items-center gap-3 shrink-0">
        <div class="px-3.5 py-1.5 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/50 border border-indigo-100 dark:border-indigo-900/60 text-center">
            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 block">Smart Avg Basis</span>
            <span class="text-sm font-black text-indigo-700 dark:text-indigo-300 font-mono">{{ $meterMatrix['average_units'] ?? 50 }} kWh</span>
        </div>
        <div class="px-3.5 py-1.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-center">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Recorded Periods</span>
            <span class="text-sm font-black text-slate-800 dark:text-slate-200 font-mono">{{ $meterMatrix['periods_count'] ?? 0 }}</span>
        </div>
    </div>
</div>
