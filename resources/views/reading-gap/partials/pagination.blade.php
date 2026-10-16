<!-- Pagination Controls -->
<div class="px-4 py-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
    <div>
        Showing <span class="font-bold text-slate-700 dark:text-slate-300" x-text="consumers.length"></span> of <span class="font-bold text-slate-700 dark:text-slate-300" x-text="pagination.total"></span> consumers
    </div>
    <div class="flex items-center gap-2">
        <button type="button" @click="fetchData(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                class="px-2.5 py-1 rounded-lg border border-slate-300 dark:border-slate-700 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-800 transition cursor-pointer">
            Prev
        </button>
        <span class="font-mono font-semibold" x-text="pagination.current_page + ' / ' + (pagination.last_page || 1)"></span>
        <button type="button" @click="fetchData(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page"
                class="px-2.5 py-1 rounded-lg border border-slate-300 dark:border-slate-700 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-800 transition cursor-pointer">
            Next
        </button>
    </div>
</div>
