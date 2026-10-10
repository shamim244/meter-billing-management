<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        @include('reports.partials.quota.header')
        @include('reports.partials.quota.summary-cards')
        @include('reports.partials.quota.trend-table')
    </div>
</x-app-layout>
