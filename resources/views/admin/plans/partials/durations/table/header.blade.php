<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-800">
    <div>
        <h2 class="text-base font-bold text-white flex items-center gap-2">
            <span>📋</span> Configured Validity Tiers ({{ $plan->durations->count() }})
        </h2>
        <p class="text-xs text-slate-400 mt-0.5">Active durations are immediately available to billing agents during checkout.</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="px-2.5 py-1 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-lg text-xs font-semibold">
            {{ $plan->durations->where('is_active', true)->count() }} Active
        </span>
        <span class="px-2.5 py-1 bg-slate-800 text-slate-400 rounded-lg text-xs font-semibold">
            {{ $plan->durations->where('is_active', false)->count() }} Disabled
        </span>
    </div>
</div>
