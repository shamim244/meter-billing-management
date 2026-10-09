<x-user-panel-layout>
    <x-slot name="header">
        API Keys & Field Automation Tokens
    </x-slot>

    <div x-data="userApiKeysManager()" class="space-y-8">
        @include('user-panel.api-keys.partials.header')
        @include('user-panel.api-keys.partials.alerts')
        @include('user-panel.api-keys.partials.secret-banner')
        @include('user-panel.api-keys.partials.metrics')
        @include('user-panel.api-keys.partials.table')
        @include('user-panel.api-keys.partials.integration-card')
        @include('user-panel.api-keys.partials.modal-create')
        @include('user-panel.api-keys.partials.modal-revoke')
    </div>

    <!-- Script Decoupling & Cache Busting -->
    <script src="{{ asset('js/user-panel/api-keys-app.js') }}?v={{ file_exists(public_path('js/user-panel/api-keys-app.js')) ? filemtime(public_path('js/user-panel/api-keys-app.js')) : time() }}"></script>
</x-user-panel-layout>
