<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
            <span>💳</span> Quota Usage & Overage Leaderboard
        </h1>
        <p class="text-sm text-slate-400 mt-1">
            Identify high-volume Billing Agents and upsell candidates exceeding included subscription quotas.
        </p>
    </div>

    <!-- Month & Sorting Filter -->
    <form method="GET" action="{{ route('admin.reports.quota') }}" class="flex flex-wrap items-center gap-2">
        <select name="sort_by" class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500">
            <option value="overage_spend" {{ $sortBy === 'overage_spend' ? 'selected' : '' }}>Sort: Highest Overage Spend</option>
            <option value="consumer_usage" {{ $sortBy === 'consumer_usage' ? 'selected' : '' }}>Sort: Most Consumers Processed</option>
            <option value="mru_usage" {{ $sortBy === 'mru_usage' ? 'selected' : '' }}>Sort: Most Active MRUs</option>
            <option value="name" {{ $sortBy === 'name' ? 'selected' : '' }}>Sort: Agent Name (A-Z)</option>
        </select>
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
