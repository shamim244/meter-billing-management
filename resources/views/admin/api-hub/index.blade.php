<x-admin-layout>
    <x-slot name="header">
        API & Field Automation Control Hub
    </x-slot>

    <div x-data="apiHubManager()" class="space-y-8">
        @include('admin.api-hub.partials.notifications')
        @include('admin.api-hub.partials.overview-header')
        @include('admin.api-hub.partials.tab-analytics')

        <!-- SHARED CONFIGURATION FORM (Handles Tabs 2, 3, 4) -->
        <form action="{{ route('admin.api_hub.settings.update') }}" method="POST" class="space-y-6">
            @csrf
            @include('admin.api-hub.partials.form-hidden-inputs')
            @include('admin.api-hub.partials.tab-features')
            @include('admin.api-hub.partials.tab-ratelimits')
            @include('admin.api-hub.partials.tab-policies')
        </form>

        @include('admin.api-hub.partials.tab-keys')
        @include('admin.api-hub.partials.modals.reset-modal')
        @include('admin.api-hub.partials.modals.purge-analytics-modal')
        @include('admin.api-hub.partials.modals.revoke-key-modal')
    </div>

    <!-- Decoupled Configuration Bridge -->
    <script>
        window.apiHubConfig = {
            apiMaster: {{ $settings['api_master_enabled'] ? 'true' : 'false' }},
            userKeys: {{ $settings['user_keys_enabled'] ? 'true' : 'false' }},
            featureAutomation: {{ $settings['feature_automation_enabled'] ? 'true' : 'false' }},
            featureMobileSync: {{ $settings['feature_mobile_sync_enabled'] ? 'true' : 'false' }},
            featureBatchSync: {{ $settings['feature_batch_sync_enabled'] ? 'true' : 'false' }},
            featureConsumerUpdates: {{ $settings['feature_consumer_updates_enabled'] ? 'true' : 'false' }},
            publicDocs: {{ $settings['public_docs_enabled'] ? 'true' : 'false' }},
            rateLimiting: {{ $settings['rate_limiting_enabled'] ? 'true' : 'false' }},
            allowPermanent: {{ $settings['allow_permanent_keys'] ? 'true' : 'false' }},
            general: {{ (int) $settings['general_per_minute'] }},
            review: {{ (int) $settings['review_per_minute'] }},
            batch: {{ (int) $settings['batch_per_minute'] }},
            login: {{ (int) $settings['login_per_minute'] }},
            openapi: {{ (int) $settings['openapi_per_minute'] }},
            maxKeys: {{ (int) $settings['max_keys_per_user'] }},
            defaultLifetime: {{ (int) $settings['default_key_lifetime_days'] }}
        };
    </script>
    <script src="{{ asset('js/admin/api-hub-app.js') }}?v={{ filemtime(public_path('js/admin/api-hub-app.js')) }}"></script>
</x-admin-layout>
