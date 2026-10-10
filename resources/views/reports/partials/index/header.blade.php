<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
            <span>📊</span> Monthly Usage & ROI Overview
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Monthly operational summary, data coverage, ledger history, and quota intelligence for <strong class="text-slate-800 dark:text-slate-200">{{ $summary['period_label'] }}</strong>.
        </p>
    </div>

    <!-- Month/Year Filter Form -->
    <form method="GET" action="{{ route('reports.usage') }}" class="flex items-center gap-2">
        <select name="month" class="text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white py-2 px-3 focus:ring-blue-500">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
            @endfor
        </select>
        <select name="year" class="text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white py-2 px-3 focus:ring-blue-500">
            @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition shadow-md shadow-blue-600/20">
            Filter
        </button>
    </form>
</div>
