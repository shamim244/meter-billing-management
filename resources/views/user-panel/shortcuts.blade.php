<x-user-panel-layout>
    <x-slot name="header">
        Keyboard Shortcuts & Keybinding Combos
    </x-slot>

    <div x-data="userShortcutsApp(window.userShortcutsConfig)" class="space-y-8">
        @include('user-panel.shortcuts.partials.header')
        @include('user-panel.shortcuts.partials.alerts')
        @include('user-panel.shortcuts.partials.rebind-box')
        @include('user-panel.shortcuts.partials.assignments-grid')
        @include('user-panel.shortcuts.partials.safety-notice')
    </div>

    <!-- Script Configuration Bridge & Decoupled Script -->
    <script>
        window.userShortcutsConfig = {
            shortcuts: {{ Js::from($shortcuts) }},
            labels: {{ Js::from($labels) }},
            saveUrl: '{{ route('user.shortcuts.save', [], false) }}',
            resetUrl: '{{ route('user.shortcuts.reset', [], false) }}',
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
    <script src="{{ asset('js/user-panel/shortcuts-app.js') }}?v={{ file_exists(public_path('js/user-panel/shortcuts-app.js')) ? filemtime(public_path('js/user-panel/shortcuts-app.js')) : time() }}"></script>
</x-user-panel-layout>
