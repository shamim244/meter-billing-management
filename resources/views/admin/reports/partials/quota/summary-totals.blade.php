<!-- Summary Totals -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-slate-900 p-4 rounded-2xl border border-slate-800 shadow-xl space-y-1">
        <span class="text-[11px] font-semibold text-slate-400 uppercase">Total Overage Spend</span>
        <div class="text-2xl font-black text-rose-400 font-mono">
            ₹{{ number_format($aggregate['totals']['total_overage_spend'], 2) }}
        </div>
    </div>
    <div class="bg-slate-900 p-4 rounded-2xl border border-slate-800 shadow-xl space-y-1">
        <span class="text-[11px] font-semibold text-slate-400 uppercase">Extra MRU Fees</span>
        <div class="text-2xl font-black text-indigo-400 font-mono">
            ₹{{ number_format($aggregate['totals']['total_mru_charges'], 2) }}
        </div>
    </div>
    <div class="bg-slate-900 p-4 rounded-2xl border border-slate-800 shadow-xl space-y-1">
        <span class="text-[11px] font-semibold text-slate-400 uppercase">Extra Consumer Fees</span>
        <div class="text-2xl font-black text-cyan-400 font-mono">
            ₹{{ number_format($aggregate['totals']['total_consumer_charges'], 2) }}
        </div>
    </div>
    <div class="bg-slate-900 p-4 rounded-2xl border border-slate-800 shadow-xl space-y-1">
        <span class="text-[11px] font-semibold text-slate-400 uppercase">Active MRUs Monitored</span>
        <div class="text-2xl font-black text-emerald-400 font-mono">
            {{ $aggregate['totals']['total_mrus_used'] }}
        </div>
    </div>
</div>
