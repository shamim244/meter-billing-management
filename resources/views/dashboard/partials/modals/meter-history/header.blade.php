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
