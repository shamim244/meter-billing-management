<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="adminSubscriptionsApp()">
        @include('admin.subscriptions.partials.header')
        @include('admin.subscriptions.partials.alerts')
        @include('admin.subscriptions.partials.metrics')
        @include('admin.subscriptions.partials.policy-bar')
        @include('admin.subscriptions.partials.table')
        @include('admin.subscriptions.partials.modal-override')
    </div>

    <!-- Decoupled Alpine Component -->
    <script src="{{ asset('js/admin/subscriptions/subscriptions-app.js') }}?v={{ file_exists(public_path('js/admin/subscriptions/subscriptions-app.js')) ? filemtime(public_path('js/admin/subscriptions/subscriptions-app.js')) : time() }}"></script>
</x-admin-layout>
