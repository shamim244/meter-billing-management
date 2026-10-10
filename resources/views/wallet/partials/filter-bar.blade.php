<form method="GET" action="{{ route('wallet.index') }}" class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-wrap items-center gap-3">
    <div class="flex-1 min-w-[180px]">
        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search description, reference ID..." class="w-full text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3 py-2">
    </div>

    <div>
        <select name="type" class="text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3 py-2">
            <option value="">All Types</option>
            <option value="credit" {{ ($filters['type'] ?? '') === 'credit' ? 'selected' : '' }}>Credits (+)</option>
            <option value="debit" {{ ($filters['type'] ?? '') === 'debit' ? 'selected' : '' }}>Debits (−)</option>
        </select>
    </div>

    <div>
        <select name="source" class="text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3 py-2">
            <option value="">All Sources</option>
            <option value="payment_topup" {{ ($filters['source'] ?? '') === 'payment_topup' ? 'selected' : '' }}>Payment Top-up</option>
            <option value="admin_adjustment" {{ ($filters['source'] ?? '') === 'admin_adjustment' ? 'selected' : '' }}>Admin Adjustment</option>
            <option value="bill_download_fee" {{ ($filters['source'] ?? '') === 'bill_download_fee' ? 'selected' : '' }}>Bill Download Fee</option>
            <option value="subscription_fee" {{ ($filters['source'] ?? '') === 'subscription_fee' ? 'selected' : '' }}>Subscription Fee</option>
        </select>
    </div>

    <div>
        <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3 py-2" title="From Date">
    </div>

    <div>
        <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3 py-2" title="To Date">
    </div>

    <div class="flex items-center gap-2">
        <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition">
            Filter
        </button>
        @if(!empty(array_filter($filters ?? [])))
            <a href="{{ route('wallet.index') }}" class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                Reset
            </a>
        @endif
    </div>
</form>
