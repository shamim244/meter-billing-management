<!-- Top Navigation & Action -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
    <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
            <span>💳</span> Payment & Balance Ledger
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Track your wallet top-ups, subscription payments, and proof submissions.
        </p>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ route('payments.create') }}" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center gap-2">
            <span>⚡</span> Make a Payment / Top-Up
        </a>
    </div>
</div>
