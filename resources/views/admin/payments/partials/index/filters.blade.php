{{-- Filter & Search Bar --}}
<div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    {{-- Queue Tabs --}}
    <div class="flex flex-wrap items-center gap-1.5 p-1 bg-slate-900 rounded-xl border border-slate-800">
        <a href="{{ route('admin.payments.index', ['status' => 'pending_verification', 'mode' => $modeFilter, 'search' => $search]) }}" 
           class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $statusFilter === 'pending_verification' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 shadow-sm' : 'text-slate-400 hover:text-white' }}">
            <span>⏳ Pending Queue</span>
            @if($pendingCount > 0)
                <span class="px-1.5 py-0.5 rounded-md bg-amber-500 text-slate-950 text-[10px] font-extrabold">{{ $pendingCount }}</span>
            @endif
        </a>
        <a href="{{ route('admin.payments.index', ['status' => 'all', 'mode' => $modeFilter, 'search' => $search]) }}" 
           class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $statusFilter === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white' }}">
            All Transactions
        </a>
        <a href="{{ route('admin.payments.index', ['status' => 'success', 'mode' => $modeFilter, 'search' => $search]) }}" 
           class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $statusFilter === 'success' ? 'bg-emerald-600/20 text-emerald-300 border border-emerald-500/40 shadow-sm' : 'text-slate-400 hover:text-white' }}">
            Successful
        </a>
        <a href="{{ route('admin.payments.index', ['status' => 'rejected', 'mode' => $modeFilter, 'search' => $search]) }}" 
           class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $statusFilter === 'rejected' ? 'bg-rose-600/20 text-rose-300 border border-rose-500/40 shadow-sm' : 'text-slate-400 hover:text-white' }}">
            Rejected
        </a>
    </div>

    {{-- Mode & Search Filters --}}
    <form method="GET" action="{{ route('admin.payments.index') }}" class="flex flex-wrap items-center gap-2">
        <input type="hidden" name="status" value="{{ $statusFilter }}">

        <select name="mode" onchange="this.form.submit()" class="text-xs font-semibold bg-slate-900 border-slate-800 rounded-xl text-slate-200 py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">All Modes</option>
            <option value="pg" {{ $modeFilter === 'pg' ? 'selected' : '' }}>⚡ PG (Online)</option>
            <option value="manual_upi" {{ $modeFilter === 'manual_upi' ? 'selected' : '' }}>📱 Manual UPI</option>
            <option value="bank_transfer" {{ $modeFilter === 'bank_transfer' ? 'selected' : '' }}>🏦 Bank Transfer</option>
        </select>

        <div class="relative">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search UTR, Ref, Agent..." class="text-xs bg-slate-900 border-slate-800 rounded-xl text-slate-200 py-2 pl-8 pr-4 focus:ring-indigo-500 focus:border-indigo-500 w-48 sm:w-64 placeholder-slate-500">
            <svg class="w-3.5 h-3.5 text-slate-500 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>

        <button type="submit" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition">
            Filter
        </button>
    </form>
</div>
