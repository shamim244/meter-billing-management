            <!-- Top Header & Action Bar (Clean & Streamlined) -->
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
                    <button @click="showQuickPullModal = true; quickPullCa = ''; quickPullResult = null;" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition active:scale-95" title="Instantly download bill for any CA">
                        <span>⚡</span> Quick Pull CA
                    </button>

                    <!-- Average Adjustment & Sequential Compounding Tuning Controls -->
                    <div class="inline-flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-xl border border-slate-200/80 dark:border-slate-700/80 text-xs shadow-2xs">
                        <button type="button" @click="openTuningModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold text-xs transition active:scale-95"
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

                        <button type="button" @click="openTuningModal()" class="p-1 rounded text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition" title="Adjust compounding percentages">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        </button>
                    </div>

                    <!-- Bulk Auto-Fill Readings Button -->
                    <button @click="bulkAutoProjectAll()" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 rounded-xl text-xs font-bold border border-indigo-200 dark:border-indigo-800/80 transition active:scale-95" title="Auto-project working readings with Previous + Adjusted Average for all accounts in this cycle">
                        <span>⚡</span> Auto-Fill (Prev + Avg)
                    </button>

                    <!-- Export Actions Group -->
                    <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200/80 dark:border-slate-700/80">
                        <button @click="exportCsv()" class="inline-flex items-center gap-1.5 px-3 py-1.5 hover:bg-white dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition" title="Export bill ledger to CSV">
                            <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>CSV</span>
                        </button>

                        <button @click="exportZip()" class="inline-flex items-center gap-1.5 px-3 py-1.5 hover:bg-white dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition" title="Export all cycle PDFs to ZIP">
                            <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span>ZIP</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- OFFLINE REASSURANCE BANNER (High Visibility & Reassurance) -->
            <div x-show="!isOnline || !isServerReachable" x-cloak class="p-4 sm:p-5 rounded-2xl sm:rounded-3xl bg-gradient-to-r from-amber-500/15 via-rose-500/10 to-amber-500/10 border-2 border-amber-400/90 dark:border-amber-600/90 text-slate-900 dark:text-white shadow-lg transition-all">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                    <div class="flex items-start sm:items-center gap-3.5">
                        <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl font-black shrink-0">
                            📡
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-black uppercase tracking-wider text-amber-700 dark:text-amber-300">You are Working Offline</span>
                                <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-amber-200/80 dark:bg-amber-900/80 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-700">Safe Offline Mode</span>
                            </div>
                            <p class="text-xs text-slate-700 dark:text-slate-300 mt-1 font-medium leading-relaxed">
                                Server connection is temporarily unavailable. <strong>Don't worry — your work is completely safe!</strong> All meter readings, remarks, and status submissions are saved directly to your device and will <strong>automatically synchronize</strong> the second connection is restored.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5 shrink-0 self-end sm:self-auto">
                        <template x-if="offlineQueue.length > 0">
                            <span class="text-xs font-bold text-amber-800 dark:text-amber-200 px-3 py-1.5 rounded-xl bg-amber-100/90 dark:bg-amber-950/90 border border-amber-300 dark:border-amber-700 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                <span x-text="offlineQueue.length + ' pending sync'"></span>
                            </span>
                        </template>
                        <button type="button" @click="checkServerConnection(true)" :disabled="isCheckingConnection" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-md shadow-amber-600/20 transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
                            <svg x-show="isCheckingConnection" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="isCheckingConnection ? 'Testing...' : '🔄 Retry Connection'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- RECONNECTING & SYNCING BANNER -->
            <div x-show="isOnline && isServerReachable && isSyncing" x-cloak class="p-3.5 sm:p-4 rounded-2xl bg-gradient-to-r from-blue-500/15 via-cyan-500/10 to-indigo-500/15 border border-blue-400/80 dark:border-blue-700/80 text-blue-900 dark:text-cyan-200 shadow-sm flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 text-xs font-semibold">
                    <svg class="w-4 h-4 animate-spin text-blue-600 dark:text-cyan-400 shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Connected to server! Synchronizing offline updates and refreshing latest data...</span>
                </div>
            </div>

            <!-- ⚠️ PERSISTENT FAILED SYNC DRAWER / BANNER -->
            <div x-show="syncErrors.length > 0" x-cloak class="p-3.5 sm:p-4 rounded-2xl bg-gradient-to-r from-rose-500/15 via-rose-500/10 to-amber-500/15 border border-rose-500/80 dark:border-rose-600/80 text-rose-950 dark:text-rose-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3 animate-in fade-in duration-200">
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

            <!-- Flash Alerts -->
            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <span>✅</span> {{ session('success') }}
                    </div>
                    <button @click="$el.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 hover:opacity-75">✕</button>
                </div>
            @endif

            @if(session('info'))
                <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800/60 text-blue-800 dark:text-cyan-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <span>ℹ️</span> {{ session('info') }}
                    </div>
                    <button @click="$el.parentElement.remove()" class="text-blue-600 dark:text-cyan-400 hover:opacity-75">✕</button>
                </div>
            @endif

            @if(session('warning'))
                <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <span>⚠️</span> {{ session('warning') }}
                    </div>
                    <button @click="$el.parentElement.remove()" class="text-amber-600 dark:text-amber-400 hover:opacity-75">✕</button>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <span>❌</span> {{ session('error') }}
                    </div>
                    <button @click="$el.parentElement.remove()" class="text-rose-600 dark:text-rose-400 hover:opacity-75">✕</button>
                </div>
            @endif

            <!-- Subscription Onboarding Banner (When user has no active plan) -->
            @if(!$activeSubscription)
                <div class="p-4 sm:p-5 rounded-2xl sm:rounded-3xl bg-gradient-to-r from-amber-500/10 via-indigo-500/5 to-transparent border border-amber-300 dark:border-amber-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
                    <div class="flex items-start sm:items-center gap-3.5">
                        <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg shrink-0 font-black">
                            ⚡
                        </div>
                        <div>
                            <div class="text-sm font-black text-slate-900 dark:text-white flex flex-wrap items-center gap-2">
                                <span>Get Started with a Subscription Plan</span>
                                <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">100% Free Plan Available</span>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                                You do not have an active subscription yet. Activate our <strong>Free Starter Tier</strong> (1 MRU & 500 Consumers) with 1 click to create cycles and download bills immediately.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('user-panel.subscription') }}" class="shrink-0 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-500/20 transition active:scale-95 text-center">
                        <span>⚡ Activate Free Plan / Choose Tier</span>
                        <span>→</span>
                    </a>
                </div>
            @endif

            <!-- Workspace Selection & Billing Period Bar -->
            <div class="bg-white dark:bg-slate-900 p-4 sm:px-6 sm:py-4 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3.5">
                <div class="flex flex-wrap items-center gap-3 sm:gap-5">
                    <!-- MRU / Area Selector (Specific MRU required + Inline Create) -->
                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 whitespace-nowrap">🏘️ MRU / Area:</span>
                        <div class="flex items-center gap-1.5">
                            <select x-model="filterMru" @change="onMruChange()" class="text-xs font-bold border-slate-300 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white py-1.5 px-3 focus:ring-blue-500 focus:border-blue-500 shadow-sm max-w-[150px] sm:max-w-[200px] truncate">
                                @forelse($mrus as $mru)
                                    <option value="{{ $mru->id }}">{{ $mru->code }} - {{ $mru->name }}</option>
                                @empty
                                    <option value="">No MRU Found</option>
                                @endforelse
                            </select>
                            <button @click="openCreateMruModal()" class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition shrink-0" title="Create New MRU Workspace">
                                + Create MRU
                            </button>
                        </div>
                    </div>

                    <!-- Billing Period Selector (Specific Period + Inline New Cycle + Sync Missing) -->
                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 whitespace-nowrap">📅 Billing Period:</span>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <select x-model="selectedPeriodKey" @change="onPeriodChange()" class="text-xs font-bold border-slate-300 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white py-1.5 px-3 focus:ring-blue-500 focus:border-blue-500 shadow-sm max-w-[130px] sm:max-w-none">
                                <template x-for="p in availablePeriods" :key="p.key">
                                    <option :value="p.key" x-text="p.label" :selected="p.key === selectedPeriodKey"></option>
                                </template>
                                <option value="" disabled x-show="!availablePeriods || availablePeriods.length === 0" :selected="!availablePeriods || availablePeriods.length === 0">No Cycles Under This MRU</option>
                            </select>
                            <button @click="showNewCycleModal = true" class="px-2.5 py-1.5 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 text-blue-700 dark:text-cyan-300 rounded-xl text-xs font-bold border border-blue-200 dark:border-blue-800/80 transition flex items-center gap-1 shrink-0" title="New Billing Cycle">
                                <span>⚡</span> + New Cycle
                            </button>
                            
                            <!-- Smart Sync Missing Action Button -->
                            <template x-if="counts.missing_pdf > 0">
                                <button @click="syncMissingBills()" :disabled="syncingMissing" class="px-3 py-1.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-xl text-xs font-bold shadow-sm flex items-center gap-1.5 transition active:scale-95 whitespace-nowrap shrink-0" title="Download only missing/failed bills for this MRU & Period">
                                    <span x-show="!syncingMissing">⚡</span>
                                    <svg x-show="syncingMissing" class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span x-text="syncingMissing ? 'Syncing...' : ('Sync Missing (' + counts.missing_pdf + ')')"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="pt-1 lg:pt-0">
                    <a :href="filterMru ? ('/mrus/' + filterMru) : '{{ route('mrus.index') }}'" class="text-xs text-blue-600 dark:text-cyan-400 hover:underline font-bold inline-flex items-center gap-1">
                        <span>📂</span> Manage MRU Workspace →
                    </a>
                </div>
            </div>

            <!-- Top KPI Cards Row -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <!-- Total Consumers -->
                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Consumers</span>
                        <span class="text-lg">👥</span>
                    </div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-1" x-text="formatNumber(counts.total_consumers ?? {{ $totalConsumers }})">{{ number_format($totalConsumers) }}</div>
                    <span class="text-[11px] text-slate-400 font-medium">Active accounts</span>
                </div>

                <!-- Total Billing -->
                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Billing</span>
                        <span class="text-lg">💰</span>
                    </div>
                    <div class="text-2xl font-black text-blue-600 dark:text-cyan-400 mt-1" x-text="'₹' + formatNumber(counts.filtered_amount ?? {{ $totalPeriodAmount }})">
                        ₹{{ number_format($totalPeriodAmount) }}
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium">Combined amount</span>
                </div>

                <!-- Total Units -->
                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Units</span>
                        <span class="text-lg">⚡</span>
                    </div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-1" x-text="formatNumber(counts.filtered_units ?? {{ $totalPeriodUnits }})">
                        {{ number_format($totalPeriodUnits) }}
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium">kWh consumed</span>
                </div>

                <!-- Submitted -->
                <div @click="filterStatus = (filterStatus === 'submitted' ? 'all' : 'submitted'); fetchData(1);" class="bg-emerald-50/70 dark:bg-emerald-950/30 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 cursor-pointer p-4 rounded-2xl border border-emerald-200 dark:border-emerald-800/60 shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">Submitted</span>
                        <span>✅</span>
                    </div>
                    <div class="text-2xl font-black text-emerald-800 dark:text-emerald-200 mt-1" x-text="counts.submitted ?? 0">
                        {{ $statusCounts['submitted'] }}
                    </div>
                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">Bills processed</span>
                </div>

                <!-- Critical -->
                <div @click="filterStatus = (filterStatus === 'critical' ? 'all' : 'critical'); fetchData(1);" class="bg-rose-50/70 dark:bg-rose-950/30 hover:bg-rose-50 dark:hover:bg-rose-950/50 cursor-pointer p-4 rounded-2xl border border-rose-200 dark:border-rose-800/60 shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-rose-700 dark:text-rose-300 uppercase tracking-wider">Critical</span>
                        <span>❌</span>
                    </div>
                    <div class="text-2xl font-black text-rose-800 dark:text-rose-200 mt-1" x-text="counts.critical ?? 0">
                        {{ $statusCounts['critical'] }}
                    </div>
                    <span class="text-[11px] text-rose-600 dark:text-rose-400 font-medium">Cannot submit</span>
                </div>

                <!-- Doubt -->
                <div @click="filterStatus = (filterStatus === 'doubt' ? 'all' : 'doubt'); fetchData(1);" class="bg-amber-50/70 dark:bg-amber-950/30 hover:bg-amber-50 dark:hover:bg-amber-950/50 cursor-pointer p-4 rounded-2xl border border-amber-200 dark:border-amber-800/60 shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-amber-700 dark:text-amber-300 uppercase tracking-wider">Doubt</span>
                        <span>⚠️</span>
                    </div>
                    <div class="text-2xl font-black text-amber-800 dark:text-amber-200 mt-1" x-text="counts.doubt ?? 0">
                        {{ $statusCounts['doubt'] }}
                    </div>
                    <span class="text-[11px] text-amber-600 dark:text-amber-400 font-medium">Review later</span>
                </div>
            </div>

            <!-- Clean & Separated Controls Section -->
            <div class="space-y-4">
                <!-- 1. Status Filter Pills Container -->
                <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">Review:</span>
                        <button @click="filterStatus = 'all'; fetchData(1)" :class="filterStatus === 'all' ? 'bg-slate-900 dark:bg-blue-600 text-white shadow-sm font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-3.5 py-1.5 rounded-2xl text-xs font-semibold transition">
                            📋 All (<span x-text="counts.all ?? 0"></span>)
                        </button>
                        <button @click="filterStatus = 'pending'; fetchData(1)" :class="filterStatus === 'pending' ? 'bg-slate-700 dark:bg-slate-600 text-white shadow-sm font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-3.5 py-1.5 rounded-2xl text-xs font-semibold transition">
                            ⏳ Pending (<span x-text="counts.pending ?? 0"></span>)
                        </button>
                        <button @click="filterStatus = 'submitted'; fetchData(1)" :class="filterStatus === 'submitted' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/50'" class="px-3.5 py-1.5 rounded-2xl text-xs font-semibold transition">
                            ✅ Submitted (<span x-text="counts.submitted ?? 0"></span>)
                        </button>
                        <button @click="filterStatus = 'critical'; fetchData(1)" :class="filterStatus === 'critical' ? 'bg-rose-600 text-white shadow-sm font-bold' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/50'" class="px-3.5 py-1.5 rounded-2xl text-xs font-semibold transition">
                            ❌ Critical (<span x-text="counts.critical ?? 0"></span>)
                        </button>
                        <button @click="filterStatus = 'doubt'; fetchData(1)" :class="filterStatus === 'doubt' ? 'bg-amber-600 text-white shadow-sm font-bold' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/50'" class="px-3.5 py-1.5 rounded-2xl text-xs font-semibold transition">
                            ⚠️ Doubt (<span x-text="counts.doubt ?? 0"></span>)
                        </button>
                    </div>

                    <!-- 1b. Basis Filter Pills (OK, LK, MD, PL, RN) -->
                    <div class="flex flex-wrap items-center gap-2 pt-2.5 border-t border-slate-100 dark:border-slate-800/80">
                        <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">⚡ Basis:</span>
                        <button @click="setBasisFilter('all')" :class="basisFilter === 'all' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition">
                            All Basis
                        </button>
                        <button @click="setBasisFilter('OK')" :class="basisFilter === 'OK' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/50'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1">
                            <span>🟢 OK (Normal)</span>
                            <span class="text-[10px] opacity-75 font-mono" x-show="counts.basis_ok !== undefined" x-text="'(' + (counts.basis_ok ?? 0) + ')'"></span>
                        </button>
                        <button @click="setBasisFilter('LK')" :class="basisFilter === 'LK' ? 'bg-amber-600 text-white shadow-xs font-bold' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/50'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1">
                            <span>🟡 LK (Locked)</span>
                            <span class="text-[10px] opacity-75 font-mono" x-show="counts.basis_lk !== undefined" x-text="'(' + (counts.basis_lk ?? 0) + ')'"></span>
                        </button>
                        <button @click="setBasisFilter('MD')" :class="basisFilter === 'MD' ? 'bg-rose-600 text-white shadow-xs font-bold' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/50'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1">
                            <span>🟠 MD (Defective)</span>
                            <span class="text-[10px] opacity-75 font-mono" x-show="counts.basis_md !== undefined" x-text="'(' + (counts.basis_md ?? 0) + ')'"></span>
                        </button>
                        <button @click="setBasisFilter('PL')" :class="basisFilter === 'PL' ? 'bg-indigo-600 text-white shadow-xs font-bold' : 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/50'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1">
                            <span>🔵 PL</span>
                            <span class="text-[10px] opacity-75 font-mono" x-show="counts.basis_pl !== undefined" x-text="'(' + (counts.basis_pl ?? 0) + ')'"></span>
                        </button>
                        <button @click="setBasisFilter('RN')" :class="basisFilter === 'RN' ? 'bg-purple-600 text-white shadow-xs font-bold' : 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 hover:bg-purple-100 dark:hover:bg-purple-900/50'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1">
                            <span>⚪ RN</span>
                            <span class="text-[10px] opacity-75 font-mono" x-show="counts.basis_rn !== undefined" x-text="'(' + (counts.basis_rn ?? 0) + ')'"></span>
                        </button>
                    </div>
                </div>

                <!-- 2. Search Bar Container -->
                <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <div class="relative w-full">
                        <input type="text" x-model="searchQuery" @input.debounce.300ms="fetchData(1)" placeholder="Search CA / Name / Meter..." class="w-full text-xs rounded-2xl border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-white pl-10 pr-4 py-3 focus:ring-blue-500 focus:border-blue-500 shadow-inner" />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                <!-- 3. Status Priority, Sort By Field & Table/Card View Switcher Container -->
                <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Left: Sorting Dropdowns -->
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-3 w-full md:w-auto">
                        <!-- Status Priority -->
                        <div class="w-full sm:w-56">
                            <select x-model="statusSort" @change="onStatusSortChange()" class="w-full text-xs font-medium border-slate-300 dark:border-slate-700 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2.5 px-3.5 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                                <option value="default">Priority: Normal (No Grouping)</option>
                                <option value="pdcs">Priority: P-D-C-S</option>
                                <option value="dcps">Priority: D-C-P-S</option>
                                <option value="cdps">Priority: C-D-P-S</option>
                                <option value="spdc">Priority: S-P-D-C</option>
                            </select>
                        </div>

                        <!-- Sort By Field -->
                        <div class="w-full sm:w-64">
                            <select x-model="sortOption" @change="onSortOptionChange()" class="w-full text-xs font-medium border-slate-300 dark:border-slate-700 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2.5 px-3.5 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                                <optgroup label="Account & Consumer">
                                    <option value="ca_number_asc">Sort: CA Number (0-9 Low-High)</option>
                                    <option value="ca_number_desc">Sort: CA Number (9-0 High-Low)</option>
                                    <option value="consumer_name_asc">Sort: Consumer Name (A-Z)</option>
                                    <option value="consumer_name_desc">Sort: Consumer Name (Z-A)</option>
                                    <option value="meter_no_asc">Sort: Meter No (A-Z)</option>
                                    <option value="meter_no_desc">Sort: Meter No (Z-A)</option>
                                </optgroup>
                                <optgroup label="Readings & Units">
                                    <option value="working_reading_asc">Sort: Working Reading (Low-High)</option>
                                    <option value="working_reading_desc">Sort: Working Reading (High-Low)</option>
                                    <option value="previous_reading_asc">Sort: Previous Reading (Low-High)</option>
                                    <option value="previous_reading_desc">Sort: Previous Reading (High-Low)</option>
                                    <option value="current_reading_asc">Sort: PDF Reading (Low-High)</option>
                                    <option value="current_reading_desc">Sort: PDF Reading (High-Low)</option>
                                    <option value="units_asc">Sort: Units (Low to High)</option>
                                    <option value="units_desc">Sort: Units (High to Low)</option>
                                    <option value="amount_asc">Sort: Amount (Low to High)</option>
                                    <option value="amount_desc">Sort: Amount (High to Low)</option>
                                </optgroup>
                                <optgroup label="Billing Basis & Status">
                                    <option value="billing_basis_asc">Sort: Basis (A-Z: LK, MD, OK, PL)</option>
                                    <option value="billing_basis_desc">Sort: Basis (Z-A: PL, OK, MD, LK)</option>
                                    <option value="basis_priority_asc">Sort: Basis Priority (OK → LK → MD → PL)</option>
                                    <option value="basis_priority_desc">Sort: Basis Priority (MD → LK → PL → OK)</option>
                                    <option value="review_status_asc">Sort: Status (Pending → Doubt → Critical → Submitted)</option>
                                    <option value="review_status_desc">Sort: Status (Submitted → Critical → Doubt → Pending)</option>
                                    <option value="bill_month_asc">Sort: Bill Month (A-Z)</option>
                                    <option value="bill_month_desc">Sort: Bill Month (Z-A)</option>
                                </optgroup>
                            </select>
                        </div>

                        <!-- Basis Filter Dropdown -->
                        <div class="w-full sm:w-48">
                            <select x-model="basisFilter" @change="onBasisFilterChange()" class="w-full text-xs font-medium border-slate-300 dark:border-slate-700 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2.5 px-3.5 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                                <option value="all">⚡ All Basis</option>
                                <option value="OK">🟢 Basis: OK (Normal)</option>
                                <option value="LK">🟡 Basis: LK (Locked)</option>
                                <option value="MD">🟠 Basis: MD (Defective)</option>
                                <option value="PL">🔵 Basis: PL (Power Line)</option>
                                <option value="RN">⚪ Basis: RN (Reading N/A)</option>
                            </select>
                        </div>

                        <!-- Tag Filter -->
                        <div class="w-full sm:w-48">
                            <select x-model="tagFilter" @change="fetchData(1)" class="w-full text-xs font-medium border-slate-300 dark:border-slate-700 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2.5 px-3.5 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                                <option value="all">🏷️ All Tags</option>
                                <template x-for="t in availableTags" :key="t.code">
                                    <option :value="t.code" x-text="'🏷️ ' + (t.short_label || t.label)"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Right: Bulk Mobile & Table / Card View Mode Switcher -->
                    <div class="flex items-center gap-2 self-start md:self-auto flex-wrap">
                        <button type="button" 
                                @click="openBulkMobileModal()" 
                                class="px-3 py-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/80 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer active:scale-95" 
                                title="Bulk Update Consumer Mobile Numbers">
                            <span>📱</span>
                            <span class="hidden sm:inline">Bulk Mobiles</span>
                        </button>

                        <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
                            <button @click="setViewMode('table')" :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-cyan-300 shadow-sm font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'" class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                Table View
                            </button>
                            <button @click="setViewMode('card')" :class="viewMode === 'card' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-cyan-300 shadow-sm font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'" class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                Cards View
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading Indicator (Cold Initial Load Only) -->
            <div x-show="loading && items.length === 0" class="flex justify-center py-12">
                <div class="flex items-center gap-3 px-5 py-3 bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 text-sm font-medium">
                    <svg class="animate-spin h-5 w-5 text-blue-600 dark:text-cyan-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Loading bill records...
                </div>
            </div>

            <!-- In-Place Filtering Slim Top Progress Bar -->
            <div x-show="loading && items.length > 0" class="w-full bg-slate-100 dark:bg-slate-800 h-1 rounded-full overflow-hidden transition-all shadow-inner">
                <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-500 h-full w-full animate-pulse"></div>
            </div>

            <!-- No Data State -->
            <div x-show="!loading && items.length === 0" class="bg-white dark:bg-slate-900 p-12 text-center rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="w-16 h-16 bg-blue-50 dark:bg-blue-950/40 text-blue-500 dark:text-blue-400 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200">No bills found for this filter</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1 mb-6">There are no records matching your current filter. You can switch filter pills or create a new cycle.</p>
                <div class="flex items-center justify-center gap-3">
                    <button @click="filterStatus = 'all'; basisFilter = 'all'; localStorage.setItem('dashboard_basis_filter', 'all'); fetchData(1)" class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition">
                        📋 View All Bills (<span x-text="counts.all ?? 0"></span>)
                    </button>
                    <button @click="showNewCycleModal = true" class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        <span>⚡</span> New Billing Cycle
                    </button>
                </div>
            </div>
