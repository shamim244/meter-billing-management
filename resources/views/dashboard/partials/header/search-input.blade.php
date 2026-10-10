<!-- 2. Search Bar Container -->
<div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
    <div class="relative w-full">
        <input type="text" x-model="searchQuery" @input.debounce.300ms="fetchData(1)" placeholder="Search CA / Name / Meter..." class="w-full text-xs rounded-2xl border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-white pl-10 pr-4 py-3 focus:ring-blue-500 focus:border-blue-500 shadow-inner" />
        <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </div>
</div>
