<div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-3">
    <form method="GET" action="{{ route('admin.issues.index') }}" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
        <input type="hidden" name="status" value="{{ $status }}">

        <!-- Search -->
        <div class="relative min-w-[220px]">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 text-xs">🔍</span>
            <input type="text"
                   name="search"
                   value="{{ $search }}"
                   placeholder="Search title, CA, user, code..."
                   class="w-full text-xs bg-slate-900 border border-slate-800 rounded-xl pl-8 pr-3 py-2 text-white placeholder-slate-500 focus:ring-1 focus:ring-indigo-500">
        </div>

        <!-- Severity Filter -->
        <select name="severity" onchange="this.form.submit()" class="text-xs bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-slate-300 focus:ring-1 focus:ring-indigo-500">
            <option value="all" {{ $severity === 'all' ? 'selected' : '' }}>All Severities</option>
            <option value="critical" {{ $severity === 'critical' ? 'selected' : '' }}>🔴 Critical</option>
            <option value="high" {{ $severity === 'high' ? 'selected' : '' }}>🟠 High</option>
            <option value="medium" {{ $severity === 'medium' ? 'selected' : '' }}>🟡 Medium</option>
            <option value="low" {{ $severity === 'low' ? 'selected' : '' }}>🟢 Low</option>
        </select>

        <!-- Category Filter -->
        <select name="category" onchange="this.form.submit()" class="text-xs bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-slate-300 focus:ring-1 focus:ring-indigo-500">
            <option value="all" {{ $category === 'all' ? 'selected' : '' }}>All Categories</option>
            <option value="calculation" {{ $category === 'calculation' ? 'selected' : '' }}>⚡ Calculation</option>
            <option value="bill_download" {{ $category === 'bill_download' ? 'selected' : '' }}>📑 Bill Download</option>
            <option value="mru_sync" {{ $category === 'mru_sync' ? 'selected' : '' }}>🗂️ MRU / Cycles</option>
            <option value="ui_display" {{ $category === 'ui_display' ? 'selected' : '' }}>🖥️ UI / Display</option>
            <option value="wallet_payment" {{ $category === 'wallet_payment' ? 'selected' : '' }}>👛 Wallet / Pay</option>
            <option value="other" {{ $category === 'other' ? 'selected' : '' }}>❓ Other</option>
        </select>

        <button type="submit" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition">
            Filter
        </button>

        @if($status !== 'all' || $severity !== 'all' || $category !== 'all' || !empty($search))
            <a href="{{ route('admin.issues.index') }}" class="text-xs text-slate-400 hover:text-white underline">Reset</a>
        @endif
    </form>

    <div class="text-xs text-slate-500 font-mono">
        Showing {{ $issues->firstItem() ?? 0 }}–{{ $issues->lastItem() ?? 0 }} of {{ $issues->total() }}
    </div>
</div>
