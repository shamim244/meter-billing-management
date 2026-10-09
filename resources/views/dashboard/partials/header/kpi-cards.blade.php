{{-- Top KPI Cards Row (Total Consumers, Billing, Units, Submitted, Critical, Doubt) --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
    <!-- Total Consumers -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Consumers</span>
            <span class="text-lg">👥</span>
        </div>
        <div class="text-2xl font-black text-slate-900 dark:text-white mt-1" x-text="formatNumber(counts.total_consumers ?? {{ $totalConsumers }})">{{ number_format($totalConsumers) }}</div>
        <span class="text-[11px] text-slate-400 font-medium">Active accounts</span>
    </div>

    <!-- Total Billing -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Billing</span>
            <span class="text-lg">💰</span>
        </div>
        <div class="text-2xl font-black text-blue-600 dark:text-cyan-400 mt-1" x-text="'₹' + formatNumber(counts.filtered_amount ?? {{ $totalPeriodAmount }})">
            ₹{{ number_format($totalPeriodAmount) }}
        </div>
        <span class="text-[11px] text-slate-400 font-medium">Combined amount</span>
    </div>

    <!-- Total Units -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Units</span>
            <span class="text-lg">⚡</span>
        </div>
        <div class="text-2xl font-black text-slate-900 dark:text-white mt-1" x-text="formatNumber(counts.filtered_units ?? {{ $totalPeriodUnits }})">
            {{ number_format($totalPeriodUnits) }}
        </div>
        <span class="text-[11px] text-slate-400 font-medium">kWh consumed</span>
    </div>

    <!-- Submitted -->
    <div @click="filterStatus = (filterStatus === 'submitted' ? 'all' : 'submitted'); fetchData(1);" class="bg-emerald-50/70 dark:bg-emerald-950/30 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 cursor-pointer p-4 rounded-2xl border border-emerald-200 dark:border-emerald-800/60 shadow-sm transition">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">Submitted</span>
            <span>✅</span>
        </div>
        <div class="text-2xl font-black text-emerald-800 dark:text-emerald-200 mt-1" x-text="counts.submitted ?? 0">
            {{ $statusCounts['submitted'] }}
        </div>
        <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">Bills processed</span>
    </div>

    <!-- Critical -->
    <div @click="filterStatus = (filterStatus === 'critical' ? 'all' : 'critical'); fetchData(1);" class="bg-rose-50/70 dark:bg-rose-950/30 hover:bg-rose-50 dark:hover:bg-rose-950/50 cursor-pointer p-4 rounded-2xl border border-rose-200 dark:border-rose-800/60 shadow-sm transition">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-rose-700 dark:text-rose-300 uppercase tracking-wider">Critical</span>
            <span>❌</span>
        </div>
        <div class="text-2xl font-black text-rose-800 dark:text-rose-200 mt-1" x-text="counts.critical ?? 0">
            {{ $statusCounts['critical'] }}
        </div>
        <span class="text-[11px] text-rose-600 dark:text-rose-400 font-medium">Cannot submit</span>
    </div>

    <!-- Doubt -->
    <div @click="filterStatus = (filterStatus === 'doubt' ? 'all' : 'doubt'); fetchData(1);" class="bg-amber-50/70 dark:bg-amber-950/30 hover:bg-amber-50 dark:hover:bg-amber-950/50 cursor-pointer p-4 rounded-2xl border border-amber-200 dark:border-amber-800/60 shadow-sm transition">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-amber-700 dark:text-amber-300 uppercase tracking-wider">Doubt</span>
            <span>⚠️</span>
        </div>
        <div class="text-2xl font-black text-amber-800 dark:text-amber-200 mt-1" x-text="counts.doubt ?? 0">
            {{ $statusCounts['doubt'] }}
        </div>
        <span class="text-[11px] text-amber-600 dark:text-amber-400 font-medium">Review later</span>
    </div>
</div>
