<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
            <span>📊</span> Platform Usage & Account Health
        </h1>
        <p class="text-sm text-slate-400 mt-1">
            System-wide operational volume, data coverage, consecutive estimate alerts, and overage revenue.
        </p>
    </div>

    <!-- Month/Year Filter -->
    <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-center gap-2">
        <select name="month" class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
            @endfor
        </select>
        <select name="year" class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500">
            @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition">
            Filter
        </button>
    </form>
</div>
