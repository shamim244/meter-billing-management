<!-- Live Storage Analytics KPIs -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
    <!-- Total Physical PDFs -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider">
            <span>Stored PDF Files</span>
            <span class="text-brand-500 text-base">📁</span>
        </div>
        <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white font-mono mt-2">
            {{ number_format($metrics['disk_files_count']) }}
        </div>
        <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
            <span>{{ number_format($metrics['downloaded_count']) }} mapped records</span>
        </div>
    </div>

    <!-- Total Disk Space Used -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider">
            <span>Disk Usage</span>
            <span class="text-indigo-500 text-base">💾</span>
        </div>
        <div class="text-2xl sm:text-3xl font-black text-indigo-600 dark:text-indigo-400 font-mono mt-2">
            {{ $metrics['total_size_formatted'] }}
        </div>
        <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
            <span>~{{ $metrics['avg_size_kb'] }} KB avg per file</span>
        </div>
    </div>

    <!-- Parsed & Synchronized Rate -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider">
            <span>Extraction Rate</span>
            <span class="text-emerald-500 text-base">⚡</span>
        </div>
        <div class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-2">
            {{ $metrics['parsed_rate'] }}%
        </div>
        <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
            <span>{{ number_format($metrics['parsed_count']) }} extracted & verified</span>
        </div>
    </div>

    <!-- Storage Health & Quota Tier -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider">
            <span>Plan & Quota</span>
            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-brand-100 dark:bg-brand-950 text-brand-700 dark:text-brand-300 border border-brand-200 dark:border-brand-800">
                {{ $metrics['plan_tier'] }}
            </span>
        </div>
        <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono mt-2">
            {{ $metrics['storage_limit_mb'] }} MB Limit
        </div>
        <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
            <span class="{{ $metrics['is_limit_exceeded'] ? 'text-rose-500 font-bold' : ($metrics['storage_usage_percent'] > 80 ? 'text-amber-500 font-bold' : 'text-emerald-500') }}">
                {{ $metrics['storage_usage_percent'] }}% used
            </span>
        </div>
    </div>
</div>

<!-- Storage Quota Usage Meter Bar -->
<div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div class="flex items-center gap-2.5">
            <span class="text-lg">📊</span>
            <div>
                <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Subscription Storage Allocation</span>
                <span class="text-[11px] text-slate-500 ml-1 font-mono">({{ $metrics['total_size_formatted'] }} / {{ $metrics['storage_limit_formatted'] }})</span>
            </div>
        </div>
        <div class="text-xs font-mono font-bold {{ $metrics['is_limit_exceeded'] ? 'text-rose-500' : ($metrics['storage_usage_percent'] > 80 ? 'text-amber-500' : 'text-emerald-500') }}">
            {{ $metrics['storage_usage_percent'] }}% Quota Used
        </div>
    </div>

    <!-- Progress Bar Track -->
    <div class="w-full bg-slate-100 dark:bg-slate-800 h-3 rounded-full overflow-hidden p-0.5 border border-slate-200/60 dark:border-slate-700/60">
        <div class="h-full rounded-full transition-all duration-500 {{ $metrics['is_limit_exceeded'] ? 'bg-gradient-to-r from-rose-500 to-red-600' : ($metrics['storage_usage_percent'] > 80 ? 'bg-gradient-to-r from-amber-400 to-rose-500' : 'bg-gradient-to-r from-brand-500 via-cyan-400 to-emerald-400') }}"
             style="width: {{ min(100, $metrics['storage_usage_percent']) }}%">
        </div>
    </div>

    @if($metrics['is_limit_exceeded'])
        <div class="p-3 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/80 text-rose-800 dark:text-rose-300 text-xs font-medium flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span>⚠️</span>
                <span><strong>Storage Limit Exceeded ({{ $metrics['storage_limit_mb'] }} MB).</strong> Further PDF downloads/uploads are blocked. Purge old cycle PDFs below to immediately reclaim space.</span>
            </div>
        </div>
    @elseif($metrics['storage_usage_percent'] > 75)
        <div class="p-3 rounded-2xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/80 text-amber-800 dark:text-amber-300 text-xs font-medium flex items-center gap-2">
            <span>💡</span>
            <span><strong>Tip:</strong> Storage is nearing capacity. Purge completed historical cycle PDFs below to free up space while preserving your ledger records.</span>
        </div>
    @endif
</div>
