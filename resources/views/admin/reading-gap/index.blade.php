<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-white flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-gradient-to-br from-amber-500 to-rose-600 text-white shadow-md text-lg">⚖️</span>
                    System-Wide Reading Gap & Unbilled Energy Intelligence
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                    Multi-tenant gap analytics, revenue at risk, MRU severity rankings, and field audit trail
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.reading-gap.export-audit') }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-700 bg-slate-900 text-slate-200 hover:bg-slate-800 transition shadow-xs">
                    <span>📑</span> Export Audit CSV
                </a>
                <a href="{{ route('admin.bills.engine-settings') }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-xs transition">
                    <span>⚙️</span> Safe Ceilings
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="adminReadingGapApp()" x-init="init()">
        @include('admin.reading-gap.partials.kpi-banner')
        @include('admin.reading-gap.partials.mru-gap-table')
        @include('admin.reading-gap.partials.inspections-log-table')
    </div>

    @push('scripts')
        <script>
            window.adminReadingGapConfig = {
                dataUrl: "{{ route('admin.reading-gap.data') }}",
                exportUrl: "{{ route('admin.reading-gap.export-audit') }}"
            };
        </script>
        <script src="{{ asset('js/admin/admin-reading-gap-app.js') }}?v={{ file_exists(public_path('js/admin/admin-reading-gap-app.js')) ? filemtime(public_path('js/admin/admin-reading-gap-app.js')) : time() }}"></script>
    @endpush
</x-admin-layout>
