<!-- Table Pagination -->
<div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between text-xs">
    <div class="text-slate-500 dark:text-slate-400 font-medium">
        Showing <span class="font-bold text-slate-800 dark:text-white" x-text="items.length > 0 ? (pagination.from || 1) : 0"></span> to <span class="font-bold text-slate-800 dark:text-white" x-text="items.length > 0 ? Math.min((pagination.to || items.length), items.length) : 0"></span> of <span class="font-bold text-slate-800 dark:text-white" x-text="pagination.total"></span> records
    </div>
    <div class="flex items-center gap-1">
        <button @click="fetchData(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="px-3 py-1 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg font-bold text-slate-700 dark:text-slate-200 disabled:opacity-40 hover:bg-slate-100 dark:hover:bg-slate-600 transition">
            ⟨ Prev
        </button>
        <span class="px-3 py-1 font-bold text-slate-800 dark:text-white" x-text="'Page ' + pagination.current_page + ' of ' + pagination.last_page"></span>
        <button @click="fetchData(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="px-3 py-1 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg font-bold text-slate-700 dark:text-slate-200 disabled:opacity-40 hover:bg-slate-100 dark:hover:bg-slate-600 transition">
            Next ⟩
        </button>
    </div>
</div>
