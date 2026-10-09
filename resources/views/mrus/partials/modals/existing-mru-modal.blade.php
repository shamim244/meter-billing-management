<!-- POPUP MODAL: MRU Already Exists Interactive Notice -->
<div x-show="showExistingMruPopup" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div @click.outside="showExistingMruPopup = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-base">
                    ℹ️
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">MRU Already Exists</h3>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">Workspace is already registered in your account</p>
                </div>
            </div>
            <button @click="showExistingMruPopup = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
        </div>

        <div class="overflow-y-auto p-4 sm:p-6 space-y-4">
            <div class="p-3.5 bg-amber-50/80 dark:bg-amber-950/40 rounded-2xl border border-amber-200/80 dark:border-amber-800/60">
                <p class="text-xs text-amber-900 dark:text-amber-200 leading-relaxed">
                    You already have an active workspace registered with MRU code <strong class="font-mono font-bold" x-text="existingMruData?.code"></strong> (<span class="font-semibold" x-text="existingMruData?.name"></span>).
                </p>
            </div>

            <!-- Existing MRU Summary Card -->
            <div class="bg-slate-50 dark:bg-slate-800/70 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-slate-900 dark:text-white" x-text="existingMruData?.name"></span>
                    <span class="px-2.5 py-0.5 rounded-lg text-xs font-mono font-bold bg-blue-50 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800/60" x-text="existingMruData?.code"></span>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                    <span>Master Consumers Registered:</span>
                    <strong class="font-mono text-slate-900 dark:text-white font-bold" x-text="existingMruData?.consumers_count"></strong>
                </div>
            </div>
        </div>

        <!-- Action Buttons in Popup -->
        <div class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-2.5">
            <button type="button" @click="showExistingMruPopup = false; showCreateModal = true;" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition text-center">
                ✏️ Change Code
            </button>
            <a :href="existingMruData?.dashboard_url" class="w-full sm:w-auto px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5">
                <span>📊</span> Dashboard
            </a>
            <a :href="existingMruData?.show_url" class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-1.5">
                <span>🚀</span> Open Workspace
            </a>
        </div>
    </div>
</div>
