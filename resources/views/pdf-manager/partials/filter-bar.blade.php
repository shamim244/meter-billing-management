<!-- Filter & Search Toolbar (Auto-Apply on Select) -->
<div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Filter & Search</span>
            <span class="text-[10px] text-brand-600 dark:text-brand-400 font-semibold bg-brand-50 dark:bg-brand-950 px-2 py-0.5 rounded-full border border-brand-200 dark:border-brand-800">
                ⚡ Instant Auto-Filter
            </span>
        </div>

        @if(!empty($mruId) || !empty($month) || !empty($year) || $status !== 'all' || !empty($search))
            <a href="{{ route('pdf-manager.index', ['mru_id' => '', 'month' => '', 'year' => '', 'status' => 'all', 'search' => '']) }}" 
               class="text-xs text-rose-500 hover:text-rose-600 font-semibold transition flex items-center gap-1">
                <span>✕</span>
                <span>Clear All Filters</span>
            </a>
        @endif
    </div>

    <form method="GET" action="{{ route('pdf-manager.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <input type="hidden" name="view" :value="viewMode">

        <!-- MRU Selector -->
        <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">MRU Workspace</label>
            <select name="mru_id" onchange="this.form.submit()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-brand-500">
                <option value="">All MRU Areas</option>
                @foreach($mrus as $m)
                    <option value="{{ $m->id }}" {{ $mruId == $m->id ? 'selected' : '' }}>
                        {{ $m->code }} - {{ $m->name }} @if($m->bill_records_count > 0)({{ $m->bill_records_count }})@endif
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Month Selector -->
        <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Billing Month</label>
            <select name="month" onchange="this.form.submit()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-brand-500">
                <option value="">All Months</option>
                @for($m = 1; $m <= 12; $m++)
                    @php $cnt = $availableMonths[$m] ?? 0; @endphp
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ date('F', mktime(0, 0, 0, $m, 1)) }} @if($cnt > 0)({{ $cnt }})@endif
                    </option>
                @endfor
            </select>
        </div>

        <!-- Year Selector -->
        <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Billing Year</label>
            <select name="year" onchange="this.form.submit()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-brand-500">
                <option value="">All Years</option>
                @php
                    $cYear = (int)date('Y');
                    $yearsList = range(max(2020, $cYear - 4), $cYear + 2);
                    rsort($yearsList);
                @endphp
                @foreach($yearsList as $y)
                    @php $ycnt = $availableYears[$y] ?? 0; @endphp
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                        {{ $y }} @if($ycnt > 0)({{ $ycnt }})@endif
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Status Filter -->
        <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">PDF Status</label>
            <select name="status" onchange="this.form.submit()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-brand-500">
                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                <option value="downloaded" {{ $status === 'downloaded' ? 'selected' : '' }}>Downloaded Only</option>
                <option value="missing" {{ $status === 'missing' ? 'selected' : '' }}>Missing on Disk</option>
                <option value="parsed" {{ $status === 'parsed' ? 'selected' : '' }}>Parsed & Extracted</option>
                <option value="unparsed" {{ $status === 'unparsed' ? 'selected' : '' }}>Unparsed</option>
                <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Parse Failed</option>
            </select>
        </div>

        <!-- Search -->
        <div class="sm:col-span-2">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Search CA, Name, Meter, File</label>
            <div class="flex items-center gap-2">
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Type CA or filename to filter..." 
                       @keydown.enter="$el.form.submit()"
                       class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-brand-500">
                <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold transition shrink-0 shadow-sm">
                    🔍
                </button>
            </div>
        </div>
    </form>
</div>
