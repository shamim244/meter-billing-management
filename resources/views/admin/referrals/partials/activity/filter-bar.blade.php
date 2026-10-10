<!-- Filter Controls -->
<div class="glass-card rounded-2xl p-4 border border-slate-800/80">
    <form action="{{ route('admin.referrals.activity') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search agent, referee, code..." class="w-full px-3.5 py-2 rounded-xl bg-slate-900/80 border border-slate-700 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500">
        </div>
        <div>
            <select name="status" class="w-full px-3.5 py-2 rounded-xl bg-slate-900/80 border border-slate-700 text-xs text-white focus:outline-none focus:border-purple-500">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>⏳ Pending Hold</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>✅ Paid to Wallet</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>🚫 Cancelled</option>
                <option value="clawed_back" {{ request('status') === 'clawed_back' ? 'selected' : '' }}>↩️ Clawed Back</option>
            </select>
        </div>
        <div>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-3.5 py-2 rounded-xl bg-slate-900/80 border border-slate-700 text-xs text-white focus:outline-none focus:border-purple-500">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition">Filter</button>
            @if(request()->anyFilled(['search', 'status', 'start_date', 'referrer_id']))
                <a href="{{ route('admin.referrals.activity') }}" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white text-xs font-semibold transition">Reset</a>
            @endif
        </div>
    </form>
</div>
