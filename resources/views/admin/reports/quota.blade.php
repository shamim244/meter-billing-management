<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('admin.reports.partials.quota.header')
        @include('admin.reports.partials.quota.nav-tabs')
        @include('admin.reports.partials.quota.summary-totals')
        @include('admin.reports.partials.quota.table')
    </div>
</x-admin-layout>
