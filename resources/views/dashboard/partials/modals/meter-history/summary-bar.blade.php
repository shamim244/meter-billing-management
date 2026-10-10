<!-- Summary Bar -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
    <div class="p-3 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 text-center">
        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 block">Smart Avg Basis</span>
        <span class="text-base font-black text-indigo-700 dark:text-cyan-400 font-mono" x-text="(meterHistoryData?.average_units || 50) + ' kWh'"></span>
    </div>
    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-center">
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Recorded Periods</span>
        <span class="text-base font-black text-slate-800 dark:text-slate-200 font-mono" x-text="meterHistoryData?.periods_count || 0"></span>
    </div>
    <div class="p-3 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/60 text-center">
        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 block">Calculation Priority</span>
        <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300">Working > PDF</span>
    </div>
    <div class="p-3 rounded-2xl bg-blue-50/60 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/60 text-center">
        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 block">Full Ledger View</span>
        <a :href="'/bills/history/' + activeHistoryCa" target="_blank" class="text-xs font-bold text-blue-600 dark:text-cyan-400 hover:underline">
            Open Page ↗
        </a>
    </div>
</div>
