<div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
    <div class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
        Status Counts (Total: {{ number_format($statusBreakdown['total']) }})
    </div>
    <div class="grid grid-cols-4 gap-2">
        <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl text-center border border-emerald-200/60 dark:border-emerald-800/40">
            <div class="text-[9px] font-bold text-emerald-600 uppercase">Submitted</div>
            <div class="text-lg font-black text-emerald-700 dark:text-emerald-300 font-mono">{{ $statusBreakdown['submitted'] }}</div>
        </div>
        <div class="p-2.5 bg-rose-50 dark:bg-rose-950/40 rounded-xl text-center border border-rose-200/60 dark:border-rose-800/40">
            <div class="text-[9px] font-bold text-rose-600 uppercase">Critical</div>
            <div class="text-lg font-black text-rose-700 dark:text-rose-300 font-mono">{{ $statusBreakdown['critical'] }}</div>
        </div>
        <div class="p-2.5 bg-amber-50 dark:bg-amber-950/40 rounded-xl text-center border border-amber-200/60 dark:border-amber-800/40">
            <div class="text-[9px] font-bold text-amber-600 uppercase">Doubt</div>
            <div class="text-lg font-black text-amber-700 dark:text-amber-300 font-mono">{{ $statusBreakdown['doubt'] }}</div>
        </div>
        <div class="p-2.5 bg-slate-100 dark:bg-slate-800 rounded-xl text-center border border-slate-200 dark:border-slate-700">
            <div class="text-[9px] font-bold text-slate-600 uppercase">Pending</div>
            <div class="text-lg font-black text-slate-700 dark:text-slate-300 font-mono">{{ $statusBreakdown['pending'] }}</div>
        </div>
    </div>
</div>
