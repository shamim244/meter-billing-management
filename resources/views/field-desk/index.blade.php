<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/field-desk/field-desk.css') }}?v={{ file_exists(public_path('css/field-desk/field-desk.css')) ? filemtime(public_path('css/field-desk/field-desk.css')) : time() }}">

    <div x-data="fieldDeskApp()" x-init="initApp()"
         @keydown.escape.window="modals.create = false; modals.complete = false; modals.timeline = false; modals.edit = false"
         class="py-6 sm:py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Hero Header & Stats Banner -->
            @include('field-desk.partials.hero-kpi-banner')

            <!-- Filter & Search Toolbar -->
            @include('field-desk.partials.filter-toolbar')

            <!-- Feed Content Area -->
            @include('field-desk.partials.agenda-feed')

        </div>

        <!-- ================= MODALS & DRAWERS ================= -->
        @include('field-desk.partials.modals.create-action-modal')
        @include('field-desk.partials.modals.complete-action-modal')
        @include('field-desk.partials.modals.timeline-drawer')
        @include('field-desk.partials.modals.edit-action-modal')
        @include('field-desk.partials.modals.quick-gps-modal')
        @include('field-desk.partials.modals.quick-mobile-modal')

        <!-- Toast Notification -->
        @include('field-desk.partials.toast')
    </div>

    <!-- FieldDesk Configuration Bridge -->
    <script>
        window.fieldDeskConfig = {
            csrfToken: '{{ csrf_token() }}',
            initialCa: @json($initialCa ?? ''),
            defaultCategoryId: '{{ $categories->first()?->id ?? 1 }}',
            apiDataUrl: '{{ route('api.field-desk.data') }}',
            apiStoreUrl: '{{ route('api.field-desk.store') }}',
            counts: {
                due_today: {{ $counts['due_today'] ?? 0 }},
                overdue: {{ $counts['overdue'] ?? 0 }},
                upcoming: {{ $counts['upcoming'] ?? 0 }},
                resolved_this_month: {{ $counts['resolved_this_month'] ?? 0 }},
                total_active: {{ $counts['total_active'] ?? 0 }}
            }
        };
    </script>
    <script src="{{ asset('js/field-desk/field-desk-app.js') }}?v={{ file_exists(public_path('js/field-desk/field-desk-app.js')) ? filemtime(public_path('js/field-desk/field-desk-app.js')) : time() }}"></script>
</x-app-layout>
