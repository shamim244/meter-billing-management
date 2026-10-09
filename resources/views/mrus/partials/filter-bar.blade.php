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
