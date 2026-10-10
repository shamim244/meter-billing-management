<!-- Pagination Bar -->
<div x-show="!loading && pagination.total > pagination.per_page" class="flex items-center justify-between pt-4 text-xs text-slate-500">
    <div>
        Showing <span class="font-bold text-slate-800 dark:text-slate-200" x-text="((pagination.current_page - 1) * pagination.per_page) + 1"></span>
        to <span class="font-bold text-slate-800 dark:text-slate-200" x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span>
        of <span class="font-bold text-slate-800 dark:text-slate-200" x-text="pagination.total"></span> actions
    </div>
    <div class="flex items-center gap-2">
        <button :disabled="pagination.current_page <= 1"
                @click="goToPage(pagination.current_page - 1)"
                :class="pagination.current_page <= 1 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-200 dark:hover:bg-slate-700'"
                class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 font-bold transition">
            Previous
        </button>
        <button :disabled="pagination.current_page >= pagination.last_page"
                @click="goToPage(pagination.current_page + 1)"
                :class="pagination.current_page >= pagination.last_page ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-200 dark:hover:bg-slate-700'"
                class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 font-bold transition">
            Next
        </button>
    </div>
</div>
