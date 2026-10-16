{{-- 4 Metric KPI Overview Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Lifetime Billed -->
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-semibold mb-1">
            <span>Lifetime Billed</span>
            <span class="text-emerald-500">💰</span>
        </div>
        <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono">
            ₹{{ number_format($stats['total_billed_amount'], 2) }}
        </div>
        <div class="text-[11px] text-slate-400 mt-0.5">
            Across {{ $stats['total_bills'] }} billing cycles
        </div>
    </div>

    <!-- Lifetime Units Consumed -->
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-semibold mb-1">
            <span>Lifetime Energy</span>
            <span class="text-blue-500">⚡</span>
        </div>
        <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono">
            {{ number_format($stats['total_units']) }} <span class="text-xs font-normal text-slate-400">kWh</span>
        </div>
        <div class="text-[11px] text-slate-400 mt-0.5">
            Avg {{ $stats['avg_monthly_units'] }} kWh / month
        </div>
    </div>

    <!-- Reading Gap Status -->
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-semibold mb-1">
            <span>Reading Reconciliation</span>
            <span>⚖️</span>
        </div>
        <div class="text-lg sm:text-xl font-black font-mono"
             :class="{
                 'text-amber-500': '{{ $gapSummary['gap_status'] }}' === 'over_posted',
                 'text-rose-500': '{{ $gapSummary['gap_status'] }}' === 'under_posted',
                 'text-emerald-500': '{{ $gapSummary['gap_status'] }}' === 'in_sync',
                 'text-slate-400': '{{ $gapSummary['gap_status'] }}' === 'uninspected'
             }">
            {{ $gapSummary['badge_label'] }}
        </div>
        <div class="text-[11px] text-slate-400 mt-0.5">
            Physical: {{ $account->last_physical_reading ?? 'None' }} • {{ $stats['inspections_count'] }} visits
        </div>
    </div>

    <!-- FieldDesk / Follow-ups -->
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-semibold mb-1">
            <span>Field Commitments</span>
            <span class="text-amber-500">📋</span>
        </div>
        <div class="text-xl sm:text-2xl font-black font-mono {{ $stats['active_commitments_count'] > 0 ? 'text-amber-500' : 'text-slate-900 dark:text-white' }}">
            {{ $stats['active_commitments_count'] }} <span class="text-xs font-normal text-slate-400">active</span>
        </div>
        <div class="text-[11px] text-slate-400 mt-0.5">
            {{ $fieldDeskActions->count() }} total field actions logged
        </div>
    </div>
</div>
