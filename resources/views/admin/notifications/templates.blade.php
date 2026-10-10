<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="notificationTemplatesApp(window.notificationTemplatesConfig)">
        @include('admin.notifications.partials.templates.header')
        @include('admin.notifications.partials.templates.nav')
        @include('admin.notifications.partials.templates.alerts')
        @include('admin.notifications.partials.templates.table', ['templates' => $templates])
        @include('admin.notifications.partials.templates.modal-edit')
        @include('admin.notifications.partials.templates.modal-preview')
    </div>

    <!-- Decoupled Script & Cache Busting -->
    <script>
        window.notificationTemplatesConfig = {
            previewUrl: '{{ route('admin.notifications.templates.preview') }}',
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
    <script src="{{ asset('js/admin/notifications/templates-app.js') }}?v={{ file_exists(public_path('js/admin/notifications/templates-app.js')) ? filemtime(public_path('js/admin/notifications/templates-app.js')) : time() }}"></script>
</x-admin-layout>
