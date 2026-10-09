<!-- MRUs Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <template x-for="mru in filteredMrus" :key="mru.id">
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-xs hover:shadow-md hover:border-blue-200 dark:hover:border-blue-800/60 transition-all duration-200 overflow-hidden flex flex-col justify-between group">
            <div>
                <!-- Card Header -->
                <div class="p-6 border-b border-slate-100 dark:border-slate-800/80 bg-gradient-to-r from-slate-50/60 to-white dark:from-slate-800/30 dark:to-slate-900">
                    <div class="flex items-center justify-between">
                        <span class="px-3 py-1 rounded-xl text-xs font-mono font-black bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60 tracking-wider" x-text="mru.code"></span>
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
                    <button @click="openCycleModal(mru.id)" class="text-xs text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-cyan-400 font-bold flex items-center gap-1 transition px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800" title="Launch Billing Cycle">
                        <span>⚡</span> Cycle
                    </button>
                    <template x-if="mru.status === 'active'">
                        <button @click="lockMru(mru)" class="text-xs text-amber-600 hover:text-amber-700 dark:text-amber-400 font-bold flex items-center gap-1 transition px-2 py-1.5 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-950/60" title="Lock MRU to free up subscription quota">
                            <span>🔒</span> Lock
                        </button>
                    </template>
                    <template x-if="mru.status === 'locked'">
                        <button @click="unlockMru(mru)" class="text-xs text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 font-bold flex items-center gap-1 transition px-2 py-1.5 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-950/60" title="Unlock MRU">
                            <span>🔓</span> Unlock
                        </button>
                    </template>
                    <button @click="openDeleteMruModal(mru)" class="text-xs text-rose-500 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 font-bold flex items-center gap-1 transition px-2 py-1.5 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/60" title="Delete MRU Workspace">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Delete</span>
                    </button>
                </div>

                <a :href="'/mrus/' + mru.id" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-xs transition group-hover:shadow-md">
                    Open →
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
