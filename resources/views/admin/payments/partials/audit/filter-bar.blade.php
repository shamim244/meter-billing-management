<!-- Filter and Search Bar -->
<div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.payments.audit') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ empty($actionFilter) ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-slate-900 text-slate-400 hover:text-white' }}">
            All Events ({{ $auditLogs->total() }})
        </a>
        <a href="{{ route('admin.payments.audit', ['action' => 'approved']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $actionFilter === 'approved' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'bg-slate-900 text-slate-400 hover:text-white' }}">
            ✓ Approvals
        </a>
        <a href="{{ route('admin.payments.audit', ['action' => 'rejected']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $actionFilter === 'rejected' ? 'bg-rose-600 text-white shadow-md shadow-rose-600/30' : 'bg-slate-900 text-slate-400 hover:text-white' }}">
            ✕ Rejections
        </a>
        <a href="{{ route('admin.payments.audit', ['action' => 'refunded']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $actionFilter === 'refunded' ? 'bg-amber-600 text-white shadow-md shadow-amber-600/30' : 'bg-slate-900 text-slate-400 hover:text-white' }}">
            🔄 Refunds
        </a>
    </div>

    <form method="GET" action="{{ route('admin.payments.audit') }}" class="flex items-center gap-2 w-full sm:w-auto">
        <input type="hidden" name="action" value="{{ $actionFilter }}">
        <div class="relative w-full sm:w-64">
            <span class="absolute left-3 top-2.5 text-slate-500 text-xs">🔍</span>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search Admin, Agent, Notes..." class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl pl-8 pr-3 py-2 text-slate-200 placeholder-slate-500 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl transition">
            Filter
        </button>
    </form>
</div>
