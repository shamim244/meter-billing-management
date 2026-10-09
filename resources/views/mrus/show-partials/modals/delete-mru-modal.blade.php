<!-- MODAL: Delete MRU Confirmation -->
<div x-show="showDeleteMruModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div @click.outside="if(!isDeletingMru) showDeleteMruModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold text-base">
                    🗑️
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Delete MRU Workspace</h3>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">Permanent removal of workspace & files</p>
                </div>
            </div>
            <button @click="showDeleteMruModal = false" :disabled="isDeletingMru" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 disabled:opacity-40 p-1">✕</button>
        </div>

        <div class="p-4 sm:p-6 space-y-4 overflow-y-auto">
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500 font-semibold uppercase">MRU Name</span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ $mru->name }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500 font-semibold uppercase">MRU Code</span>
                    <span class="font-mono font-bold text-blue-600 dark:text-blue-400">{{ $mru->code }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500 font-semibold uppercase">Master Consumers</span>
                    <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ number_format($consumers->total()) }} Accounts</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500 font-semibold uppercase">Billing Sessions</span>
                    <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ $sessions->count() }} Sessions</span>
                </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-xs text-rose-800 dark:text-rose-300 space-y-1">
                <div class="flex items-center gap-1.5 font-bold">
                    <span>⚠️</span>
                    <span>Warning: Irreversible Action</span>
                </div>
                <p class="text-[11px] text-rose-700 dark:text-rose-400 leading-relaxed">
                    Deleting this MRU will permanently delete all consumer accounts, all historical billing sessions, and purge all physical PDF bill files stored on disk for MRU <strong>{{ $mru->code }}</strong>.
                </p>
            </div>
        </div>

        <div class="p-4 sm:p-6 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5">
            <button type="button" @click="showDeleteMruModal = false" :disabled="isDeletingMru" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 transition text-center">
                Cancel
            </button>
            <button type="button" 
                    @click="confirmDeleteMru()" 
                    :disabled="isDeletingMru"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-md shadow-rose-500/20">
                <span x-show="!isDeletingMru">🗑️ Delete MRU & Files</span>
                <span x-show="isDeletingMru" class="flex items-center gap-1">
                    <span class="animate-spin text-xs">⏳</span> Deleting...
                </span>
            </button>
        </div>
    </div>
</div>
