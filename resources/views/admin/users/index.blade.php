<x-admin-layout>
    <x-slot name="header">
        Billing Agents & User Management
    </x-slot>

    <div class="space-y-6" x-data="adminUsersIndexApp()">
        @include('admin.users.partials.index.stats')
        @include('admin.users.partials.index.toolbar')
        @include('admin.users.partials.index.bulk-actions')
        @include('admin.users.partials.index.table')
    </div>

    <!-- Decoupled Alpine Component -->
    <script src="{{ asset('js/admin/users/users-index-app.js') }}?v={{ file_exists(public_path('js/admin/users/users-index-app.js')) ? filemtime(public_path('js/admin/users/users-index-app.js')) : time() }}"></script>
</x-admin-layout>
