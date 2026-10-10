<!-- Modal Actions: Two distinct choices -->
<div class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex flex-col gap-3">
    <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2.5">
        <button type="button" @click="showStartBillingModal = false" :disabled="billingInProgress" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition text-center">
            Cancel
        </button>

        <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
            <button type="button" @click="triggerMruBilling('create_only')" :disabled="billingInProgress || {{ $consumers->total() }} === 0" class="w-full sm:w-auto px-4 py-2.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 disabled:opacity-40 disabled:cursor-not-allowed text-slate-800 dark:text-slate-200 rounded-xl text-xs font-bold transition text-center" title="{{ $consumers->total() === 0 ? 'Cannot create cycle: MRU has 0 consumers. Add consumers first.' : '' }}">
                ➕ Create Cycle Only
            </button>
            <button type="button" @click="triggerMruBilling('download_all')" :disabled="billingInProgress || {{ $consumers->total() }} === 0" class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-1" title="{{ $consumers->total() === 0 ? 'Cannot create cycle: MRU has 0 consumers. Add consumers first.' : '' }}">
                <span>⚡</span> Create & Download All
            </button>
        </div>
    </div>

    @if($consumers->total() === 0)
        <div class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/60 text-[11px] text-amber-700 dark:text-amber-300 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-1.5 font-medium">
                <span>🔒</span>
                <span>Buttons are disallowed: Add or import consumers to this MRU to unlock billing cycles.</span>
            </div>
            <button type="button" @click="showStartBillingModal = false; showImportModal = true" class="text-xs font-bold text-amber-800 dark:text-amber-200 hover:underline shrink-0">
                Import Now →
            </button>
        </div>
    @endif
</div>
