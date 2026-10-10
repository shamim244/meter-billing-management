<!-- Filter & Search Bar -->
<div class="bg-slate-950 p-4 rounded-2xl border border-slate-800">
    <form method="GET" action="{{ route('admin.wallets.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
        <div class="sm:col-span-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by agent name, email, or phone..." class="w-full text-xs rounded-xl bg-slate-900 border-slate-800 text-white placeholder-slate-500 p-2.5 focus:ring-indigo-500">
        </div>

        <div>
            <select name="status" class="w-full text-xs rounded-xl bg-slate-900 border-slate-800 text-white p-2.5 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>🟢 Active (Normal)</option>
                <option value="frozen" {{ request('status') === 'frozen' ? 'selected' : '' }}>🔒 Frozen Wallets</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition text-xs shadow-md shadow-indigo-600/30">
                Filter
            </button>
            <a href="{{ route('admin.wallets.index') }}" class="p-2.5 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white rounded-xl transition text-xs font-bold">
                Reset
            </a>
        </div>
    </form>
</div>
