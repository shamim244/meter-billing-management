<x-app-layout>
    <div class="py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @include('wallet.partials.header')
            @include('wallet.partials.frozen-banner')
            @include('wallet.partials.kpi-cards')
            @include('wallet.partials.filter-bar')
            @include('wallet.partials.table')
        </div>
    </div>
</x-app-layout>
