<x-app-layout>
    <div class="py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @include('payments.partials.index.header')
            @include('payments.partials.index.alerts')
            @include('payments.partials.index.kpi-cards')
            @include('payments.partials.index.table')
        </div>
    </div>
</x-app-layout>
