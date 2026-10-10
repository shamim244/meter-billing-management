<x-admin-layout>
    <x-slot name="header">
        Payment Audit Trail & Administrative Log
    </x-slot>

    <div class="space-y-6">
        @include('admin.payments.nav')
        @include('admin.payments.partials.audit.filter-bar')
        @include('admin.payments.partials.audit.table')
    </div>
</x-admin-layout>
