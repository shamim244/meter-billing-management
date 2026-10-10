<!-- Platform KPI Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl space-y-1">
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Bills Processed</span>
        <div class="text-2xl font-black text-cyan-400 font-mono">
            {{ number_format($summary['totals']['total_bills_processed']) }}
        </div>
        <div class="text-[10px] text-slate-500">{{ date('F Y', mktime(0,0,0,$month,1,$year)) }}</div>
    </div>

    <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl space-y-1">
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Active MRUs Platform-Wide</span>
        <div class="text-2xl font-black text-indigo-400 font-mono">
            {{ number_format($summary['totals']['total_active_mrus']) }}
        </div>
        <div class="text-[10px] text-slate-500">Across {{ $summary['totals']['total_agents'] }} Billing Agents</div>
    </div>

    <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl space-y-1">
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Consecutive LK/MD Alerts</span>
        <div class="text-2xl font-black text-amber-400 font-mono">
            {{ number_format($summary['totals']['total_flagged_consumers']) }}
        </div>
        <div class="text-[10px] text-slate-500">2+ cycles on estimate</div>
    </div>

    <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl space-y-1">
        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Active Billing Agents</span>
        <div class="text-2xl font-black text-emerald-400 font-mono">
            {{ $summary['totals']['total_agents'] }}
        </div>
        <div class="text-[10px] text-slate-500">Registered platform accounts</div>
    </div>
</div>
