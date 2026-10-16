<!-- Reading Gap KPI Summary Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Unbilled Gap Volume -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs relative overflow-hidden">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">⚡ Unbilled Volume</span>
            <span class="p-1.5 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 text-sm">📈</span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-2xl font-black text-rose-600 dark:text-rose-400" x-text="(kpis.total_unbilled_kwh || 0).toLocaleString()">0</span>
            <span class="text-xs font-semibold text-slate-400">kWh</span>
        </div>
        <div class="text-[11px] text-slate-400 mt-1" x-text="(kpis.under_posted_count || 0) + ' under-posted meters'">0 under-posted</div>
    </div>

    <!-- Estimated Financial Risk -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs relative overflow-hidden">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">💰 Est. Unbilled Revenue</span>
            <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-sm">₹</span>
        </div>
        <div class="mt-2 flex items-baseline gap-1">
            <span class="text-xs font-bold text-slate-400">₹</span>
            <span class="text-2xl font-black text-slate-900 dark:text-white" x-text="Math.round(kpis.estimated_financial_gap_rs || 0).toLocaleString()">0</span>
        </div>
        <div class="text-[11px] text-slate-400 mt-1">At risk until recovery billed</div>
    </div>

    <!-- Over-Posted (Advance / Advance units) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs relative overflow-hidden">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">⚠️ Over-Posted Advance</span>
            <span class="p-1.5 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 text-sm">🛡️</span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-2xl font-black text-amber-600 dark:text-amber-400" x-text="(kpis.over_posted_count || 0).toLocaleString()">0</span>
            <span class="text-xs font-semibold text-slate-400">meters</span>
        </div>
        <div class="text-[11px] text-slate-400 mt-1" x-text="(kpis.total_over_posted_kwh || 0).toLocaleString() + ' advance kWh held'">0 kWh held</div>
    </div>

    <!-- Stale Inspections (>90 days) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs relative overflow-hidden">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">⏱️ Stale Visits (>90d)</span>
            <span class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-sm">🚶</span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400" x-text="(kpis.stale_inspections_count || 0).toLocaleString()">0</span>
            <span class="text-xs font-semibold text-slate-400">consumers</span>
        </div>
        <div class="text-[11px] text-slate-400 mt-1">Need fresh physical verification</div>
    </div>
</div>
