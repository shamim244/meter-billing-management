<!-- Summary Metrics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center text-xl font-bold">
            📑
        </div>
        <div>
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total Reported</span>
            <span class="text-2xl font-black text-slate-900 dark:text-white">{{ $stats['total'] }}</span>
        </div>
    </div>

    <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl font-bold">
            ⏳
        </div>
        <div>
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Under Review / Active</span>
            <span class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ $stats['active'] }}</span>
        </div>
    </div>

    <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl font-bold">
            ✅
        </div>
        <div>
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Resolved & Fixed</span>
            <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $stats['resolved'] }}</span>
        </div>
    </div>
</div>
