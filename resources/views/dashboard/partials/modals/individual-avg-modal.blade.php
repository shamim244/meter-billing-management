<!-- 🎯 INDIVIDUAL CONSUMER SMART AVERAGE & EXCLUSION MODAL -->
<div x-show="showIndividualAvgModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150" @click.away="showIndividualAvgModal = false">
        
        <!-- Header -->
        <div class="p-4 sm:p-5 bg-gradient-to-r from-slate-900 to-indigo-950 text-white flex items-center justify-between border-b border-slate-800">
            <div class="flex items-center gap-2">
                <span class="text-base">🎯</span>
                <div>
                    <h3 class="text-sm font-bold">Individual Average Tuning</h3>
                    <p class="text-[11px] text-slate-400 font-mono" x-text="activeIndividualBill ? ('CA: ' + activeIndividualBill.ca_number) : ''"></p>
                </div>
            </div>
            <button type="button" @click="showIndividualAvgModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg">✕</button>
        </div>

        <!-- Body -->
        <div class="p-5 space-y-3.5 text-xs">
            <!-- Consumer Identity Info & Live Preview -->
            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 space-y-1.5">
                <div class="flex justify-between text-slate-500 dark:text-slate-400">
                    <span>Consumer:</span>
                    <span class="font-bold text-slate-700 dark:text-slate-200" x-text="activeIndividualBill?.consumer_name"></span>
                </div>
                <div class="flex justify-between text-slate-500 dark:text-slate-400">
                    <span>Base History Avg:</span>
                    <span class="font-mono font-bold text-slate-700 dark:text-slate-200" x-text="(activeIndividualBill?.base_avg_units ?? 50) + ' kWh'"></span>
                </div>
                <div class="flex justify-between items-center pt-1 border-t border-slate-200/60 dark:border-slate-700/60 text-slate-500 dark:text-slate-400">
                    <span class="font-semibold">Effective Tuned:</span>
                    <span class="font-mono font-bold text-xs" :class="{
                        'text-emerald-600 dark:text-emerald-400': getIndividualEffectivePreview().status === 'normal',
                        'text-amber-600 dark:text-amber-400': getIndividualEffectivePreview().status === 'clamped_min' || getIndividualEffectivePreview().status === 'clamped_max',
                        'text-indigo-600 dark:text-cyan-400': getIndividualEffectivePreview().status === 'custom',
                        'text-rose-600 dark:text-rose-400': getIndividualEffectivePreview().status === 'excluded'
                    }" x-text="getIndividualEffectivePreview().label"></span>
                </div>
            </div>

            <!-- Minimum Floor & Maximum Ceiling Guardrails -->
            <div class="space-y-1.5">
                <span class="font-bold text-slate-700 dark:text-slate-300 block text-[11px]">Safe Range Guardrails (Floor & Ceiling)</span>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="text-[10px] text-slate-500 dark:text-slate-400 block mb-0.5">Min Floor (kWh)</label>
                        <input type="number" min="0" max="5000" x-model.number="individualMinUnits" placeholder="e.g. 20"
                               class="w-full py-1.5 px-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono font-bold focus:ring-2 focus:ring-indigo-500 text-xs" />
                        <p class="text-[9px] text-slate-400 mt-0.5">Floor if avg &lt; min</p>
                    </div>
                    <div>
                        <label class="text-[10px] text-slate-500 dark:text-slate-400 block mb-0.5">Max Ceiling (kWh)</label>
                        <input type="number" min="0" max="5000" x-model.number="individualMaxUnits" placeholder="e.g. 100"
                               class="w-full py-1.5 px-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono font-bold focus:ring-2 focus:ring-indigo-500 text-xs" />
                        <p class="text-[9px] text-slate-400 mt-0.5">Cap if avg &gt; max</p>
                    </div>
                </div>
            </div>

            <!-- Custom Units Override -->
            <div class="space-y-1">
                <label class="font-bold text-slate-700 dark:text-slate-300 block text-[11px]">Manual Custom Fixed Average (Optional)</label>
                <div class="flex items-center gap-2">
                    <input type="number" min="1" max="5000" x-model.number="individualCustomUnits" placeholder="e.g. 65"
                           class="w-full py-1.5 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono font-bold focus:ring-2 focus:ring-indigo-500 text-xs" />
                    <span class="font-bold text-slate-400 text-xs">kWh</span>
                </div>
                <p class="text-[9px] text-slate-400">Leave blank to use dynamic average with floor/ceiling limits.</p>
            </div>

            <!-- Exclusion Toggle & Reason -->
            <div class="pt-2 border-t border-slate-200 dark:border-slate-800 space-y-2">
                <label class="flex items-start gap-2 cursor-pointer select-none">
                    <input type="checkbox" x-model="individualIsExcluded" class="mt-0.5 rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5" />
                    <div>
                        <span class="font-bold text-slate-800 dark:text-slate-200 block text-[11px]">Exclude from Smart Average / Auto-Projection</span>
                        <span class="text-[9px] text-slate-400">When checked, bulk auto-projection will skip this account.</span>
                    </div>
                </label>

                <div x-show="individualIsExcluded" x-transition class="space-y-1 pl-5">
                    <input type="text" x-model="individualExcludeReason" placeholder="e.g. Over-Posted / Disputed meter"
                           class="w-full py-1.5 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500" />
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-3.5 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <button type="button" @click="individualCustomUnits = null; individualMinUnits = null; individualMaxUnits = null; individualIsExcluded = false; individualExcludeReason = ''; saveIndividualAverageTuning();"
                    class="text-xs text-rose-600 hover:underline font-bold">
                Reset to Default
            </button>
            <div class="flex items-center gap-2">
                <button type="button" @click="showIndividualAvgModal = false" class="px-3.5 py-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition">
                    Cancel
                </button>
                <button type="button" @click="saveIndividualAverageTuning()" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md transition">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</div>
