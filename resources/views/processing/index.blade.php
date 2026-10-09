<x-app-layout>
    <div x-data="processingHubApp()" x-init="init()" class="py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- 1. Header Bar -->
            @include('processing.partials.header')

            <!-- 2. Workspace & Billing Period Toolbar -->
            @include('processing.partials.toolbar')

            <!-- 3. Two Core Processing Control Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @include('processing.partials.downloader-card')
                @include('processing.partials.parser-card')
            </div>

            <!-- 4. Real-time Live Log / Terminal Output -->
            @include('processing.partials.terminal')

            <!-- 5. Modal: New Billing Cycle -->
            @include('processing.partials.cycle-modal')

        </div>
    </div>

    <!-- Server Configuration Bridge -->
    <script>
        window.processingConfig = {
            mruPeriodsMap: @js($mruPeriodsMap),
            selectedMruId: '{{ $selectedMruId }}',
            selectedPeriodKey: '{{ $selectedMonth }}_{{ $selectedYear }}',
            selectedMonth: '{{ $selectedMonth }}',
            selectedYear: '{{ $selectedYear }}',
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
    <script src="{{ asset('js/processing/processing-hub-app.js') }}?v={{ filemtime(public_path('js/processing/processing-hub-app.js')) }}"></script>
</x-app-layout>
