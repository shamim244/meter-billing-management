<x-admin-layout>
    <x-slot name="header">
        Financial Analytics & Revenue Performance
    </x-slot>

    <div class="space-y-6">
        @include('admin.payments.nav')
        @include('admin.payments.partials.analytics.kpi-cards')

        <!-- Volume & Breakdown Grids -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @include('admin.payments.partials.analytics.channel-distribution')
            @include('admin.payments.partials.analytics.status-breakdown')
        </div>

        <!-- Monthly Trends & Top Billing Agents -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @include('admin.payments.partials.analytics.trends-table')
            @include('admin.payments.partials.analytics.top-agents')
        </div>
    </div>
</x-admin-layout>
