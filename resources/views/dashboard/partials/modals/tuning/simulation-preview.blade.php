<!-- Compounding Simulation Display -->
<div class="bg-gradient-to-br from-indigo-50/70 via-slate-50 to-indigo-50/40 dark:from-indigo-950/40 dark:via-slate-900 dark:to-indigo-950/20 p-4 rounded-2xl border border-indigo-100 dark:border-indigo-900/60 space-y-3">
    <div class="flex items-center justify-between">
        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Compounded Yield Preview</span>
        <div class="flex items-center gap-2">
            <label class="text-[11px] font-semibold text-slate-500">Base:</label>
            <input type="number" x-model.number="tuningBaseUnits" class="w-16 py-0.5 px-2 text-center text-xs font-bold font-mono rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200" />
            <span class="text-xs font-bold text-slate-400">kWh</span>
        </div>
    </div>

    <!-- Sequential Chain Visualization -->
    <div class="flex flex-wrap items-center gap-2 font-mono text-xs">
        <span class="px-2.5 py-1 rounded-xl bg-slate-200/80 dark:bg-slate-800 font-bold text-slate-700 dark:text-slate-300">
            <span x-text="tuningBaseUnits"></span> kWh (Base)
        </span>

        <template x-for="(st, idx) in getCompoundedStepsDetails(tuningBaseUnits)" :key="idx">
            <div class="flex items-center gap-1.5">
                <span class="text-slate-400 font-bold">→</span>
                <span class="px-2 py-1 rounded-xl font-bold border flex items-center gap-1"
                      :class="st.percent < 0 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'">
                    <span x-text="(st.percent > 0 ? '+' : '') + st.percent + '%'"></span>
                    <span class="text-[10px] opacity-75 font-normal" x-text="'(' + st.after + ' kWh)'"></span>
                </span>
            </div>
        </template>
    </div>

    <!-- Net Result Box -->
    <div class="pt-2 border-t border-indigo-100 dark:border-indigo-900/60 flex items-center justify-between">
        <div>
            <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Effective Tuned Average:</span>
            <div class="text-xl font-black text-indigo-700 dark:text-cyan-400 font-mono">
                <span x-text="getCompoundedUnits(tuningBaseUnits)"></span> kWh
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 font-sans"
                      x-text="'(' + (getNetTuningPercent(tuningBaseUnits) >= 0 ? '+' : '') + getNetTuningPercent(tuningBaseUnits) + '% net)'"></span>
            </div>
        </div>
        <template x-if="avgTuningSteps.length > 1">
            <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-cyan-300 border border-indigo-200 dark:border-indigo-700">
                Sequential Compounding Active
            </span>
        </template>
    </div>
</div>
