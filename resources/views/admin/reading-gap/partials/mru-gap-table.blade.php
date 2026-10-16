<!-- MRU Risk Ranking Table -->
<div class="bg-slate-950 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
    <div class="p-4 border-b border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-base">🗂️</span>
            <h3 class="text-sm font-bold text-white">MRU Severity & Unbilled Risk Rankings</h3>
        </div>
        <span class="text-xs text-slate-400" x-text="mruList.length + ' MRU areas tracked'"></span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
            <thead>
                <tr class="border-b border-slate-800 bg-slate-900/60 text-slate-400 font-bold">
                    <th class="py-3 px-4">MRU Code & Village</th>
                    <th class="py-3 px-3">Assigned Operator</th>
                    <th class="py-3 px-3 text-right">Total Meters</th>
                    <th class="py-3 px-3 text-right">Under-Posted</th>
                    <th class="py-3 px-3 text-right">Over-Posted</th>
                    <th class="py-3 px-3 text-right">Unbilled Gap</th>
                    <th class="py-3 px-4 text-right">Est. Revenue Risk</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                <template x-if="loading">
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-500">
                            Loading MRU gap analysis...
                        </td>
                    </tr>
                </template>
                <template x-if="!loading && mruList.length === 0">
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-500">
                            No MRUs registered in the system.
                        </td>
                    </tr>
                </template>
                <template x-for="m in mruList" :key="m.id">
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="py-3 px-4">
                            <span class="font-mono font-bold text-white" x-text="m.code"></span>
                            <span class="text-slate-400 block text-[11px]" x-text="m.name"></span>
                        </td>
                        <td class="py-3 px-3 text-slate-300" x-text="m.agent_name"></td>
                        <td class="py-3 px-3 text-right font-mono text-slate-400" x-text="m.total_consumers"></td>
                        <td class="py-3 px-3 text-right">
                            <span class="font-mono font-bold text-rose-400" x-text="m.under_posted_count"></span>
                        </td>
                        <td class="py-3 px-3 text-right">
                            <span class="font-mono font-bold text-amber-400" x-text="m.over_posted_count"></span>
                        </td>
                        <td class="py-3 px-3 text-right">
                            <span class="font-mono font-extrabold text-white" x-text="(m.unbilled_kwh || 0).toLocaleString() + ' kWh'"></span>
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-400">
                            <span x-text="'₹ ' + Math.round(m.estimated_risk_rs || 0).toLocaleString()"></span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
