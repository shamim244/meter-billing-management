<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        @include('reports.partials.index.header')
        @include('reports.partials.index.sub-reports')
        @include('reports.partials.index.roi-metrics')
        @include('reports.partials.index.status-breakdown')
        @include('reports.partials.index.quota-utilization')
    </div>
</x-app-layout>
