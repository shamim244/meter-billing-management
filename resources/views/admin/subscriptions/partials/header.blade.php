<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
            <span>🔄</span> Subscriptions & Lifecycle State Machine
        </h1>
        <p class="text-sm text-slate-400 mt-1">Manage Agent subscription lifecycles, grace periods, manual overrides, and read-only suspension rules.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.subscriptions.renewal_attempts') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition border border-slate-700/60 flex items-center gap-2">
            <span>🔁</span> Renewal Attempts
        </a>
        <a href="{{ route('admin.subscriptions.upgrade_logs') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition border border-slate-700/60 flex items-center gap-2">
            <span>⚖️</span> Proration Audit Logs
        </a>
    </div>
</div>
