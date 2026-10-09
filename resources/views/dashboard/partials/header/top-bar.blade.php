{{-- Top Header & Action Bar (Clean & Streamlined) --}}
<div class="bg-white dark:bg-slate-900 p-4 sm:p-5 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>⚡</span> Billing Hub
            </h1>

            <!-- Real-Time Server Connectivity Badge & Sync -->
            <div class="inline-flex items-center gap-1.5">
                <!-- Online & Synced -->
                <template x-if="isOnline && isServerReachable && !isSyncing">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/80 shadow-2xs" title="Connected to server. All data live and synchronized.">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Online</span>
                    </span>
                </template>

                <!-- Offline / Disconnected -->
                <template x-if="!isOnline || !isServerReachable">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200/80 dark:border-rose-800/80 animate-pulse shadow-2xs" title="Disconnected from server. Working offline safely — your work is saved locally.">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <span>Offline</span>
                        <span x-show="offlineQueue.length > 0" class="px-1.5 py-0.2 rounded-full bg-rose-200 dark:bg-rose-900 text-rose-900 dark:text-rose-100 text-[9px]" x-text="offlineQueue.length + ' queued'"></span>
                    </span>
                </template>

                <!-- Syncing in Progress -->
                <template x-if="isSyncing">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/80 shadow-2xs" title="Synchronizing with server...">
                        <svg class="w-3 h-3 animate-spin text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Syncing...</span>
                    </span>
                </template>

                <!-- Instant Sync Refresh Button -->
                <button type="button" @click="forceSync()" :disabled="isSyncing" class="p-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white transition active:scale-95 cursor-pointer" title="Synchronize / Refresh latest data from server">
                    <svg class="w-3.5 h-3.5" :class="isSyncing ? 'animate-spin text-blue-600 dark:text-cyan-400' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">
            Manage, verify & analyze monthly consumer bills
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <!-- Quick Single CA Pull -->
        <button @click="showQuickPullModal = true; quickPullCa = ''; quickPullResult = null;" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition active:scale-95 cursor-pointer" title="Instantly download bill for any CA">
            <span>⚡</span> Quick Pull CA
        </button>

        <!-- Average Adjustment & Sequential Compounding Tuning Controls -->
        <div class="inline-flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-xl border border-slate-200/80 dark:border-slate-700/80 text-xs shadow-2xs">
            <button type="button" @click="openTuningModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold text-xs transition active:scale-95 cursor-pointer"
                    :class="(avgTuningSteps.length > 0 || avgAdjustmentPercent !== 0) ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-400' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800'"
                    title="Click to open Smart Average Tuning & Sequential Compounding calculator">
                <span>⚡</span>
                <template x-if="avgTuningSteps.length > 0">
                    <span x-text="'Tuned: ' + getCompoundedUnits(50) + ' kWh (' + (getNetTuningPercent(50) >= 0 ? '+' : '') + getNetTuningPercent(50) + '%)'"></span>
                </template>
                <template x-if="avgTuningSteps.length === 0 && avgAdjustmentPercent !== 0">
                    <span x-text="'Tuned: ' + (avgAdjustmentPercent > 0 ? '+' : '') + avgAdjustmentPercent + '%'"></span>
                </template>
                <template x-if="avgTuningSteps.length === 0 && avgAdjustmentPercent === 0">
                    <span>⚡ Avg Tuning</span>
                </template>
            </button>

            <button type="button" @click="openTuningModal()" class="p-1 rounded text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition cursor-pointer" title="Adjust compounding percentages">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
            </button>
        </div>

        <!-- Bulk Auto-Fill Readings Button -->
        <button @click="bulkAutoProjectAll()" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 rounded-xl text-xs font-bold border border-indigo-200 dark:border-indigo-800/80 transition active:scale-95 cursor-pointer" title="Auto-project working readings with Previous + Adjusted Average for all accounts in this cycle">
            <span>⚡</span> Auto-Fill (Prev + Avg)
        </button>

        <!-- Export Actions Group -->
        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200/80 dark:border-slate-700/80">
            <button @click="exportCsv()" class="inline-flex items-center gap-1.5 px-3 py-1.5 hover:bg-white dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition cursor-pointer" title="Export bill ledger to CSV">
                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>CSV</span>
            </button>

            <button @click="exportZip()" class="inline-flex items-center gap-1.5 px-3 py-1.5 hover:bg-white dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition cursor-pointer" title="Export all cycle PDFs to ZIP">
                <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>ZIP</span>
            </button>
        </div>
    </div>
</div>
