<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="mailboxApp()">
        @include('admin.notifications.partials.mailbox.header')
        @include('admin.notifications.partials.mailbox.nav-tabs')
        @include('admin.notifications.partials.mailbox.alerts')

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            @include('admin.notifications.partials.mailbox.sidebar', [
                'mailboxes' => $mailboxes,
                'selectedAddress' => $selectedAddress
            ])
            @include('admin.notifications.partials.mailbox.table', [
                'messages' => $messages,
                'selectedAddress' => $selectedAddress
            ])
        </div>

        @include('admin.notifications.partials.mailbox.modal-read')
        @include('admin.notifications.partials.mailbox.modal-compose', [
            'mailboxes' => $mailboxes,
            'selectedAddress' => $selectedAddress
        ])
    </div>

    <!-- Decoupled Script & Cache Busting -->
    <script src="{{ asset('js/admin/notifications/mailbox-app.js') }}?v={{ file_exists(public_path('js/admin/notifications/mailbox-app.js')) ? filemtime(public_path('js/admin/notifications/mailbox-app.js')) : time() }}"></script>
</x-admin-layout>
