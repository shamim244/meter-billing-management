<div class="grid grid-cols-2 md:grid-cols-5 gap-4">
    <!-- 1. Bills Processed -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Bills Processed</span>
        <div class="text-2xl font-black text-blue-600 dark:text-cyan-400 font-mono">
            {{ number_format($summary['roi_summary']['bills_processed']) }}
        </div>
        <div class="text-[10px] text-slate-400">Parsed this cycle</div>
    </div>

    <!-- 2. Active MRUs -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Active MRUs</span>
        <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 font-mono">
            {{ $summary['roi_summary']['mrus_active'] }}
        </div>
        <div class="text-[10px] text-slate-400">Under contract</div>
    </div>

    <!-- 3. Data Coverage -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Data Coverage</span>
        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">
            {{ $summary['roi_summary']['data_coverage_percentage'] }}%
        </div>
        <div class="text-[10px] text-slate-400">{{ $summary['roi_summary']['bills_processed'] }} / {{ $summary['roi_summary']['total_consumers'] }} consumers</div>
    </div>

    <!-- 4. Flagged Estimates -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Consecutive LK/MD</span>
        <div class="text-2xl font-black {{ $summary['roi_summary']['flagged_consumers_count'] > 0 ? 'text-amber-500' : 'text-slate-400' }} font-mono">
            {{ $summary['roi_summary']['flagged_consumers_count'] }}
        </div>
        <div class="text-[10px] text-slate-400">Needs meter reader inspection</div>
    </div>

    <!-- 5. Historical Depth -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-1 col-span-2 md:col-span-1">
        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Historical Depth</span>
        <div class="text-2xl font-black text-purple-600 dark:text-purple-400 font-mono">
            {{ $summary['roi_summary']['historical_depth_months'] }} <span class="text-xs text-slate-400 font-normal">Months</span>
        </div>
        <div class="text-[10px] text-slate-400">Continuous ledger records</div>
    </div>
</div>
