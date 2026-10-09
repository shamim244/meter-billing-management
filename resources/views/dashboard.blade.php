<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/dashboard/dashboard.css') }}?v={{ file_exists(public_path('css/dashboard/dashboard.css')) ? filemtime(public_path('css/dashboard/dashboard.css')) : time() }}">

    <div x-data="dashboardApp()" x-init="init()" @keydown.window="onKeyNav($event)" class="py-6 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors overflow-x-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Stats & Filter Header Controls -->
            @include('dashboard.partials.stats-header')

            <!-- Table View -->
            @include('dashboard.partials.table-view')

            <!-- Sliding Card Carousel View -->
            @include('dashboard.partials.card-view')

            <!-- Floating Toast Notification with Undo -->
            @include('dashboard.partials.toast')

            <!-- Modals -->
            @include('dashboard.partials.modals.create-mru-modal')
            @include('dashboard.partials.modals.existing-mru-popup')
            @include('dashboard.partials.modals.billing-cycle-modal')
            @include('dashboard.partials.modals.pdf-viewer-modal')
            @include('dashboard.partials.modals.quick-pull-modal')
            @include('dashboard.partials.modals.shortcuts-modal')
            @include('dashboard.partials.modals.tuning-modal')
            @include('dashboard.partials.modals.meter-history-modal')
            @include('dashboard.partials.modals.mobile-modal')
            @include('dashboard.partials.modals.bulk-mobile-modal')
            @include('dashboard.partials.modals.field-desk-modal')

        </div>
    </div>

    <!-- Dashboard Configuration Bridge -->
    <script>
        window.dashboardConfig = {
            csrfToken: '{{ csrf_token() }}',
            mruTopupUrl: '{{ route('wallet.index') }}',
            mruUpgradeUrl: '{{ route('user-panel.subscription') }}',
            shortcuts: @json(Auth::user()->getShortcutMap()),
            shortcutLabels: @json(Auth::user()->getShortcutLabels()),
            cardDensity: '{{ session('pref_card_density', 'compact') }}',
            amountSize: '{{ session('pref_amount_size', 'standard') }}',
            showRemarkPresets: {{ session('pref_remark_presets', false) ? 'true' : 'false' }},
            colorSettings: {!! json_encode($colorSettings ?? ['enabled' => true, 'amount_safe_ceiling' => 500, 'amount_warning_ceiling' => 1500, 'amount_danger_floor' => 2500, 'units_safe_ceiling' => 50, 'units_warning_ceiling' => 120, 'units_danger_floor' => 200]) !!},
            selectedMonth: {{ $selectedMonth }},
            selectedYear: {{ $selectedYear }},
            selectedMruId: '{{ $selectedMruId }}',
            mruPeriodsMap: @json($mruPeriodsMap ?? []),
            availableTags: @json($activeTags ?? []),
            defaultTag: '{{ $defaultTag ?? "OK" }}',
            fieldDeskDefaultCategoryId: '{{ isset($fieldDeskCategories) && count($fieldDeskCategories) > 0 ? $fieldDeskCategories->first()->id : 1 }}',
            counts: {
                all: {{ $totalPeriodBills ?? 0 }},
                pending: {{ $statusCounts['pending'] ?? 0 }},
                submitted: {{ $statusCounts['submitted'] ?? 0 }},
                critical: {{ $statusCounts['critical'] ?? 0 }},
                doubt: {{ $statusCounts['doubt'] ?? 0 }},
                missing_pdf: {{ $statusCounts['missing_pdf'] ?? 0 }},
                filtered_units: {{ $totalPeriodUnits ?? 0 }},
                filtered_amount: {{ $totalPeriodAmount ?? 0 }},
                total_consumers: {{ $totalConsumers ?? 0 }},
                basis_ok: {{ $statusCounts['basis_ok'] ?? 0 }},
                basis_lk: {{ $statusCounts['basis_lk'] ?? 0 }},
                basis_md: {{ $statusCounts['basis_md'] ?? 0 }},
                basis_pl: {{ $statusCounts['basis_pl'] ?? 0 }},
                basis_rn: {{ $statusCounts['basis_rn'] ?? 0 }}
            }
        };
    </script>
    <script src="{{ asset('js/dashboard/dashboard-app.js') }}?v={{ file_exists(public_path('js/dashboard/dashboard-app.js')) ? filemtime(public_path('js/dashboard/dashboard-app.js')) : time() }}"></script>
</x-app-layout>
