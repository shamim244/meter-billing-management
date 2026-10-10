<x-admin-layout>
    <x-slot name="header">
        Coupon Analytics — {{ $coupon->code }}
    </x-slot>

    <div class="space-y-6">
        @include('admin.coupons.partials.show.toolbar')
        @include('admin.coupons.partials.show.hero')
        @include('admin.coupons.partials.show.metrics')
        @include('admin.coupons.partials.show.slabs')
        @include('admin.coupons.partials.show.audit-table')
    </div>
</x-admin-layout>
