<!-- 🛡️ Safe Range Guardrails & Exclusion Controls -->
<div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 space-y-3.5">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-1.5">
            <span class="text-sm">🛡️</span>
            <span class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Guardrails & Exclusion Rules</span>
        </div>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-cyan-300 font-semibold"
              x-text="'Batch Excluded: ' + getExcludedCount() + ' CAs'"></span>
    </div>

    <!-- Min Floor & Max Ceiling Controls -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="flex items-center justify-between bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800">
            <div>
                <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 block">Min Floor Clamp</label>
                <span class="text-[10px] text-slate-400">Never project below</span>
            </div>
            <div class="flex items-center gap-1.5">
                <input type="number" min="0" max="1000" x-model.number="tuningMinFloor" class="w-16 py-1 px-2 text-center text-xs font-bold font-mono rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-indigo-500" />
                <span class="text-[11px] font-bold text-slate-400">kWh</span>
            </div>
        </div>

        <div class="flex items-center justify-between bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800">
            <div>
                <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300 block">Max Ceiling Clamp</label>
                <span class="text-[10px] text-slate-400">Cap runaway projections</span>
            </div>
            <div class="flex items-center gap-1.5">
                <input type="number" min="0" max="5000" x-model.number="tuningMaxCeiling" class="w-16 py-1 px-2 text-center text-xs font-bold font-mono rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-indigo-500" />
                <span class="text-[11px] font-bold text-slate-400">kWh</span>
            </div>
        </div>
    </div>

    <!-- Exclusion Checkbox Toggles -->
    <div class="pt-2 border-t border-slate-200/70 dark:border-slate-700/60 space-y-2">
        <label class="flex items-center gap-2 cursor-pointer select-none text-xs text-slate-700 dark:text-slate-300">
            <input type="checkbox" x-model="tuningAutoExcludeOverposted" class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 h-4 w-4" />
            <span class="font-medium">Auto-exclude Over-Posted meters <span class="text-[10px] text-amber-500 font-mono">(Physical &lt; Posted)</span></span>
        </label>

        <label class="flex items-center gap-2 cursor-pointer select-none text-xs text-slate-700 dark:text-slate-300">
            <input type="checkbox" x-model="tuningExcludeCriticalDoubt" class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 h-4 w-4" />
            <span class="font-medium">Auto-exclude Critical &amp; Doubt review statuses</span>
        </label>
    </div>
</div>
