<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
    <div>
        <h2 class="text-base font-bold text-white">🗂️ Backup Archives Ledger</h2>
        <p class="text-xs text-slate-400">All available restore points and historical system dumps.</p>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.backups.index') }}" class="flex items-center gap-2">
        <select name="type" onchange="this.form.submit()" class="bg-slate-950 border border-slate-700 text-slate-300 text-xs rounded-xl px-3 py-1.5 font-semibold focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">All Types</option>
            <option value="db_only" {{ request('type') == 'db_only' ? 'selected' : '' }}>Database Only</option>
            <option value="storage_only" {{ request('type') == 'storage_only' ? 'selected' : '' }}>PDF Storage</option>
            <option value="full" {{ request('type') == 'full' ? 'selected' : '' }}>Full Snapshot</option>
        </select>

        <select name="status" onchange="this.form.submit()" class="bg-slate-950 border border-slate-700 text-slate-300 text-xs rounded-xl px-3 py-1.5 font-semibold focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">All Statuses</option>
            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
        </select>
    </form>
</div>
