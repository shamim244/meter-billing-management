<!-- Modal Footer -->
<div class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
    <button type="button" @click="clearTuning(); showTuningModal = false; fetchData(1);" class="text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-bold">
        Reset to Normal (0%)
    </button>
    <div class="flex items-center gap-2">
        <button type="button" @click="showTuningModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition">
            Cancel
        </button>
        <button type="button" @click="applyTuningAndFetch()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-500/20 transition">
            ⚡ Apply & Recalculate
        </button>
    </div>
</div>
