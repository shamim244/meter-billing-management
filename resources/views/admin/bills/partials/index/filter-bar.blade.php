<!-- Filter Toolbar -->
<form method="GET" action="{{ route('admin.bills.index') }}" class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Tenant Filter -->
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Tenant User</label>
            <select name="user_id" class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-indigo-500">
                <option value="">All Tenants</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
        </div>

        <!-- MRU Filter -->
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">MRU / Area</label>
            <select name="mru_id" class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-indigo-500">
                <option value="">All MRUs</option>
                @foreach($mrus as $m)
                    <option value="{{ $m->id }}" {{ $mruId == $m->id ? 'selected' : '' }}>{{ $m->code }} - {{ $m->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Billing Month -->
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Month</label>
            <select name="month" class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-indigo-500">
                <option value="">All Months</option>
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                @endfor
            </select>
        </div>

        <!-- Billing Year -->
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Year</label>
            <select name="year" class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-indigo-500">
                <option value="">All Years</option>
                @php
                    $currYear = (int) date('Y');
                    $billYears = range(max(2020, $currYear - 4), $currYear + 3);
                    rsort($billYears);
                @endphp
                @foreach($billYears as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <!-- Search Input -->
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Search CA / Name</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search..." class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:ring-indigo-500">
        </div>
    </div>

    <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 pt-2 border-t border-slate-900">
        <a href="{{ route('admin.bills.index') }}" class="w-full sm:w-auto px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:bg-slate-900 transition text-center">Reset</a>
        <button type="submit" class="w-full sm:w-auto px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow transition text-center">Apply Filters</button>
    </div>
</form>
