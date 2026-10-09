{{-- Loading Indicator & Empty State --}}
<div>
    <!-- Loading Indicator (Cold Initial Load Only) -->
    <div x-show="loading && items.length === 0" class="flex justify-center py-12">
        <div class="flex items-center gap-3 px-5 py-3 bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 text-sm font-medium">
            <svg class="animate-spin h-5 w-5 text-blue-600 dark:text-cyan-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            Loading bill records...
        </div>
    </div>

    <!-- In-Place Filtering Slim Top Progress Bar -->
    <div x-show="loading && items.length > 0" class="w-full bg-slate-100 dark:bg-slate-800 h-1 rounded-full overflow-hidden transition-all shadow-inner">
        <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-500 h-full w-full animate-pulse"></div>
    </div>

    <!-- No Data State -->
    <div x-show="!loading && items.length === 0" class="bg-white dark:bg-slate-900 p-12 text-center rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="w-16 h-16 bg-blue-50 dark:bg-blue-950/40 text-blue-500 dark:text-blue-400 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200">No bills found for this filter</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1 mb-6">There are no records matching your current filter. You can switch filter pills or create a new cycle.</p>
        <div class="flex items-center justify-center gap-3">
            <button @click="filterStatus = 'all'; basisFilter = 'all'; localStorage.setItem('dashboard_basis_filter', 'all'); fetchData(1)" class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition cursor-pointer">
                📋 View All Bills (<span x-text="counts.all ?? 0"></span>)
            </button>
            <button @click="showNewCycleModal = true" class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer">
                <span>⚡</span> New Billing Cycle
            </button>
        </div>
    </div>
</div>
