<!-- 2. Search Bar Container -->
<div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
    <div class="relative w-full flex items-center">
        <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" 
               x-model="searchQuery" 
               @input.debounce.300ms="fetchData(1)" 
               @keydown.enter.prevent="fetchData(1)" 
               @keydown.escape.prevent="clearSearch()" 
               placeholder="Search CA / Name / Meter / Mobile / Tariff..." 
               class="w-full text-xs rounded-2xl border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-white pl-10 pr-16 py-3 focus:ring-blue-500 focus:border-blue-500 shadow-inner" />
        <div class="absolute right-3.5 flex items-center gap-1.5">
            <span x-show="loading && searchQuery" class="text-blue-500">
                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </span>
            <button x-show="searchQuery" 
                    @click="clearSearch()" 
                    type="button" 
                    class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-200/50 dark:hover:bg-slate-700 rounded-lg text-xs font-bold transition cursor-pointer"
                    title="Clear search (Esc)">
                ✕
            </button>
        </div>
    </div>
</div>
