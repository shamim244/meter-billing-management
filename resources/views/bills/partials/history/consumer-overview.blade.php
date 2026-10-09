{{-- Consumer Overview Card --}}
<div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
    <div>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-50 dark:bg-blue-900/60 text-blue-700 dark:text-cyan-300 border border-blue-100 dark:border-blue-800/60">
                {{ $account->mru ? $account->mru->code : 'GENERAL' }}
            </span>
            <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                Tariff: {{ $account->tariff_category ?: 'DS-II' }}
            </span>
            <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                Basis: {{ $account->billing_basis ?: 'OK' }}
            </span>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-2">
            {{ $account->consumer_name ?: 'Consumer Account' }}
        </h1>
        <p class="text-xs sm:text-sm font-mono text-slate-500 dark:text-slate-400 mt-1">
            CA Number: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $account->ca_number }}</span>
        </p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 border-t lg:border-t-0 lg:border-l border-slate-100 dark:border-slate-800 pt-3 lg:pt-0 lg:pl-6">
        <div>
            <span class="text-[10px] sm:text-xs text-slate-400 dark:text-slate-500 uppercase font-semibold">Total Bills</span>
            <div class="text-lg sm:text-xl font-black text-slate-900 dark:text-white font-mono">{{ $bills->count() }}</div>
        </div>
        <div>
            <span class="text-[10px] sm:text-xs text-slate-400 dark:text-slate-500 uppercase font-semibold">Total Billed</span>
            <div class="text-lg sm:text-xl font-black text-blue-600 dark:text-cyan-400 font-mono">₹{{ number_format($bills->sum('total_amount'), 2) }}</div>
        </div>
        <div>
            <span class="text-[10px] sm:text-xs text-slate-400 dark:text-slate-500 uppercase font-semibold">Baseline Amt</span>
            <div class="text-lg sm:text-xl font-black text-emerald-600 dark:text-emerald-400 font-mono">₹{{ number_format((float)($account->baseline_amount ?: 0), 2) }}</div>
        </div>
        <div>
            <span class="text-[10px] sm:text-xs text-slate-400 dark:text-slate-500 uppercase font-semibold">Initial Reading</span>
            <div class="text-lg sm:text-xl font-black text-indigo-600 dark:text-indigo-400 font-mono">{{ $account->baseline_previous_reading !== null ? $account->baseline_previous_reading : ($account->last_working_reading !== null ? $account->last_working_reading : '—') }}</div>
        </div>
    </div>
</div>
