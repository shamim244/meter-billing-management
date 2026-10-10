<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Review Status Breakdown Card -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <span>📋</span> Review Status Distribution
            </h2>
            <a href="{{ route('reports.status_tag', ['month' => $month, 'year' => $year]) }}" class="text-xs font-semibold text-blue-600 dark:text-cyan-400 hover:underline">
                Details & CSV →
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 rounded-xl text-center">
                <div class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 uppercase">Submitted</div>
                <div class="text-xl font-black text-emerald-600 dark:text-emerald-300 font-mono mt-0.5">
                    {{ $summary['status_breakdown']['submitted'] }}
                </div>
            </div>

            <div class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/40 rounded-xl text-center">
                <div class="text-[10px] font-bold text-rose-700 dark:text-rose-400 uppercase">Critical</div>
                <div class="text-xl font-black text-rose-600 dark:text-rose-300 font-mono mt-0.5">
                    {{ $summary['status_breakdown']['critical'] }}
                </div>
            </div>

            <div class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/40 rounded-xl text-center">
                <div class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase">Doubt</div>
                <div class="text-xl font-black text-amber-600 dark:text-amber-300 font-mono mt-0.5">
                    {{ $summary['status_breakdown']['doubt'] }}
                </div>
            </div>

            <div class="p-3 bg-slate-100 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl text-center">
                <div class="text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase">Pending</div>
                <div class="text-xl font-black text-slate-700 dark:text-slate-300 font-mono mt-0.5">
                    {{ $summary['status_breakdown']['pending'] }}
                </div>
            </div>
        </div>
    </div>

    <!-- Tag Breakdown Card -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <span>🏷️</span> Bill Review Tags
            </h2>
            <a href="{{ route('reports.status_tag', ['month' => $month, 'year' => $year]) }}" class="text-xs font-semibold text-blue-600 dark:text-cyan-400 hover:underline">
                Drill Down →
            </a>
        </div>

        <div class="space-y-2.5">
            @foreach($summary['tag_breakdown']['tags'] as $tag)
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-{{ $tag['color'] }}-500"></span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $tag['short_label'] }}</span>
                        <span class="text-[10px] text-slate-400">({{ $tag['label'] }})</span>
                    </div>
                    <div class="flex items-center gap-3 font-mono">
                        <span class="font-bold text-slate-900 dark:text-white">{{ $tag['count'] }}</span>
                        <span class="text-[10px] text-slate-400 w-10 text-right">{{ $tag['percentage'] }}%</span>
                    </div>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                    <div class="h-1.5 rounded-full bg-{{ $tag['color'] }}-500 transition-all duration-500" style="width: {{ $tag['percentage'] }}%"></div>
                </div>
            @endforeach
        </div>
    </div>
</div>
