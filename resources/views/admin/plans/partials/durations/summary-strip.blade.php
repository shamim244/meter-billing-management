<!-- Plan Summary Strip -->
<div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-xl grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-4">
    <div>
        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Plan Name</div>
        <div class="text-sm font-black text-white mt-0.5">{{ $plan->name }}</div>
    </div>
    <div>
        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Base Price (1m)</div>
        <div class="text-sm font-black text-emerald-400 mt-0.5">₹{{ number_format($plan->base_price, 2) }}</div>
    </div>
    <div>
        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Included MRUs</div>
        <div class="text-sm font-bold text-slate-200 mt-0.5">{{ number_format($plan->included_mrus) }}</div>
    </div>
    <div>
        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Consumers / Cycle</div>
        <div class="text-sm font-bold text-slate-200 mt-0.5">{{ number_format($plan->included_consumers) }}</div>
    </div>
    <div>
        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Extra MRU Rate</div>
        <div class="text-sm font-bold text-amber-400 mt-0.5">₹{{ number_format($plan->extra_mru_rate, 2) }}</div>
    </div>
    <div>
        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Active Subscribers</div>
        <div class="text-sm font-bold text-indigo-400 mt-0.5">{{ number_format($activeSubscribersCount) }} Agents</div>
    </div>
</div>
