<!-- Current Month Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Active Plan -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-2">
        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Subscribed Plan</span>
        <div class="text-xl font-black text-slate-900 dark:text-white">
            {{ $quotaUsage['subscription']['plan_name'] ?? 'No Active Subscription' }}
        </div>
        <div class="text-xs text-slate-500 flex items-center gap-2">
            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                {{ $quotaUsage['subscription']['status'] ?? 'N/A' }}
            </span>
            @if(!empty($quotaUsage['subscription']['expires_at']))
                <span>Expires: {{ $quotaUsage['subscription']['expires_at'] }}</span>
            @endif
        </div>
    </div>

    <!-- MRU Quota Usage -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-2">
        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">MRU Quota (Current)</span>
        <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 font-mono">
            {{ $quotaUsage['mru']['used'] }} <span class="text-sm font-normal text-slate-400">/ {{ $quotaUsage['mru']['included'] }} included</span>
        </div>
        <div class="text-xs text-slate-500">
            Extra MRUs: <strong class="text-slate-800 dark:text-slate-200">{{ $quotaUsage['mru']['extra'] }}</strong> (₹{{ number_format($quotaUsage['overage_charges']['mru_charges'], 2) }})
        </div>
    </div>

    <!-- Consumer Quota Usage -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-2">
        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Consumer Quota ({{ date('M Y', mktime(0,0,0,$month,1,$year)) }})</span>
        <div class="text-2xl font-black text-blue-600 dark:text-cyan-400 font-mono">
            {{ number_format($quotaUsage['consumer']['used']) }} <span class="text-sm font-normal text-slate-400">/ {{ number_format($quotaUsage['consumer']['included']) }} included</span>
        </div>
        <div class="text-xs text-slate-500">
            Extra Consumers: <strong class="text-slate-800 dark:text-slate-200">{{ number_format($quotaUsage['consumer']['extra']) }}</strong> (₹{{ number_format($quotaUsage['overage_charges']['consumer_charges'], 2) }})
        </div>
    </div>
</div>
