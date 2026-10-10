<div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
    <div class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
        Tag Breakdown (Total: {{ number_format($tagBreakdown['total_bills']) }})
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @foreach($tagBreakdown['tags'] as $t)
            <div class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-{{ $t['color'] }}-500"></span>
                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ $t['short_label'] }}:</span>
                <span class="text-xs font-bold font-mono text-slate-900 dark:text-white">{{ $t['count'] }}</span>
                <span class="text-[10px] text-slate-400">({{ $t['percentage'] }}%)</span>
            </div>
        @endforeach
    </div>
</div>
