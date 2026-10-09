{{-- Filter & Search Bar for Ledger --}}
<div class="bg-slate-950 p-4 rounded-2xl border border-slate-800">
    <form method="GET" action="{{ route('admin.wallets.show', $user->id) }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3 text-xs">
        <div>
            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Type</label>
            <select name="type" class="w-full text-xs rounded-xl bg-slate-900 border-slate-800 text-white p-2.5 focus:ring-indigo-500">
                <option value="">All Types</option>
                <option value="credit" {{ ($filters['type'] ?? '') === 'credit' ? 'selected' : '' }}>🟢 Credits Only</option>
                <option value="debit" {{ ($filters['type'] ?? '') === 'debit' ? 'selected' : '' }}>🔴 Debits Only</option>
            </select>
        </div>

        <div>
            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Source</label>
            <select name="source" class="w-full text-xs rounded-xl bg-slate-900 border-slate-800 text-white p-2.5 focus:ring-indigo-500">
                <option value="">All Sources</option>
                <option value="payment_topup" {{ ($filters['source'] ?? '') === 'payment_topup' ? 'selected' : '' }}>Payment Top-Up</option>
                <option value="admin_adjustment" {{ ($filters['source'] ?? '') === 'admin_adjustment' ? 'selected' : '' }}>Admin Adjustment</option>
            </select>
        </div>

        <div>
            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">From Date</label>
            <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="w-full text-xs rounded-xl bg-slate-900 border-slate-800 text-white p-2.5 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Search</label>
            <input type="text" name="search" placeholder="Search ref or description..." value="{{ $filters['search'] ?? '' }}" class="w-full text-xs rounded-xl bg-slate-900 border-slate-800 text-white p-2.5 focus:ring-indigo-500">
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition text-xs shadow-md shadow-indigo-600/30">
                Filter
            </button>
            <a href="{{ route('admin.wallets.show', $user->id) }}" class="p-2.5 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white rounded-xl transition text-xs font-bold">
                Reset
            </a>
        </div>
    </form>
</div>
