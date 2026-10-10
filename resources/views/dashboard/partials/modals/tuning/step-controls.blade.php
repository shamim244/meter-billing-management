<!-- Add Compounding Steps -->
<div class="space-y-2">
    <label class="text-xs font-bold text-slate-700 dark:text-slate-300 block">
        Quick Step Additions (Click to chain sequentially):
    </label>
    <div class="grid grid-cols-4 sm:grid-cols-8 gap-1.5">
        <button type="button" @click="addTuningStep(-30)" class="py-1.5 px-1 bg-slate-100 dark:bg-slate-800 hover:bg-amber-100 dark:hover:bg-amber-950/70 text-slate-700 dark:text-slate-300 hover:text-amber-700 rounded-xl text-xs font-bold font-mono transition">-30%</button>
        <button type="button" @click="addTuningStep(-20)" class="py-1.5 px-1 bg-slate-100 dark:bg-slate-800 hover:bg-amber-100 dark:hover:bg-amber-950/70 text-slate-700 dark:text-slate-300 hover:text-amber-700 rounded-xl text-xs font-bold font-mono transition">-20%</button>
        <button type="button" @click="addTuningStep(-10)" class="py-1.5 px-1 bg-slate-100 dark:bg-slate-800 hover:bg-amber-100 dark:hover:bg-amber-950/70 text-slate-700 dark:text-slate-300 hover:text-amber-700 rounded-xl text-xs font-bold font-mono transition">-10%</button>
        <button type="button" @click="addTuningStep(-5)" class="py-1.5 px-1 bg-slate-100 dark:bg-slate-800 hover:bg-amber-100 dark:hover:bg-amber-950/70 text-slate-700 dark:text-slate-300 hover:text-amber-700 rounded-xl text-xs font-bold font-mono transition">-5%</button>
        <button type="button" @click="addTuningStep(5)" class="py-1.5 px-1 bg-slate-100 dark:bg-slate-800 hover:bg-emerald-100 dark:hover:bg-emerald-950/70 text-slate-700 dark:text-slate-300 hover:text-emerald-700 rounded-xl text-xs font-bold font-mono transition">+5%</button>
        <button type="button" @click="addTuningStep(10)" class="py-1.5 px-1 bg-slate-100 dark:bg-slate-800 hover:bg-emerald-100 dark:hover:bg-emerald-950/70 text-slate-700 dark:text-slate-300 hover:text-emerald-700 rounded-xl text-xs font-bold font-mono transition">+10%</button>
        <button type="button" @click="addTuningStep(20)" class="py-1.5 px-1 bg-slate-100 dark:bg-slate-800 hover:bg-emerald-100 dark:hover:bg-emerald-950/70 text-slate-700 dark:text-slate-300 hover:text-emerald-700 rounded-xl text-xs font-bold font-mono transition">+20%</button>
        <button type="button" @click="addTuningStep(30)" class="py-1.5 px-1 bg-slate-100 dark:bg-slate-800 hover:bg-emerald-100 dark:hover:bg-emerald-950/70 text-slate-700 dark:text-slate-300 hover:text-emerald-700 rounded-xl text-xs font-bold font-mono transition">+30%</button>
    </div>

    <!-- Custom Percentage Step -->
    <div class="flex items-center gap-2 pt-2">
        <span class="text-xs font-medium text-slate-500">Custom Step:</span>
        <input type="number" x-model.number="customTuningStep" placeholder="10" class="w-20 py-1 px-2.5 text-xs font-bold font-mono rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500" />
        <span class="text-xs font-bold text-slate-400">%</span>
        <button type="button" @click="addTuningStep(customTuningStep)" class="px-3 py-1 bg-indigo-50 dark:bg-indigo-950/80 hover:bg-indigo-100 text-indigo-700 dark:text-cyan-300 font-bold text-xs rounded-xl border border-indigo-200 dark:border-indigo-800 transition">
            + Add Step
        </button>
    </div>
</div>

<!-- Active Steps List -->
<div class="space-y-2">
    <div class="flex items-center justify-between">
        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">
            Current Compounding Sequence (<span x-text="avgTuningSteps.length"></span> steps):
        </label>
        <button type="button" @click="clearTuning()" class="text-[11px] font-bold text-rose-600 dark:text-rose-400 hover:underline">
            ↺ Clear / Reset to Normal
        </button>
    </div>

    <template x-if="avgTuningSteps.length === 0">
        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-xs text-slate-400 italic text-center">
            No percentage adjustments added. The baseline average (Normal 0%) will be used.
        </div>
    </template>

    <div class="flex flex-wrap items-center gap-1.5">
        <template x-for="(st, idx) in avgTuningSteps" :key="idx">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-mono font-bold border"
                  :class="st < 0 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'">
                <span x-text="'Step ' + (idx + 1) + ': ' + (st > 0 ? '+' : '') + st + '%'"></span>
                <button type="button" @click="removeTuningStep(idx)" class="hover:text-rose-600 font-sans font-black text-xs leading-none">✕</button>
            </span>
        </template>
    </div>
</div>
