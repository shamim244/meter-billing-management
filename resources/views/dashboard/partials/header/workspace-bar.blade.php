{{-- Workspace Selection & Billing Period Bar --}}
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
                <button @click="openCreateMruModal()" class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition shrink-0 cursor-pointer" title="Create New MRU Workspace">
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
                <button @click="showNewCycleModal = true" class="px-2.5 py-1.5 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 text-blue-700 dark:text-cyan-300 rounded-xl text-xs font-bold border border-blue-200 dark:border-blue-800/80 transition flex items-center gap-1 shrink-0 cursor-pointer" title="New Billing Cycle">
                    <span>⚡</span> + New Cycle
                </button>
                
                <!-- Smart Sync Missing Action Button -->
                <template x-if="counts.missing_pdf > 0">
                    <button @click="syncMissingBills()" :disabled="syncingMissing" class="px-3 py-1.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-xl text-xs font-bold shadow-sm flex items-center gap-1.5 transition active:scale-95 whitespace-nowrap shrink-0 cursor-pointer" title="Download only missing/failed bills for this MRU & Period">
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
