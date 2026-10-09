            <!-- 📊 2D METER READING HISTORY MODAL -->
            <div x-show="showMeterHistoryModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-4xl w-full shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150" @click.away="showMeterHistoryModal = false">
                    <!-- Modal Header -->
                    <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-950/80 text-blue-600 dark:text-cyan-400 flex items-center justify-center text-xl font-bold shadow-inner">
                                📊
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white" x-text="activeHistoryConsumerName || 'Consumer Reading History'"></h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-50 dark:bg-indigo-950/80 text-indigo-700 dark:text-cyan-300 border border-indigo-200 dark:border-indigo-800">
                                        2D Matrix
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-mono" x-text="'CA: ' + activeHistoryCa"></p>
                            </div>
                        </div>
                        <button type="button" @click="showMeterHistoryModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            ✕
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-5 sm:p-6 space-y-4">
                        <!-- Loading Indicator -->
                        <div x-show="meterHistoryLoading" class="py-12 flex flex-col items-center justify-center gap-3 text-slate-400">
                            <div class="w-8 h-8 border-3 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                            <span class="text-xs font-semibold">Loading 2D reading history...</span>
                        </div>

                        <!-- Content when loaded -->
                        <div x-show="!meterHistoryLoading && meterHistoryData">
                            <!-- Summary Bar -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                                <div class="p-3 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 text-center">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 block">Smart Avg Basis</span>
                                    <span class="text-base font-black text-indigo-700 dark:text-cyan-400 font-mono" x-text="(meterHistoryData?.average_units || 50) + ' kWh'"></span>
                                </div>
                                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-center">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Recorded Periods</span>
                                    <span class="text-base font-black text-slate-800 dark:text-slate-200 font-mono" x-text="meterHistoryData?.periods_count || 0"></span>
                                </div>
                                <div class="p-3 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/60 text-center">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 block">Calculation Priority</span>
                                    <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300">Working > PDF</span>
                                </div>
                                <div class="p-3 rounded-2xl bg-blue-50/60 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/60 text-center">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 block">Full Ledger View</span>
                                    <a :href="'/bills/history/' + activeHistoryCa" target="_blank" class="text-xs font-bold text-blue-600 dark:text-cyan-400 hover:underline">
                                        Open Page ↗
                                    </a>
                                </div>
                            </div>

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
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                        <button type="button" @click="showMeterHistoryModal = false" class="px-5 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-white text-xs font-bold rounded-xl transition">
                            Close
                        </button>
                    </div>
                </div>
            </div>
