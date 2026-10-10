<div class="p-12 text-center space-y-3">
    <div class="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-slate-800 text-3xl flex items-center justify-center mx-auto text-slate-400">
        🎉
    </div>
    <h3 class="text-base font-bold text-slate-900 dark:text-white">No Bug Reports Found</h3>
    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto">
        @if(!empty($search) || $status !== 'all')
            No tickets match your active filter. Try resetting your search or filter.
        @else
            You haven't reported any issues yet. If you encounter any bugs or calculation problems while working, use the floating bug icon anytime to let our AI agent investigate!
        @endif
    </p>
    <div class="pt-2">
        <button type="button" @click="openNewReportModal()" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition">
            Report an Issue Now
        </button>
    </div>
</div>
