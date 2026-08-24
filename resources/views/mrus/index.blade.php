<x-app-layout>
    <div x-data="mrusIndexApp()" class="py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Hero Header & Stats Banner -->
            <div class="bg-white dark:bg-slate-900/90 backdrop-blur-md rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800/60 mb-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                            Workspace Management Hub
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                            MRU Permanent Workspaces
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-2xl leading-relaxed">
                            Organize consumers by Meter Reading Units (MRU), maintain master consumer lists, and manage isolated monthly billing cycles.
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap items-center gap-3">
                        <button @click="openCycleModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 hover:shadow-blue-500/30 transition active:scale-[0.98]">
                            <span>⚡</span> New Billing Cycle
                        </button>
                        <button @click="openCreateModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition active:scale-[0.98]">
                            <span>+</span> Create MRU
                        </button>
                    </div>
                </div>

                <!-- Live Overview Stats Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
                    <div class="bg-slate-50/80 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                        <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Total Workspaces</span>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">
                            {{ number_format($mrus->count()) }}
                        </div>
                    </div>
                    <div class="bg-slate-50/80 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                        <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Master Consumers</span>
                        <div class="text-xl sm:text-2xl font-black text-blue-600 dark:text-cyan-400 mt-1 font-mono">
                            {{ number_format($mrus->sum('consumer_accounts_count')) }}
                        </div>
                    </div>
                    <div class="col-span-2 sm:col-span-1 bg-slate-50/80 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                        <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Total Bills Recorded</span>
                        <div class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                            {{ number_format($mrus->sum('bill_records_count')) }}
                        </div>
                    </div>
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

            <!-- Search & Filter Controls -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <!-- Search Input -->
                <div class="relative flex-1 max-w-md">
                    <input type="text" x-model="searchQuery" placeholder="Search by MRU code, village or area name..." class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white pl-9 pr-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <!-- Status Filter Pills -->
                <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
                    <button @click="statusFilter = 'all'" :class="statusFilter === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'" class="px-3 py-1.5 rounded-lg text-xs transition">
                        All (<span x-text="mruList.length"></span>)
                    </button>
                    <button @click="statusFilter = 'active'" :class="statusFilter === 'active' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'" class="px-3 py-1.5 rounded-lg text-xs transition">
                        Active
                    </button>
                    <button @click="statusFilter = 'inactive'" :class="statusFilter === 'inactive' ? 'bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-300 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'" class="px-3 py-1.5 rounded-lg text-xs transition">
                        Inactive
                    </button>
                </div>
            </div>

            <!-- MRUs Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="mru in filteredMrus" :key="mru.id">
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-xs hover:shadow-md hover:border-blue-200 dark:hover:border-blue-800/60 transition-all duration-200 overflow-hidden flex flex-col justify-between group">
                        <div>
                            <!-- Card Header -->
                            <div class="p-6 border-b border-slate-100 dark:border-slate-800/80 bg-gradient-to-r from-slate-50/60 to-white dark:from-slate-800/30 dark:to-slate-900">
                                <div class="flex items-center justify-between">
                                    <span class="px-3 py-1 rounded-xl text-xs font-mono font-black bg-blue-50 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60 tracking-wider" x-text="mru.code"></span>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="mru.status === 'active' ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="mru.status === 'active' ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                        <span x-text="mru.status"></span>
                                    </span>
                                </div>
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-3.5 tracking-tight group-hover:text-blue-600 dark:group-hover:text-cyan-400 transition" x-text="mru.name"></h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 font-mono mt-1 line-clamp-1" x-text="mru.full_identifier || mru.code"></p>
                            </div>

                            <!-- Card Stats -->
                            <div class="p-6 grid grid-cols-2 gap-3">
                                <div class="bg-slate-50 dark:bg-slate-800/60 p-3.5 rounded-2xl border border-slate-100 dark:border-slate-800">
                                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Consumers</span>
                                    <div class="text-lg font-black text-slate-900 dark:text-white mt-0.5 font-mono" x-text="Number(mru.consumer_accounts_count).toLocaleString()"></div>
                                </div>
                                <div class="bg-slate-50 dark:bg-slate-800/60 p-3.5 rounded-2xl border border-slate-100 dark:border-slate-800">
                                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Bills Processed</span>
                                    <div class="text-lg font-black text-blue-600 dark:text-cyan-400 mt-0.5 font-mono" x-text="Number(mru.bill_records_count).toLocaleString()"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Action Footer -->
                        <div class="px-6 py-4 bg-slate-50/60 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1">
                                <button @click="openCycleModal(mru.id)" class="text-xs text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-cyan-400 font-bold flex items-center gap-1 transition px-2.5 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800" title="Launch Billing Cycle">
                                    <span>⚡</span> Cycle
                                </button>
                                <button @click="openDeleteMruModal(mru)" class="text-xs text-rose-500 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 font-bold flex items-center gap-1 transition px-2 py-1.5 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/60" title="Delete MRU Workspace">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Delete</span>
                                </button>
                            </div>

                            <a :href="'/mrus/' + mru.id" class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-xs transition group-hover:shadow-md">
                                Open Workspace →
                            </a>
                        </div>
                    </div>
                </template>

                <!-- Empty Search State -->
                <div x-show="filteredMrus.length === 0" class="col-span-full bg-white dark:bg-slate-900 p-12 text-center rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="w-16 h-16 bg-blue-50 dark:bg-blue-950/40 text-blue-500 dark:text-blue-400 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                        🏘️
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">No matching MRU workspaces found</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1 mb-6">
                        Try adjusting your search terms or create a new MRU workspace below.
                    </p>
                    <button @click="openCreateModal()" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition">
                        + Create MRU Workspace
                    </button>
                </div>
            </div>

            <!-- MODAL: Create New MRU Workspace -->
            <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="if(!isSubmittingMru) showCreateModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-base">
                                🏘️
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Create MRU Workspace</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Define permanent meter reading zone</p>
                            </div>
                        </div>
                        <button @click="showCreateModal = false" :disabled="isSubmittingMru" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 disabled:opacity-40 p-1">✕</button>
                    </div>

                    <form @submit.prevent="submitCreateMru(false)" class="overflow-y-auto p-4 sm:p-6 space-y-4">
                        <div x-show="createMruError" class="p-3 bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-semibold rounded-xl" x-text="createMruError"></div>

                        <!-- Overage Confirmation Alert -->
                        <div x-show="mruOverageRequired" class="p-4 bg-amber-50 dark:bg-amber-950/60 border border-amber-300 dark:border-amber-700/80 rounded-2xl space-y-2">
                            <div class="flex items-center gap-2 text-amber-800 dark:text-amber-300 font-bold text-xs">
                                <span>⚠️</span> Plan Quota Notice
                            </div>
                            <p class="text-xs text-amber-900 dark:text-amber-200" x-text="mruOverageMessage"></p>
                            <button type="button" @click="submitCreateMru(true)" :disabled="isSubmittingMru" class="w-full mt-2 py-2.5 px-4 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow transition flex items-center justify-center gap-1.5">
                                <span>✓</span> Confirm & Pay ₹<span x-text="mruOverageAmount"></span> from Wallet
                            </button>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">MRU Code *</label>
                            <input type="text" x-model="newMruCode" placeholder="e.g. 0477, 0473, LAHGARIYA_LALPUR" required class="w-full text-xs font-mono uppercase rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            <span class="text-[10px] text-slate-400 mt-1 block">Used as physical directory folder for PDFs.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Village / Area Name *</label>
                            <input type="text" x-model="newMruName" placeholder="e.g. Gerua, Hala, Lalpur" required class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Full Identifier / Sub-Division (Optional)</label>
                            <input type="text" x-model="newMruIdentifier" placeholder="e.g. Sub-division 04 / Gerua North" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        </div>

                        <div class="pt-4 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-2.5 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showCreateModal = false" :disabled="isSubmittingMru" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-center">
                                Cancel
                            </button>
                            <button x-show="!mruOverageRequired" type="submit" :disabled="isSubmittingMru" class="w-full sm:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-1.5">
                                <svg x-show="isSubmittingMru" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span x-text="isSubmittingMru ? 'Verifying & Creating...' : 'Create Workspace'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

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

            <!-- MODAL: Global New Billing Cycle -->
            <div x-show="showCycleModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="if(!cycleInProgress) showCycleModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-base">
                                ⚡
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">New Billing Cycle</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Initialize cycle session or download bills</p>
                            </div>
                        </div>
                        <button @click="showCycleModal = false" :disabled="cycleInProgress" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 disabled:opacity-40 p-1">✕</button>
                    </div>

                    <div class="overflow-y-auto p-4 sm:p-6 space-y-4">
                        <!-- For MRU Select -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Target MRU Workspace</label>
                            <select x-model="selectedMruId" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500">
                                <option value="">Select MRU Workspace...</option>
                                <template x-for="m in mruList" :key="m.id">
                                    <option :value="m.id" x-text="m.name + ' (' + m.code + ') — ' + m.consumer_accounts_count + ' consumers'"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Billing Month & Year Selectors -->
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Billing Month -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Billing Month</label>
                                <select x-model="cycleMonth" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500">
                                    <option value="1">January</option>
                                    <option value="2">February</option>
                                    <option value="3">March</option>
                                    <option value="4">April</option>
                                    <option value="5">May</option>
                                    <option value="6">June</option>
                                    <option value="7">July</option>
                                    <option value="8">August</option>
                                    <option value="9">September</option>
                                    <option value="10">October</option>
                                    <option value="11">November</option>
                                    <option value="12">December</option>
                                </select>
                            </div>

                            <!-- Billing Year -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Billing Year</label>
                                <select x-model="cycleYear" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500">
                                    @php
                                        $currYear = (int) date('Y');
                                        $availableYears = range(max(2020, $currYear - 3), $currYear + 5);
                                        rsort($availableYears);
                                    @endphp
                                    @foreach($availableYears as $yr)
                                        <option value="{{ $yr }}">{{ $yr }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Tip Box -->
                        <div class="p-3.5 bg-blue-50/60 dark:bg-blue-950/40 rounded-2xl border border-blue-100 dark:border-blue-900/60 text-[11px] text-blue-800 dark:text-cyan-300 leading-relaxed">
                            💡 <strong>Recommendation:</strong> Use <strong>"Create Cycle Only"</strong> to instantly open the workspace ledger with preceding readings synced, or <strong>"Create & Download All"</strong> to download official PDFs in parallel.
                        </div>

                        <!-- Live Progress Box -->
                        <div x-show="cycleInProgress" class="bg-slate-950 text-cyan-300 p-4 rounded-2xl font-mono text-xs space-y-1.5 border border-slate-800 shadow-inner">
                            <div class="flex items-center gap-2 text-white font-bold">
                                <svg class="animate-spin h-4 w-4 text-cyan-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span x-text="executingAction === 'create_only' ? 'Initializing cycle workspace...' : 'Launching cycle & pulling PDFs...'"></span>
                            </div>
                            <div class="text-slate-400 text-[10px]">Processing consumers concurrently. Please wait...</div>
                        </div>

                        <!-- Cycle Overage Confirmation Alert -->
                        <div x-show="cycleOverageRequired" class="p-4 bg-amber-50 dark:bg-amber-950/60 border border-amber-300 dark:border-amber-700/80 rounded-2xl space-y-2">
                            <div class="flex items-center gap-2 text-amber-800 dark:text-amber-300 font-bold text-xs">
                                <span>⚠️</span> Consumer Quota Notice
                            </div>
                            <p class="text-xs text-amber-900 dark:text-amber-200" x-text="cycleOverageMessage"></p>
                            <button type="button" @click="launchBillingCycle(executingAction, true)" :disabled="cycleInProgress" class="w-full mt-2 py-2.5 px-4 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow transition flex items-center justify-center gap-1.5">
                                <span>✓</span> Confirm & Pay ₹<span x-text="cycleOverageAmount"></span> from Wallet
                            </button>
                        </div>

                        <!-- Result notification -->
                        <div x-show="cycleResult && !cycleOverageRequired" class="p-3.5 rounded-2xl text-xs font-semibold" :class="cycleResult?.success ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800'" x-text="cycleResult?.message"></div>
                    </div>

                    <div class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2.5">
                        <button type="button" @click="showCycleModal = false" :disabled="cycleInProgress" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition text-center">
                            Cancel
                        </button>

                        <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                            <button type="button" @click="launchBillingCycle('create_only')" :disabled="cycleInProgress || !selectedMruId" class="w-full sm:w-auto px-4 py-2.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 disabled:opacity-40 text-slate-800 dark:text-slate-200 rounded-xl text-xs font-bold transition text-center">
                                ➕ Create Cycle Only
                            </button>
                            <button type="button" @click="launchBillingCycle('download_all')" :disabled="cycleInProgress || !selectedMruId" class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-1">
                                <span>⚡</span> Create & Download All
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL: Delete MRU Workspace Confirmation -->
            <div x-show="showDeleteMruModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="if(!isDeletingMru) showDeleteMruModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <!-- Modal Header -->
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
                        <!-- Summary Info Card -->
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-semibold uppercase">MRU Name</span>
                                <span class="font-bold text-slate-900 dark:text-white" x-text="targetMru?.name"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-semibold uppercase">MRU Code</span>
                                <span class="font-mono font-bold text-blue-600 dark:text-blue-400" x-text="targetMru?.code"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-semibold uppercase">Consumer Accounts</span>
                                <span class="font-mono font-bold text-slate-700 dark:text-slate-300" x-text="(targetMru?.consumer_accounts_count || 0) + ' Accounts'"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-semibold uppercase">Billing Records</span>
                                <span class="font-mono font-bold text-slate-700 dark:text-slate-300" x-text="(targetMru?.bill_records_count || 0) + ' Bills'"></span>
                            </div>
                        </div>

                        <!-- Irreversible Warning Box -->
                        <div class="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-xs text-rose-800 dark:text-rose-300 space-y-1">
                            <div class="flex items-center gap-1.5 font-bold">
                                <span>⚠️</span>
                                <span>Warning: Irreversible Action</span>
                            </div>
                            <p class="text-[11px] text-rose-700 dark:text-rose-400 leading-relaxed">
                                Deleting this MRU will permanently remove its consumer accounts, all historical billing sessions, and purge all physical PDF bill files stored on disk for this MRU.
                            </p>
                        </div>
                    </div>

                    <!-- Footer -->
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

        </div>
    </div>

    <script>
        function mrusIndexApp() {
            return {
                showCreateModal: false,
                showCycleModal: false,
                showExistingMruPopup: false,
                showDeleteMruModal: false,
                isDeletingMru: false,
                targetMru: null,
                existingMruData: null,
                searchQuery: '',
                statusFilter: 'all',
                mruList: @js($mrus),
                selectedMruId: '{{ $mrus->first()?->id ?? "" }}',
                cycleMonth: {{ now()->month }},
                cycleYear: {{ now()->year }},
                executingAction: 'download_all',
                cycleInProgress: false,
                cycleResult: null,

                // Create MRU Form State
                newMruCode: '',
                newMruName: '',
                newMruIdentifier: '',
                isSubmittingMru: false,
                createMruError: null,
                mruOverageRequired: false,
                mruOverageAmount: 0,
                mruOverageMessage: '',

                // Cycle Overage State
                cycleOverageRequired: false,
                cycleOverageAmount: 0,
                cycleOverageMessage: '',

                get filteredMrus() {
                    return this.mruList.filter(m => {
                        const matchesSearch = !this.searchQuery || 
                            (m.name && m.name.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                            (m.code && m.code.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                            (m.full_identifier && m.full_identifier.toLowerCase().includes(this.searchQuery.toLowerCase()));

                        const matchesStatus = this.statusFilter === 'all' || m.status === this.statusFilter;

                        return matchesSearch && matchesStatus;
                    });
                },

                openCreateModal() {
                    this.newMruCode = '';
                    this.newMruName = '';
                    this.newMruIdentifier = '';
                    this.createMruError = null;
                    this.mruOverageRequired = false;
                    this.mruOverageAmount = 0;
                    this.mruOverageMessage = '';
                    this.showCreateModal = true;
                },

                submitCreateMru(payOverage = false) {
                    if (!this.newMruCode.trim() || !this.newMruName.trim()) return;

                    this.isSubmittingMru = true;
                    this.createMruError = null;

                    fetch('/mrus', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            code: this.newMruCode,
                            name: this.newMruName,
                            full_identifier: this.newMruIdentifier,
                            pay_overage: payOverage ? 1 : 0
                        })
                    })
                    .then(async res => {
                        const data = await res.json();
                        if (res.status === 402 && data.requires_overage) {
                            this.mruOverageRequired = true;
                            this.mruOverageAmount = data.amount_due || 0;
                            this.mruOverageMessage = data.message || 'Plan MRU limit exceeded. Wallet deduction required.';
                            throw new Error(data.message);
                        }
                        if (!res.ok) {
                            throw new Error(data.message || 'Server error occurred');
                        }
                        return data;
                    })
                    .then(data => {
                        if (data.already_exists) {
                            this.showCreateModal = false;
                            this.existingMruData = data.mru;
                            this.showExistingMruPopup = true;
                        } else if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        } else {
                            window.location.reload();
                        }
                    })
                    .catch(err => {
                        this.createMruError = err.message || 'Failed to process MRU workspace.';
                    })
                    .finally(() => {
                        this.isSubmittingMru = false;
                    });
                },

                openCycleModal(mruId = null) {
                    if (mruId) {
                        this.selectedMruId = mruId;
                    } else if (!this.selectedMruId && this.mruList.length > 0) {
                        this.selectedMruId = this.mruList[0].id;
                    }
                    this.cycleResult = null;
                    this.cycleOverageRequired = false;
                    this.cycleOverageAmount = 0;
                    this.cycleOverageMessage = '';
                    this.showCycleModal = true;
                },

                launchBillingCycle(actionType = 'download_all', payOverage = false) {
                    if (!this.selectedMruId) return;

                    this.executingAction = actionType;
                    this.cycleInProgress = true;
                    this.cycleResult = null;

                    fetch('/mrus/billing-cycle', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            mru_id: this.selectedMruId,
                            billing_month: this.cycleMonth,
                            billing_year: this.cycleYear,
                            action_type: actionType,
                            pay_overage: payOverage ? 1 : 0
                        })
                    })
                    .then(async res => {
                        const data = await res.json();
                        if (res.status === 402 && data.requires_overage) {
                            this.cycleOverageRequired = true;
                            this.cycleOverageAmount = data.amount_due || 0;
                            this.cycleOverageMessage = data.message || 'Consumer quota exceeded. Wallet deduction required.';
                            throw new Error(data.message);
                        }
                        if (!res.ok) {
                            throw new Error(data.message || 'Server returned an error');
                        }
                        return data;
                    })
                    .then(json => {
                        this.cycleResult = json;
                        if (json.success) {
                            setTimeout(() => {
                                if (json.redirect_url) {
                                    window.location.href = json.redirect_url;
                                } else {
                                    window.location.reload();
                                }
                            }, 800);
                        }
                    })
                    .catch(err => {
                        this.cycleResult = {
                            success: false,
                            message: err.message || 'An error occurred while executing billing cycle.'
                        };
                    })
                    .finally(() => {
                        this.cycleInProgress = false;
                    });
                },

                openDeleteMruModal(mru) {
                    this.targetMru = mru;
                    this.showDeleteMruModal = true;
                },

                confirmDeleteMru() {
                    if (!this.targetMru) return;
                    this.isDeletingMru = true;

                    fetch(`/mrus/${this.targetMru.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(async res => {
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Error deleting MRU');
                        return data;
                    })
                    .then(data => {
                        this.isDeletingMru = false;
                        this.showDeleteMruModal = false;
                        window.location.reload();
                    })
                    .catch(err => {
                        this.isDeletingMru = false;
                        alert('Failed to delete MRU: ' + err.message);
                    });
                }
            };
        }
    </script>
</x-app-layout>
