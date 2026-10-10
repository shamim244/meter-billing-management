<x-user-panel-layout>
    <x-slot name="header">
        Account Overview & Activity
    </x-slot>

    <div class="space-y-8">
        @include('user-panel.overview.hero')
        @include('user-panel.overview.metrics')
        @include('user-panel.overview.quick-links')
    </div>
</x-user-panel-layout>
