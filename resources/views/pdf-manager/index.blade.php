<x-app-layout>
    <x-slot name="header">
        @include('pdf-manager.partials.header')
    </x-slot>

    <div x-data="pdfManager()" class="py-6 min-h-screen text-slate-800 dark:text-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- 1. Live Storage Analytics KPIs & Quota Bar -->
            @include('pdf-manager.partials.stats-header')

            <!-- 2. Billing Cycle PDF Cleanup & Storage Recovery -->
            @include('pdf-manager.partials.cycle-cleanup')

            <!-- 3. Filter & Sticky Batch Actions Bar -->
            @include('pdf-manager.partials.filter-bar')
            @include('pdf-manager.partials.batch-actions')

            <!-- 4. Table / Grid Header Control -->
            <div class="flex items-center justify-between">
                <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                    Showing <span class="font-bold text-slate-800 dark:text-slate-200">{{ $bills->firstItem() ?? 0 }}-{{ $bills->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-800 dark:text-slate-200">{{ $bills->total() }}</span> bill documents
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="selectAllOnPage()"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 transition">
                        Select Page (<span x-text="pageIds.length"></span>)
                    </button>

                    <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
                        <button type="button" 
                                @click="viewMode = 'table'" 
                                :class="viewMode === 'table' ? 'bg-white dark:bg-slate-900 shadow-sm text-brand-600 dark:text-brand-400 font-bold' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                                class="px-3 py-1 text-xs rounded-lg transition flex items-center gap-1">
                            <span>📋</span>
                            <span class="hidden sm:inline">Table</span>
                        </button>
                        <button type="button" 
                                @click="viewMode = 'grid'" 
                                :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-900 shadow-sm text-brand-600 dark:text-brand-400 font-bold' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                                class="px-3 py-1 text-xs rounded-lg transition flex items-center gap-1">
                            <span>🗃️</span>
                            <span class="hidden sm:inline">Cards</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 5. Data Views -->
            @include('pdf-manager.partials.table-view')
            @include('pdf-manager.partials.grid-view')

            <!-- 6. Pagination Controls -->
            @if($bills->hasPages())
                <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    {{ $bills->links() }}
                </div>
            @endif

            <!-- 7. Modals -->
            @include('pdf-manager.partials.modals.health-modal')
            @include('pdf-manager.partials.modals.upload-modal')
            @include('pdf-manager.partials.modals.delete-modal')

        </div>
    </div>

    <!-- Script Configuration Bridge -->
    <script>
        window.pdfManagerConfig = {
            viewMode: @json($viewMode),
            pageIds: @json($bills->pluck('id')->toArray()),
            mruId: @json($mruId),
            month: @json($month ?: now()->month),
            year: @json($year ?: now()->year),
            routes: {
                batchDownload: @json(route('pdf-manager.batch-download')),
                batchReparse: @json(route('pdf-manager.batch-reparse')),
                batchRedownload: @json(route('pdf-manager.batch-redownload')),
                batchDelete: @json(route('pdf-manager.batch-delete')),
                deleteSingle: @json(route('bills.delete-pdf')),
                healthCheck: @json(route('pdf-manager.health-check')),
                syncStorage: @json(route('pdf-manager.sync-storage')),
                purgeCycle: @json(route('pdf-manager.purge-cycle')),
                upload: @json(route('pdf-manager.upload')),
            },
            csrfToken: @json(csrf_token()),
        };
    </script>
    <script src="{{ asset('js/pdf-manager/pdf-manager-app.js') }}?v={{ filemtime(public_path('js/pdf-manager/pdf-manager-app.js')) }}"></script>
</x-app-layout>
