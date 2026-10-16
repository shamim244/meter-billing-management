<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-gradient-to-br from-indigo-500 to-brand-600 text-white shadow-md text-lg">⚖️</span>
                    Reading Gap & Reconciliation Hub
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Field audit tracking, dial rollover protection & controlled gap recovery
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('reading-gap.export-csv') }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-xs">
                    <span>📑</span> Export Route CSV
                </a>
                <a href="{{ route('dashboard') }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-brand-600 hover:bg-brand-500 text-white shadow-xs transition">
                    <span>📊</span> Main Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6" x-data="readingGapWorkspace()" x-init="init()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @include('reading-gap.partials.kpi-cards')
            @include('reading-gap.partials.filter-bar')
            @include('reading-gap.partials.gap-table')
        </div>

        @include('reading-gap.partials.modals.meter-swap-modal')
        @include('dashboard.partials.modals.inspection-modal')
    </div>

    @push('scripts')
        <script>
            window.readingGapConfig = {
                dataUrl: "{{ route('reading-gap.data') }}",
                exportCsvUrl: "{{ route('reading-gap.export-csv') }}",
                recordSwapUrl: "{{ route('api.reading-gap.record-swap') }}",
                recordInspectionUrl: "{{ route('api.reading-gap.record-inspection') }}",
                updateModeUrl: "{{ route('api.reading-gap.update-mode') }}",
                csrfToken: "{{ csrf_token() }}",
                initialCa: "{{ $selectedCa ?? '' }}"
            };
        </script>
        <script src="{{ asset('js/reading-gap/reading-gap-app.js') }}?v={{ file_exists(public_path('js/reading-gap/reading-gap-app.js')) ? filemtime(public_path('js/reading-gap/reading-gap-app.js')) : time() }}"></script>
    @endpush
</x-app-layout>
