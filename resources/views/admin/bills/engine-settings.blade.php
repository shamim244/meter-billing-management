<x-admin-layout>
    <x-slot name="header">
        NBPDCL Download & Extraction Engine
    </x-slot>

    <div class="space-y-8" x-data="engineSettingsManager()">
        @include('admin.bills.partials.toolbar')
        @include('admin.bills.partials.alerts')

        <!-- Settings Form -->
        <form action="{{ route('admin.bills.engine-settings.update') }}" method="POST" class="space-y-8">
            @csrf
            @include('admin.bills.partials.driver-section')
            @include('admin.bills.partials.extraction-section')
            @include('admin.bills.partials.spike-filter-section')
            @include('admin.bills.partials.colorization-section')
            @include('admin.bills.partials.connection-section')
            @include('admin.bills.partials.performance-section')
        </form>

        @include('admin.bills.partials.diagnostic-sandbox')
    </div>

    <!-- Decoupled Configuration Bridge -->
    <script>
        window.engineSettingsConfig = {
            globalSpikeMultiplier: {{ old('global_spike_multiplier', $settings['global_spike_multiplier'] ?? 2.0) }},
            minSpikeBuffer: {{ old('min_spike_unit_buffer', $settings['min_spike_unit_buffer'] ?? 30) }},
            agriMultiplier: {{ old('agriculture_spike_multiplier', $settings['agriculture_spike_multiplier'] ?? 4.0) }},
            commMultiplier: {{ old('commercial_spike_multiplier', $settings['commercial_spike_multiplier'] ?? 2.5) }},
            domMultiplier: {{ old('domestic_spike_multiplier', $settings['domestic_spike_multiplier'] ?? 2.0) }},
            colorizationEnabled: {{ old('dynamic_colorization_enabled', $settings['dynamic_colorization_enabled'] ?? true) ? 'true' : 'false' }},
            amountSafeCeiling: {{ old('color_amount_safe_ceiling', $settings['color_amount_safe_ceiling'] ?? 500) }},
            amountWarningCeiling: {{ old('color_amount_warning_ceiling', $settings['color_amount_warning_ceiling'] ?? 1500) }},
            amountDangerFloor: {{ old('color_amount_danger_floor', $settings['color_amount_danger_floor'] ?? 2500) }},
            unitsSafeCeiling: {{ old('color_units_safe_ceiling', $settings['color_units_safe_ceiling'] ?? 50) }},
            unitsWarningCeiling: {{ old('color_units_warning_ceiling', $settings['color_units_warning_ceiling'] ?? 120) }},
            unitsDangerFloor: {{ old('color_units_danger_floor', $settings['color_units_danger_floor'] ?? 200) }},
            currentMonth: {{ (int)date("n") }},
            currentYear: {{ (int)date("Y") }},
            diagnosticUrl: '{{ route("admin.bills.engine-settings.diagnostic") }}',
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
    <script src="{{ asset('js/admin/engine-settings-app.js') }}?v={{ filemtime(public_path('js/admin/engine-settings-app.js')) }}"></script>
</x-admin-layout>
