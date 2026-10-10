<x-user-panel-layout>
    <x-slot name="header">
        General & Workspace Preferences
    </x-slot>

    <div class="space-y-8">
        @include('user-panel.preferences.header')

        <!-- Preferences Form -->
        <form method="POST" action="{{ route('user-panel.preferences.update') }}" class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl space-y-6">
            @csrf

            @include('user-panel.preferences.section-view-mode')
            @include('user-panel.preferences.section-card-density')
            @include('user-panel.preferences.section-automation')
            @include('user-panel.preferences.section-appearance')
        </form>
    </div>
</x-user-panel-layout>
