<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
            <span>📋</span> Subscription Plans Management
        </h1>
        <p class="text-sm text-slate-400 mt-1">Configure pricing tiers, included quotas, duration discounts, and overage rates.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.plans.overage_charges') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition border border-slate-700/60 flex items-center gap-2">
            <span>⚡</span> Overage Audit Logs
        </a>
        <a href="{{ route('admin.plans.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30 flex items-center gap-2">
            <span>➕</span> Create New Plan
        </a>
    </div>
</div>
