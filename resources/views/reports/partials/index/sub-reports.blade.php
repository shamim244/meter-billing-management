<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <a href="{{ route('reports.status_tag', ['month' => $month, 'year' => $year]) }}" class="p-3.5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-blue-500/50 flex items-center justify-between transition shadow-sm group">
        <div class="flex items-center gap-3">
            <span class="text-xl">🏷️</span>
            <div>
                <div class="text-xs font-bold text-slate-800 dark:text-white group-hover:text-blue-500 transition">Status & Tag Report</div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400">Review status & custom tag breakdown</div>
            </div>
        </div>
        <span class="text-xs text-blue-500 font-bold">→</span>
    </a>

    <a href="{{ route('reports.quota', ['month' => $month, 'year' => $year]) }}" class="p-3.5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-blue-500/50 flex items-center justify-between transition shadow-sm group">
        <div class="flex items-center gap-3">
            <span class="text-xl">📈</span>
            <div>
                <div class="text-xs font-bold text-slate-800 dark:text-white group-hover:text-blue-500 transition">Quota Usage Report</div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400">MRU / Consumer consumption & trends</div>
            </div>
        </div>
        <span class="text-xs text-blue-500 font-bold">→</span>
    </a>

    <a href="{{ route('reports.flagged', ['month' => $month, 'year' => $year]) }}" class="p-3.5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-amber-500/50 flex items-center justify-between transition shadow-sm group">
        <div class="flex items-center gap-3">
            <span class="text-xl">⚠️</span>
            <div>
                <div class="text-xs font-bold text-slate-800 dark:text-white group-hover:text-amber-500 transition">Consecutive Estimates</div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400">{{ $summary['roi_summary']['flagged_consumers_count'] }} consumers on 2+ LK/MD cycles</div>
            </div>
        </div>
        <span class="text-xs text-amber-500 font-bold">→</span>
    </a>
</div>
