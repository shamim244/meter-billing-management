<div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
    <form method="GET" action="{{ route('reports.status_tag') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
        <!-- Month -->
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Month</label>
            <select name="month" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2 px-3 focus:ring-blue-500">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                @endfor
            </select>
        </div>

        <!-- Year -->
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Year</label>
            <select name="year" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2 px-3 focus:ring-blue-500">
                @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>

        <!-- MRU Filter -->
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">MRU Workspace</label>
            <select name="mru_id" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2 px-3 focus:ring-blue-500">
                <option value="">All MRUs</option>
                @foreach($mrus as $mru)
                    <option value="{{ $mru->id }}" {{ $mruId === $mru->id ? 'selected' : '' }}>{{ $mru->code }} - {{ $mru->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Status Filter -->
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Review Status</label>
            <select name="status" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2 px-3 focus:ring-blue-500">
                <option value="all">All Statuses</option>
                <option value="submitted" {{ $status === 'submitted' ? 'selected' : '' }}>✅ Submitted</option>
                <option value="critical" {{ $status === 'critical' ? 'selected' : '' }}>❌ Critical</option>
                <option value="doubt" {{ $status === 'doubt' ? 'selected' : '' }}>⚠️ Doubt</option>
                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>⏳ Pending</option>
            </select>
        </div>

        <!-- Tag Filter -->
        <div class="flex items-end gap-2">
            <div class="flex-1">
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tag</label>
                <select name="tag" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2 px-3 focus:ring-blue-500">
                    <option value="all">All Tags</option>
                    @foreach($tagBreakdown['tags'] as $t)
                        <option value="{{ $t['code'] }}" {{ strtoupper($tag) === strtoupper($t['code']) ? 'selected' : '' }}>
                            🏷️ {{ $t['short_label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition">
                Filter
            </button>
        </div>
    </form>
</div>
