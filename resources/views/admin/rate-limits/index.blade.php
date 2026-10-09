<x-admin-layout>
    <x-slot name="header">
        API Rate Limiting & Automation Throttling
    </x-slot>

    <div x-data="rateLimitsApp(window.rateLimitsConfig)" class="space-y-8">
        @include('admin.rate-limits.partials.alerts')
        @include('admin.rate-limits.partials.overview', ['stats' => $stats])

        <form action="{{ route('admin.rate_limits.update') }}" method="POST" class="space-y-6">
            @csrf
            @include('admin.rate-limits.partials.master-toggle')
            @include('admin.rate-limits.partials.tiers-grid', ['defaults' => $defaults])
            @include('admin.rate-limits.partials.action-bar')
        </form>

        @include('admin.rate-limits.partials.modal-reset')
    </div>

    <script>
        window.rateLimitsConfig = {
            enabled: {{ $currentLimits['enabled'] ? 'true' : 'false' }},
            general: {{ (int) $currentLimits['general_per_minute'] }},
            review: {{ (int) $currentLimits['review_per_minute'] }},
            batch: {{ (int) $currentLimits['batch_per_minute'] }},
            login: {{ (int) $currentLimits['login_per_minute'] }},
            openapi: {{ (int) $currentLimits['openapi_per_minute'] }}
        };
    </script>
    <script src="{{ asset('js/admin/rate-limits/rate-limits-app.js') }}?v={{ file_exists(public_path('js/admin/rate-limits/rate-limits-app.js')) ? filemtime(public_path('js/admin/rate-limits/rate-limits-app.js')) : time() }}"></script>
</x-admin-layout>
