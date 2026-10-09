<!-- Storage Health Modal -->
<div x-show="showHealthModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div @click.outside="showHealthModal = false" class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-cyan-100 dark:bg-cyan-950 text-cyan-600 dark:text-cyan-400 flex items-center justify-center text-xl font-bold">
                    🩺
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Storage Health & Integrity Scanner</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Deep check of physical storage disk vs database registry</p>
                </div>
            </div>
            <button type="button" @click="showHealthModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
        </div>

        <div class="p-5 sm:p-6 space-y-4 overflow-y-auto">
            <div x-show="healthScanning" class="py-8 text-center space-y-3">
                <div class="w-8 h-8 border-4 border-cyan-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                <p class="text-xs font-semibold text-slate-500">Scanning physical disk clusters and verifying records...</p>
            </div>

            <div x-show="!healthScanning && healthData" class="space-y-4">
                <!-- Overall verdict -->
                <div class="p-4 rounded-2xl flex items-center gap-3" :class="healthData?.is_healthy ? 'bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800' : 'bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800'">
                    <span class="text-2xl" x-text="healthData?.is_healthy ? '✅' : '⚠️'"></span>
                    <div>
                        <div class="text-sm font-black" :class="healthData?.is_healthy ? 'text-emerald-800 dark:text-emerald-300' : 'text-amber-800 dark:text-amber-300'" x-text="healthData?.is_healthy ? 'Storage System is 100% Healthy & Synchronized' : 'Discrepancies Detected'"></div>
                        <div class="text-xs text-slate-600 dark:text-slate-400" x-text="healthData?.is_healthy ? 'All physical PDFs correspond with active database records.' : 'Some records have missing physical files or orphaned PDFs on disk.'"></div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3 text-center text-xs">
                    <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <div class="text-slate-400 text-[10px] uppercase font-bold">Missing Files</div>
                        <div class="text-lg font-black font-mono mt-1" :class="healthData?.missing_count > 0 ? 'text-rose-500' : 'text-slate-700 dark:text-slate-300'" x-text="healthData?.missing_count || 0"></div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <div class="text-slate-400 text-[10px] uppercase font-bold">Corrupt (<500B)</div>
                        <div class="text-lg font-black font-mono mt-1" :class="healthData?.corrupted_count > 0 ? 'text-rose-500' : 'text-slate-700 dark:text-slate-300'" x-text="healthData?.corrupted_count || 0"></div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <div class="text-slate-400 text-[10px] uppercase font-bold">Orphaned Files</div>
                        <div class="text-lg font-black font-mono mt-1" :class="healthData?.orphaned_count > 0 ? 'text-amber-500' : 'text-slate-700 dark:text-slate-300'" x-text="healthData?.orphaned_count || 0"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-5 sm:p-6 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5">
            <button type="button" @click="showHealthModal = false" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 transition text-center">
                Close
            </button>
            <button type="button" 
                    @click="runStorageSync()" 
                    :disabled="actionRunning"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-md shadow-cyan-500/20">
                <span>🔧 Auto-Heal & Sync Storage</span>
            </button>
        </div>
    </div>
</div>
