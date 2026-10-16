{{-- 4 Forensic KPI Diagnostics Deck --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- 1. Net Reading Gap -->
    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs relative overflow-hidden">
        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
            <span class="text-xs font-bold uppercase tracking-wider">Net Discrepancy Gap</span>
            <span class="text-xl">⚖️</span>
        </div>
        <div class="text-2xl font-black font-mono {{ ($gapSummary['gap_status'] ?? '') === 'under_posted' ? 'text-rose-500' : (($gapSummary['gap_status'] ?? '') === 'over_posted' ? 'text-amber-500' : 'text-slate-900 dark:text-white') }}">
            {{ ($gapSummary['net_gap'] ?? 0) > 0 ? '+' : '' }}{{ number_format((int)($gapSummary['net_gap'] ?? 0)) }} <span class="text-xs font-normal text-slate-400">kWh</span>
        </div>
        <div class="text-xs text-slate-500 mt-1">
            @if(($gapSummary['gap_status'] ?? '') === 'under_posted')
                Physical meter is running ahead of billing.
            @elseif(($gapSummary['gap_status'] ?? '') === 'over_posted')
                Consumer was billed in advance of wall meter.
            @else
                Bill reading matches wall meter reading.
            @endif
        </div>
    </div>

    <!-- 2. Physical Inspection State -->
    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs relative overflow-hidden">
        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
            <span class="text-xs font-bold uppercase tracking-wider">Physical Meter State</span>
            <span class="text-xl">🔍</span>
        </div>
        <div class="text-2xl font-black font-mono text-cyan-500">
            {{ $account->last_physical_reading !== null ? number_format((int)$account->last_physical_reading) : 'N/A' }} <span class="text-xs font-normal text-slate-400">kWh</span>
        </div>
        <div class="text-xs text-slate-500 mt-1 flex items-center justify-between">
            <span>
                {{ $account->last_inspected_at ? $account->last_inspected_at->format('d M Y') : 'Never Inspected' }}
            </span>
            @if($gapSummary['is_stale'] ?? false)
                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-500 border border-rose-500/20">Stale (>90d)</span>
            @elseif($account->last_inspected_at)
                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">Fresh</span>
            @endif
        </div>
    </div>

    <!-- 3. Financial Exposure Estimate -->
    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs relative overflow-hidden">
        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
            <span class="text-xs font-bold uppercase tracking-wider">Financial Exposure</span>
            <span class="text-xl">💰</span>
        </div>
        <div class="text-2xl font-black font-mono text-slate-900 dark:text-white">
            ₹{{ number_format($financialExposure, 2) }}
        </div>
        <div class="text-xs text-slate-500 mt-1">
            @if(($gapSummary['gap_status'] ?? '') === 'under_posted')
                Estimated unbilled revenue pending recovery.
            @elseif(($gapSummary['gap_status'] ?? '') === 'over_posted')
                Estimated advance credit held by utility.
            @else
                Zero unbilled revenue exposure.
            @endif
        </div>
    </div>

    <!-- 4. Pacing Trajectory -->
    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs relative overflow-hidden">
        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
            <span class="text-xs font-bold uppercase tracking-wider">Recovery Plan</span>
            <span class="text-xl">📈</span>
        </div>
        <div class="text-2xl font-black font-mono text-indigo-500">
            @if(($gapSummary['gap_status'] ?? '') === 'under_posted')
                +{{ $gapSummary['gap_slice_applied'] ?? 0 }} <span class="text-xs font-normal text-slate-400">kWh/mo</span>
            @elseif(($gapSummary['gap_status'] ?? '') === 'over_posted')
                {{ ($account->gap_recovery_mode === 'hold_advance') ? '+1 Hold' : '+0 Hold' }}
            @else
                Normal Pacing
            @endif
        </div>
        <div class="text-xs text-slate-500 mt-1">
            @if(($gapSummary['gap_status'] ?? '') === 'under_posted')
                ~{{ $gapSummary['estimated_recovery_cycles'] ?? 0 }} cycles to zero discrepancy
            @elseif(($gapSummary['gap_status'] ?? '') === 'over_posted')
                ~{{ $gapSummary['estimated_catchup_cycles'] ?? 0 }} cycles to meter catch-up
            @else
                Standard consumption progression
            @endif
        </div>
    </div>
</div>
