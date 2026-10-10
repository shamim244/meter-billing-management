<div class="bg-slate-950 p-4 rounded-3xl border border-slate-800 shadow-lg flex flex-col lg:flex-row lg:items-center justify-between gap-3">
    <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-center gap-2 flex-1">
        <div class="flex-1 min-w-[180px]">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, email, or phone..." class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl px-3.5 py-2 text-white placeholder-slate-500 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <select name="role" class="text-xs bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-white focus:ring-indigo-500">
            <option value="all" {{ $roleFilter === 'all' ? 'selected' : '' }}>All Roles</option>
            <option value="admin" {{ $roleFilter === 'admin' ? 'selected' : '' }}>Administrators</option>
            <option value="user" {{ $roleFilter === 'user' ? 'selected' : '' }}>Billing Operators</option>
        </select>

        <select name="status" class="text-xs bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-white focus:ring-indigo-500">
            <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Statuses</option>
            <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active Only</option>
            <option value="suspended" {{ $statusFilter === 'suspended' ? 'selected' : '' }}>Suspended Only</option>
        </select>

        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition shrink-0">
            Filter
        </button>

        @if(!empty($search) || $roleFilter !== 'all' || $statusFilter !== 'all')
            <a href="{{ route('admin.users.index') }}" class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-slate-400 rounded-xl text-xs font-medium transition shrink-0">
                Clear
            </a>
        @endif
    </form>

    <div class="flex items-center gap-2 shrink-0 flex-wrap">
        <!-- Export to CSV Button -->
        <a href="{{ route('admin.users.export', request()->query()) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 hover:border-slate-600 rounded-xl text-xs font-bold transition">
            <span>📥</span> Export CSV
        </a>

        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/20 transition text-center shrink-0">
            <span>+</span> Add New User
        </a>
    </div>
</div>
