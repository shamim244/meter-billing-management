<!-- 2D Table -->
<div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
        <thead class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-[11px] uppercase font-bold text-slate-500 dark:text-slate-400">
            <tr>
                <th class="py-3 px-3">Month</th>
                <th class="py-3 px-3 text-center">Official PDF Reading</th>
                <th class="py-3 px-3 text-center">PDF Units</th>
                <th class="py-3 px-3 text-center">Working Reading</th>
                <th class="py-3 px-3 text-center">Working Units</th>
                <th class="py-3 px-3 text-center">Smart Avg Used</th>
                <th class="py-3 px-3 text-center">Basis</th>
                <th class="py-3 px-3 text-center">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-xs">
            <template x-for="row in (meterHistoryData?.periods || [])" :key="row.year + '_' + row.month">
                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/50 transition">
                    <td class="py-2.5 px-3 font-bold font-mono text-slate-900 dark:text-white" x-text="row.month_name"></td>
                    <td class="py-2.5 px-3 text-center font-mono">
                        <span x-show="row.has_pdf" class="font-semibold text-slate-800 dark:text-slate-200" x-text="row.pdf_reading || '—'"></span>
                        <span x-show="!row.has_pdf" class="text-slate-400 italic">—</span>
                    </td>
                    <td class="py-2.5 px-3 text-center font-mono">
                        <span x-show="row.pdf_units !== null" class="font-bold text-slate-700 dark:text-slate-300" x-text="row.pdf_units + ' kWh'"></span>
                        <span x-show="row.pdf_units === null" class="text-slate-400">—</span>
                    </td>
                    <td class="py-2.5 px-3 text-center font-mono">
                        <span x-show="row.has_working" class="font-black text-blue-600 dark:text-cyan-400 bg-blue-50 dark:bg-blue-950/60 px-2 py-0.5 rounded-lg border border-blue-100 dark:border-blue-900" x-text="row.working_reading"></span>
                        <span x-show="!row.has_working" class="text-slate-400 italic">Not entered</span>
                    </td>
                    <td class="py-2.5 px-3 text-center font-mono">
                        <span x-show="row.working_units !== null" class="font-bold text-blue-600 dark:text-cyan-400" x-text="row.working_units + ' kWh'"></span>
                        <span x-show="row.working_units === null" class="text-slate-400">—</span>
                    </td>
                    <td class="py-2.5 px-3 text-center font-mono">
                        <span class="font-black px-2 py-0.5 rounded"
                              :class="row.has_working ? 'bg-indigo-100 dark:bg-indigo-950/80 text-indigo-800 dark:text-cyan-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300'"
                              x-text="row.effective_units + ' kWh'"></span>
                        <span x-show="row.delta_formula" class="block text-[10px] text-slate-400 font-mono mt-0.5" x-text="row.delta_formula"></span>
                    </td>
                    <td class="py-2.5 px-3 text-center font-mono">
                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase"
                              :class="{
                                  'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300': row.billing_basis === 'OK',
                                  'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300': row.billing_basis === 'LK',
                                  'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300': row.billing_basis === 'MD',
                                  'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400': !row.billing_basis
                              }"
                              x-text="row.billing_basis || 'OK'"></span>
                    </td>
                    <td class="py-2.5 px-3 text-center">
                        <span x-show="row.is_closed" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300">
                            <span>🔒</span> Closed
                        </span>
                        <span x-show="!row.is_closed" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-blue-50 dark:bg-blue-950/80 text-blue-700 dark:text-cyan-300">
                            <span>📝</span> Active
                        </span>
                    </td>
                </tr>
            </template>
            <template x-if="!meterHistoryData?.periods || meterHistoryData.periods.length === 0">
                <tr>
                    <td colspan="8" class="py-6 text-center text-slate-400 text-xs italic">
                        No monthly readings recorded for this consumer yet.
                    </td>
                </tr>
            </template>
        </tbody>
    </table>
</div>
