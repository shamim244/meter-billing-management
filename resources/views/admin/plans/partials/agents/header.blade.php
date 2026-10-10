<!-- Header -->
<div class="flex items-center justify-between">
    <div>
        <a href="{{ route('admin.plans.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1 mb-2">
            ← Back to Plans
        </a>
        <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
            <span>👥</span> Subscribed Agents — {{ $plan->name }}
        </h1>
        <p class="text-sm text-slate-400 mt-1">View locked subscriber snapshots and perform plan migrations.</p>
    </div>
</div>

@if(session('success'))
    <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-emerald-300 text-xs font-semibold flex items-center gap-2">
        <span>✅</span> {{ session('success') }}
    </div>
@endif
