<!-- Consumer Gap Table -->
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
            <thead>
                <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-bold">
                    <th class="py-3 px-4">Consumer & CA</th>
                    <th class="py-3 px-3">MRU & Tariff</th>
                    <th class="py-3 px-3">Meter No</th>
                    <th class="py-3 px-3 text-right">Physical Visit</th>
                    <th class="py-3 px-3 text-right">Latest Posted</th>
                    <th class="py-3 px-3 text-center">Net Gap Status</th>
                    <th class="py-3 px-3 text-right">Recovery Plan</th>
                    <th class="py-3 px-4 text-center">Quick Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                <template x-if="loading">
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            <span class="inline-block animate-spin text-xl mr-2">⏳</span> Loading reconciliation records...
                        </td>
                    </tr>
                </template>
                <template x-if="!loading && consumers.length === 0">
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            No consumer records match the current filter.
                        </td>
                    </tr>
                </template>
                <template x-for="c in consumers" :key="c.ca_number">
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                        <!-- CA & Name -->
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900 dark:text-white" x-text="c.consumer_name"></div>
                            <div class="font-mono text-[11px] text-slate-400" x-text="c.ca_number"></div>
                        </td>
                        <!-- MRU & Tariff -->
                        <td class="py-3 px-3">
                            <span class="inline-block px-2 py-0.5 rounded-md font-mono text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300" x-text="c.mru_code"></span>
                            <span class="text-[10px] text-slate-400 block mt-0.5" x-text="c.tariff_category"></span>
                        </td>
                        <!-- Meter No -->
                        <td class="py-3 px-3 font-mono text-[11px] text-slate-600 dark:text-slate-300" x-text="c.meter_no"></td>
                        <!-- Physical Visit Reading -->
                        <td class="py-3 px-3 text-right">
                            <template x-if="c.last_physical_reading !== null">
                                <div>
                                    <span class="font-mono font-bold text-slate-900 dark:text-white" x-text="c.last_physical_reading"></span>
                                    <div class="text-[10px] text-slate-400 mt-0.5 flex items-center justify-end gap-1">
                                        <span x-text="c.last_inspected_at"></span>
                                        <template x-if="c.is_stale">
                                            <span class="text-rose-500 font-bold" title="Stale inspection (>90d)">⚠️</span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <template x-if="c.last_physical_reading === null">
                                <span class="text-slate-400 italic">Never inspected</span>
                            </template>
                        </td>
                        <!-- Latest Posted -->
                        <td class="py-3 px-3 text-right font-mono font-bold text-slate-600 dark:text-slate-400" x-text="c.posted_reading"></td>
                        <!-- Net Gap Status Badge -->
                        <td class="py-3 px-3 text-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-lg text-[10px] font-black tracking-wide"
                                  :class="{
                                      'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30': c.gap_status === 'over_posted',
                                      'bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30': c.gap_status === 'under_posted',
                                      'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30': c.gap_status === 'in_sync',
                                      'bg-slate-500/15 text-slate-600 dark:text-slate-400 border border-slate-500/30': c.gap_status === 'uninspected'
                                  }" x-text="c.badge_label">
                            </span>
                        </td>
                        <!-- Recovery Plan -->
                        <td class="py-3 px-3 text-right">
                            <template x-if="c.gap_status === 'under_posted'">
                                <div>
                                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="'+' + c.gap_slice_applied + ' kWh/mo'"></span>
                                    <div class="text-[10px] text-slate-400" x-text="'~' + c.estimated_recovery_cycles + ' cycles'"></div>
                                </div>
                            </template>
                            <template x-if="c.gap_status === 'over_posted'">
                                <div>
                                    <span class="font-bold text-amber-600" x-text="c.gap_recovery_mode === 'hold_advance' ? '+1 kWh Hold' : '+0 kWh Hold'"></span>
                                    <div class="text-[10px] text-slate-400" x-text="'~' + c.estimated_catchup_cycles + ' cycles'"></div>
                                </div>
                            </template>
                            <template x-if="c.gap_status === 'in_sync' || c.gap_status === 'uninspected'">
                                <span class="text-slate-400">Regular Normal</span>
                            </template>
                        </td>
                        <!-- Quick Actions -->
                        <td class="py-3 px-4 text-center">
                            <div class="inline-flex items-center gap-1.5">
                                <button type="button" @click="openInspectionModal(c)" 
                                        class="p-1.5 rounded-lg bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/40 dark:hover:bg-brand-900/60 text-brand-600 dark:text-cyan-400 transition cursor-pointer" title="Record Physical Inspection">
                                    <span>🔍</span>
                                </button>
                                <button type="button" @click="openMeterSwapModal(c)" 
                                        class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition cursor-pointer" title="Meter Swap / Replacement">
                                    <span>🔄</span>
                                </button>
                                <a :href="'/dashboard?search=' + c.ca_number" 
                                   class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition cursor-pointer" title="Open in Billing Dashboard">
                                    <span>↗️</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    @include('reading-gap.partials.pagination')
</div>
