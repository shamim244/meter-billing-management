<!-- Admin System-Wide KPI Banner -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Unbilled Energy Volume -->
    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 shadow-lg relative overflow-hidden">
        <div class="flex items-center justify-between">
            <span class="text-xs uppercase font-bold text-slate-400 tracking-wider">Unbilled Energy</span>
            <span class="p-1.5 rounded-lg bg-rose-500/10 text-rose-400 text-sm">⚡</span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-2xl font-black text-rose-400" x-text="kpis.total_unbilled_mwh || '0'">0</span>
            <span class="text-xs font-semibold text-slate-400">MWh</span>
        </div>
        <div class="text-[11px] text-slate-400 mt-1" x-text="(kpis.total_unbilled_kwh || 0).toLocaleString() + ' kWh pending across meters'">0 kWh pending</div>
    </div>

    <!-- Revenue at Risk -->
    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 shadow-lg relative overflow-hidden">
        <div class="flex items-center justify-between">
            <span class="text-xs uppercase font-bold text-slate-400 tracking-wider">Revenue At Risk</span>
            <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 text-sm">₹</span>
        </div>
        <div class="mt-2 flex items-baseline gap-1">
            <span class="text-xs font-bold text-slate-400">₹</span>
            <span class="text-2xl font-black text-white" x-text="kpis.estimated_financial_gap_lakhs || '0'">0</span>
            <span class="text-xs font-semibold text-slate-400">Lakhs</span>
        </div>
        <div class="text-[11px] text-slate-400 mt-1" x-text="'₹ ' + Math.round(kpis.estimated_financial_gap_rs || 0).toLocaleString() + ' recoverable'">₹ 0 recoverable</div>
    </div>

    <!-- Over-Posted Float -->
    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 shadow-lg relative overflow-hidden">
        <div class="flex items-center justify-between">
            <span class="text-xs uppercase font-bold text-slate-400 tracking-wider">Advance Units Float</span>
            <span class="p-1.5 rounded-lg bg-amber-500/10 text-amber-400 text-sm">⚠️</span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-2xl font-black text-amber-400" x-text="(kpis.over_posted_count || 0).toLocaleString()">0</span>
            <span class="text-xs font-semibold text-slate-400">meters</span>
        </div>
        <div class="text-[11px] text-slate-400 mt-1" x-text="(kpis.total_over_posted_kwh || 0).toLocaleString() + ' advance kWh held on +0/+1'">0 advance kWh held</div>
    </div>

    <!-- Stale Audits -->
    <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 shadow-lg relative overflow-hidden">
        <div class="flex items-center justify-between">
            <span class="text-xs uppercase font-bold text-slate-400 tracking-wider">Stale Audits (>90d)</span>
            <span class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-400 text-sm">⏱️</span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-2xl font-black text-indigo-400" x-text="(kpis.stale_inspections_count || 0).toLocaleString()">0</span>
            <span class="text-xs font-semibold text-slate-400">consumers</span>
        </div>
        <div class="text-[11px] text-slate-400 mt-1" x-text="(kpis.inspected_count || 0) + ' inspected / ' + (kpis.uninspected_count || 0) + ' uninspected'">0 inspected</div>
    </div>
</div>
