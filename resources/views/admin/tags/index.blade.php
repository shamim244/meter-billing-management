<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="adminTagsApp()">
        @include('admin.tags.partials.header')
        @include('admin.tags.partials.alerts')
        @include('admin.tags.partials.table', ['tags' => $tags, 'defaultTag' => $defaultTag])
        @include('admin.tags.partials.delete-forms', ['tags' => $tags, 'defaultTag' => $defaultTag])
        @include('admin.tags.partials.modal-add')
    </div>

    <!-- Decoupled Script & Cache Busting -->
    <script src="{{ asset('js/admin/tags/tags-app.js') }}?v={{ file_exists(public_path('js/admin/tags/tags-app.js')) ? filemtime(public_path('js/admin/tags/tags-app.js')) : time() }}"></script>
</x-admin-layout>
