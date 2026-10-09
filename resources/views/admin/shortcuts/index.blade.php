<x-admin-layout>
    <x-slot name="header">
        System Keyboard Shortcuts & Defaults
    </x-slot>

    <div x-data="adminShortcutsApp(window.adminShortcutsConfig)" class="space-y-8">
        @include('admin.shortcuts.partials.overview', ['stats' => $stats])
        @include('admin.shortcuts.partials.conflicts')
        @include('admin.shortcuts.partials.rebind-box')
        @include('admin.shortcuts.partials.form-grid', [
            'systemShortcuts' => $systemShortcuts,
            'labels' => $labels
        ])
    </div>

    <!-- Script Configuration Bridge & Decoupled Script -->
    <script>
        window.adminShortcutsConfig = {
            shortcuts: {{ Js::from($systemShortcuts) }},
            labels: {{ Js::from($labels) }}
        };
    </script>
    <script src="{{ asset('js/admin/shortcuts/shortcuts-app.js') }}?v={{ file_exists(public_path('js/admin/shortcuts/shortcuts-app.js')) ? filemtime(public_path('js/admin/shortcuts/shortcuts-app.js')) : time() }}"></script>
</x-admin-layout>
