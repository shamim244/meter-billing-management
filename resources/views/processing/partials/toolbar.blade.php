<!-- Workspace & Dynamic Billing Period Toolbar -->
<div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
        
        <!-- MRU Selector -->
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">🏘️ MRU Workspace</label>
            <select x-model="selectedMruId" @change="onMruChange()" class="w-full text-xs font-bold border-slate-300 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500 shadow-sm">
                @forelse($mrus as $mru)
                    <option value="{{ $mru->id }}">{{ $mru->code }} - {{ $mru->name }} ({{ $mru->consumer_accounts_count }} CAs)</option>
                @empty
                    <option value="">No MRUs Found</option>
                @endforelse
            </select>
        </div>

        <!-- Billing Cycle Selector (Dynamically loaded from existing sessions) -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">📅 Billing Cycle</label>
                <template x-if="availablePeriods.length > 0">
                    <span class="text-[10px] text-blue-600 dark:text-cyan-400 font-mono font-bold" x-text="availablePeriods.length + ' cycle(s) found'"></span>
                </template>
            </div>

            <!-- Dropdown if existing cycles exist -->
            <template x-if="availablePeriods.length > 0">
                <select x-model="selectedPeriodKey" @change="onPeriodKeyChange()" class="w-full text-xs font-bold border-slate-300 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500 shadow-sm">
                    <template x-for="p in availablePeriods" :key="p.key">
                        <option :value="p.key" x-text="p.label"></option>
                    </template>
                    <option value="custom">⚡ + Custom / Other Month...</option>
                </select>
            </template>

            <!-- No cycles alert & quick launch -->
            <template x-if="availablePeriods.length === 0">
                <div class="flex items-center gap-1.5">
                    <button @click="openNewCycleForCurrentMru()" class="w-full py-2.5 bg-amber-50 dark:bg-amber-950/60 hover:bg-amber-100 dark:hover:bg-amber-900/60 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 rounded-xl text-xs font-bold flex items-center justify-center gap-1 transition">
                        <span>⚡ + Create First Cycle</span>
                    </button>
                </div>
            </template>
        </div>

        <!-- Month & Year Selector (Visible if custom selected or no cycles) -->
        <div x-show="selectedPeriodKey === 'custom' || availablePeriods.length === 0" class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Month</label>
                <select x-model="selectedMonth" @change="onCustomDateChange()" class="w-full text-xs font-bold border-slate-300 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white py-2 px-2 focus:ring-2 focus:ring-blue-500">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}">{{ date('M', mktime(0, 0, 0, $m, 1)) }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Year</label>
                <select x-model="selectedYear" @change="onCustomDateChange()" class="w-full text-xs font-bold border-slate-300 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white py-2 px-2 focus:ring-2 focus:ring-blue-500">
                    @php
                        $currYr = (int) date('Y');
                        $yrs = range($currYr - 3, $currYr + 3);
                        rsort($yrs);
                    @endphp
                    @foreach($yrs as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Placeholder when custom is not open to keep 4-column layout aligned -->
        <div x-show="selectedPeriodKey !== 'custom' && availablePeriods.length > 0" class="hidden lg:block">
            <div class="text-[11px] text-slate-400 dark:text-slate-500 font-medium pb-2">
                Active Cycle: <strong class="text-slate-800 dark:text-slate-200 font-mono" x-text="getCurrentPeriodLabel()"></strong>
            </div>
        </div>

        <!-- 1-Click Pipeline Button -->
        <div>
            <button @click="runFullPipeline()" :disabled="pipelineRunning || downloaderRunning || parserRunning || stats.total_cas === 0" class="w-full py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition active:scale-95 disabled:opacity-40 flex items-center justify-center gap-2">
                <span x-show="!pipelineRunning">⚡ Run Full Pipeline</span>
                <span x-show="pipelineRunning" class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Running Pipeline...
                </span>
            </button>
        </div>

    </div>
</div>
