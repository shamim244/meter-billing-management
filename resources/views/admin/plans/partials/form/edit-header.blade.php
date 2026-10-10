<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <a href="{{ route('admin.plans.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1 mb-2">
            ← Back to Plans
        </a>
        <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
            <span>✏️</span> Edit Subscription Plan: {{ $plan->name }}
        </h1>
        <p class="text-sm text-slate-400 mt-1">Modify plan parameters, quotas, and duration pricing options.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.plans.durations.index', $plan) }}" class="px-4 py-2 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 rounded-xl text-xs font-bold transition border border-indigo-500/30 flex items-center gap-1.5 shadow-sm">
            <span>⏳</span> Dedicated Durations Console ({{ $plan->durations->count() }})
        </a>
        <a href="{{ route('admin.plans.agents', $plan) }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition border border-slate-700/60 flex items-center gap-1.5">
            <span>👥</span> Subscribers ({{ $plan->subscriptions()->where('status', 'active')->where('billing_end', '>', now())->count() }})
        </a>
    </div>
</div>
