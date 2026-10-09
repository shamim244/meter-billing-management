<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="emailProvidersApp()">
        @include('admin.notifications.partials.providers.header')
        @include('admin.notifications.partials.providers.nav')
        @include('admin.notifications.partials.providers.alerts')
        @include('admin.notifications.partials.providers.providers-table')
        @include('admin.notifications.partials.providers.deliveries-table')
        @include('admin.notifications.partials.providers.modal-add')
        @include('admin.notifications.partials.providers.modal-edit')
        @include('admin.notifications.partials.providers.modal-test')
    </div>

    <!-- App Script -->
    <script src="{{ asset('js/admin/notifications/email-providers-app.js') }}?v={{ file_exists(public_path('js/admin/notifications/email-providers-app.js')) ? filemtime(public_path('js/admin/notifications/email-providers-app.js')) : time() }}"></script>
</x-admin-layout>
