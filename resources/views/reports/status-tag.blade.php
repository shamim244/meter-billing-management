<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        @include('reports.partials.status-tag.header')
        @include('reports.partials.status-tag.filter-bar')

        <!-- Summary Distribution Bars -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @include('reports.partials.status-tag.status-cards')
            @include('reports.partials.status-tag.tag-cards')
        </div>

        @include('reports.partials.status-tag.table')
    </div>
</x-app-layout>
