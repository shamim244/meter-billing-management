<x-admin-layout>
    <x-slot name="header">
        Universal Cloud Migration & Server Portability
    </x-slot>

    <div class="space-y-8" x-data="adminMigrationApp()">
        @include('admin.migration.partials.header')
        @include('admin.migration.partials.alerts')
        @include('admin.migration.partials.preflight')
        @include('admin.migration.partials.operations')
        @include('admin.migration.partials.bundles-table')
        @include('admin.migration.partials.mobile-discovery')
    </div>

    <!-- Decoupled Alpine Component -->
    <script src="{{ asset('js/admin/migration/migration-app.js') }}?v={{ file_exists(public_path('js/admin/migration/migration-app.js')) ? filemtime(public_path('js/admin/migration/migration-app.js')) : time() }}"></script>
</x-admin-layout>
