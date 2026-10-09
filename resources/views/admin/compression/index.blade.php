<x-admin-layout>
    <x-slot name="header">
        Adaptive Compression & Edge Optimization
    </x-slot>

    <div class="space-y-8" x-data="compressionDashboard()">
        @include('admin.compression.partials.header')
        @include('admin.compression.partials.alerts')
        @include('admin.compression.partials.extension-grid')
        @include('admin.compression.partials.fallback-hierarchy')
        @include('admin.compression.partials.diagnostic-benchmarker')
        @include('admin.compression.partials.governance-form')
    </div>

    <!-- Server Configuration Bridge & App Script -->
    <script>
        window.compressionConfig = {
            benchmark: @json($benchmark),
            diagnosticUrl: "{{ route('admin.compression.diagnostic') }}",
            csrfToken: "{{ csrf_token() }}"
        };
    </script>
    <script src="{{ asset('js/admin/compression/compression-manager-app.js') }}?v={{ file_exists(public_path('js/admin/compression/compression-manager-app.js')) ? filemtime(public_path('js/admin/compression/compression-manager-app.js')) : time() }}"></script>
</x-admin-layout>
