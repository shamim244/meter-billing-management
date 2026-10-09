{{-- Offline Reassurance Banner & Failed Sync Drawer --}}
<div>
    <!-- OFFLINE REASSURANCE BANNER (High Visibility & Reassurance) -->
    <div x-show="!isOnline || !isServerReachable" x-cloak class="p-4 sm:p-5 rounded-2xl sm:rounded-3xl bg-gradient-to-r from-amber-500/15 via-rose-500/10 to-amber-500/10 border-2 border-amber-400/90 dark:border-amber-600/90 text-slate-900 dark:text-white shadow-lg transition-all">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
            <div class="flex items-start sm:items-center gap-3.5">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl font-black shrink-0">
                    📡
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="text-sm font-black tracking-tight">Offline Mode Active — Work Safely!</h4>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-500 text-slate-950">Local Memory</span>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5 leading-relaxed">
                        Internet connection lost. You can continue reading, submitting, and editing normally. Every change is preserved on your device and will auto-sync when connection restores.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                <span class="px-3 py-1.5 rounded-xl bg-slate-900/80 text-white font-mono text-xs font-bold shadow-xs" x-text="offlineQueue.length + ' Pending Sync'"></span>
                <button type="button" @click="forceSync()" :disabled="isSyncing" class="px-3.5 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-md transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" :class="isSyncing ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Retry Sync</span>
                </button>
            </div>
        </div>
    </div>

    <!-- PERSISTENT FAILED SYNC DRAWER / BANNER -->
    <div x-show="syncErrors.length > 0" x-cloak class="mt-3 p-3.5 sm:p-4 rounded-2xl bg-gradient-to-r from-rose-500/15 via-rose-500/10 to-amber-500/15 border border-rose-500/80 dark:border-rose-600/80 text-rose-950 dark:text-rose-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3 animate-in fade-in duration-200">
        <div class="flex items-center gap-2.5 text-xs font-bold">
            <span class="text-base">⚠️</span>
            <span>
                <strong x-text="syncErrors.length + (syncErrors.length === 1 ? ' update' : ' updates')"></strong> was rejected by the server and reverted. Click to review:
            </span>
        </div>
        <div class="flex items-center gap-2 shrink-0 flex-wrap">
            <template x-for="err in syncErrors" :key="err.ca_number + '_' + err.field">
                <button type="button" @click="jumpToCa(err.ca_number)" class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-[11px] font-bold shadow-sm transition active:scale-95 flex items-center gap-1 cursor-pointer">
                    <span x-text="'CA: ' + err.ca_number"></span>
                    <span class="text-[9px] opacity-80" x-text="'(' + err.field + ')'"></span>
                </button>
            </template>
            <button type="button" @click="syncErrors = []" class="text-xs text-rose-700 dark:text-rose-300 hover:underline px-1 font-semibold cursor-pointer">
                Dismiss
            </button>
        </div>
    </div>
</div>
