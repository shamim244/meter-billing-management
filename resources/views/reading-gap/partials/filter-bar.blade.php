<!-- Filter and Search Bar -->
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-3">
    <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        <!-- Status Filter Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none text-xs font-bold">
            <button type="button" @click="setTab('all')" 
                    :class="activeTab === 'all' ? 'bg-brand-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'"
                    class="px-3 py-1.5 rounded-xl transition whitespace-nowrap cursor-pointer">
                All (<span x-text="kpis.total_consumers || 0"></span>)
            </button>
            <button type="button" @click="setTab('under_posted')" 
                    :class="activeTab === 'under_posted' ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'"
                    class="px-3 py-1.5 rounded-xl transition whitespace-nowrap cursor-pointer">
                ⚖️ Under-Posted (<span x-text="kpis.under_posted_count || 0"></span>)
            </button>
            <button type="button" @click="setTab('over_posted')" 
                    :class="activeTab === 'over_posted' ? 'bg-amber-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'"
                    class="px-3 py-1.5 rounded-xl transition whitespace-nowrap cursor-pointer">
                ⚠️ Over-Posted (<span x-text="kpis.over_posted_count || 0"></span>)
            </button>
            <button type="button" @click="setTab('stale')" 
                    :class="activeTab === 'stale' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'"
                    class="px-3 py-1.5 rounded-xl transition whitespace-nowrap cursor-pointer">
                ⏱️ Stale >90d (<span x-text="kpis.stale_inspections_count || 0"></span>)
            </button>
            <button type="button" @click="setTab('in_sync')" 
                    :class="activeTab === 'in_sync' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'"
                    class="px-3 py-1.5 rounded-xl transition whitespace-nowrap cursor-pointer">
                🟢 In-Sync (<span x-text="kpis.in_sync_count || 0"></span>)
            </button>
            <button type="button" @click="setTab('uninspected')" 
                    :class="activeTab === 'uninspected' ? 'bg-slate-700 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'"
                    class="px-3 py-1.5 rounded-xl transition whitespace-nowrap cursor-pointer">
                ❓ Uninspected (<span x-text="kpis.uninspected_count || 0"></span>)
            </button>
        </div>

        <!-- Controls: MRU & Search -->
        <div class="flex items-center gap-2">
            <!-- MRU Dropdown -->
            <select x-model="selectedMru" @change="fetchData(1)"
                    class="text-xs font-semibold py-1.5 pl-3 pr-8 rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 shadow-xs">
                <option value="">All MRUs</option>
                @foreach($mrus as $mru)
                    <option value="{{ $mru->id }}">{{ $mru->code }} ({{ $mru->name }})</option>
                @endforeach
            </select>

            <!-- Search Input -->
            <div class="relative flex-1 sm:w-56">
                <input type="text" x-model="searchQuery" @input.debounce.300ms="fetchData(1)"
                       placeholder="Search CA / Name / Meter..."
                       class="w-full text-xs py-1.5 pl-8 pr-3 rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 shadow-xs placeholder-slate-400">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>
    </div>
</div>
