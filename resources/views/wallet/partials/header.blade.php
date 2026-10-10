<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
            <span>👛</span> Agent Wallet & Financial Ledger
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Prepaid balance for automated NBPDCL bill downloading and meter processing tasks.
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
        <a href="{{ route('wallet.export') }}" class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 transition flex items-center gap-1.5 shadow-sm">
            <span>📥</span> Export CSV Ledger
        </a>
        <a href="{{ route('payments.create', ['purpose' => 'wallet_topup']) }}" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-500/20 transition flex items-center gap-1.5">
            <span>⚡</span> + Add Funds / Top-Up
        </a>
    </div>
</div>
