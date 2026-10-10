<div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
        <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
            <span>💳</span> Subscription Plan Quota Utilization
        </h2>
        <a href="{{ route('reports.quota', ['month' => $month, 'year' => $year]) }}" class="text-xs font-semibold text-blue-600 dark:text-cyan-400 hover:underline">
            6-Month Trend Matrix →
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- MRU Quota -->
        <div class="p-4 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-slate-800 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">MRU Quota</span>
                <span class="text-xs font-mono font-bold text-slate-900 dark:text-white">
                    {{ $summary['quota_usage']['mru']['used'] }} / {{ $summary['quota_usage']['mru']['included'] }} Active
                </span>
            </div>
            @php
                $mruPct = $summary['quota_usage']['mru']['included'] > 0 
                    ? min(100, round(($summary['quota_usage']['mru']['used'] / $summary['quota_usage']['mru']['included']) * 100)) 
                    : 0;
            @endphp
            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                <div class="h-2 rounded-full {{ $summary['quota_usage']['mru']['is_over_quota'] ? 'bg-rose-500' : 'bg-blue-600' }}" style="width: {{ $mruPct }}%"></div>
            </div>
            <div class="flex items-center justify-between text-[10px] text-slate-500">
                <span>Extra MRUs: {{ $summary['quota_usage']['mru']['extra'] }}</span>
                <span>Extra Charges: ₹{{ number_format($summary['quota_usage']['overage_charges']['mru_charges'], 2) }}</span>
            </div>
        </div>

        <!-- Consumer Quota -->
        <div class="p-4 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-slate-800 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Consumer Quota</span>
                <span class="text-xs font-mono font-bold text-slate-900 dark:text-white">
                    {{ number_format($summary['quota_usage']['consumer']['used']) }} / {{ number_format($summary['quota_usage']['consumer']['included']) }} Processed
                </span>
            </div>
            @php
                $csmPct = $summary['quota_usage']['consumer']['included'] > 0 
                    ? min(100, round(($summary['quota_usage']['consumer']['used'] / $summary['quota_usage']['consumer']['included']) * 100)) 
                    : 0;
            @endphp
            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                <div class="h-2 rounded-full {{ $summary['quota_usage']['consumer']['is_over_quota'] ? 'bg-rose-500' : 'bg-emerald-500' }}" style="width: {{ $csmPct }}%"></div>
            </div>
            <div class="flex items-center justify-between text-[10px] text-slate-500">
                <span>Extra Consumers: {{ number_format($summary['quota_usage']['consumer']['extra']) }}</span>
                <span>Extra Charges: ₹{{ number_format($summary['quota_usage']['overage_charges']['consumer_charges'], 2) }}</span>
            </div>
        </div>
    </div>
</div>
