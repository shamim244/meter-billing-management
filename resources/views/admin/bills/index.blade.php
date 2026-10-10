<x-admin-layout>
    <x-slot name="header">
        Global Bills Inspector (All Tenants)
    </x-slot>

    <div class="space-y-6">
        @include('admin.bills.partials.index.header')
        @include('admin.bills.partials.index.filter-bar')
        @include('admin.bills.partials.index.table')
    </div>
</x-admin-layout>
