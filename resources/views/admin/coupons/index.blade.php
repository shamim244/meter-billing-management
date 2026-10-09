<x-admin-layout>
    <x-slot name="header">
        Coupon Code Campaigns & Discounts
    </x-slot>

    <div class="space-y-6" x-data="couponsIndexApp()">
        @include('admin.coupons.partials.metrics', ['stats' => $stats])
        @include('admin.coupons.partials.toolbar', [
            'search' => $search,
            'typeFilter' => $typeFilter,
            'statusFilter' => $statusFilter
        ])
        @include('admin.coupons.partials.bulk-bar')
        @include('admin.coupons.partials.table', ['coupons' => $coupons])
    </div>

    <!-- Decoupled Script & Cache Busting -->
    <script src="{{ asset('js/admin/coupons/coupons-index-app.js') }}?v={{ file_exists(public_path('js/admin/coupons/coupons-index-app.js')) ? filemtime(public_path('js/admin/coupons/coupons-index-app.js')) : time() }}"></script>
</x-admin-layout>
