<x-admin-layout title="Payments & Verification Queue">
    <div x-data="adminPaymentsIndexApp()" class="space-y-6">
        @include('admin.payments.nav')
        @include('admin.payments.partials.index.header')
        @include('admin.payments.partials.index.kpi-cards')
        @include('admin.payments.partials.index.filters')
        @include('admin.payments.partials.index.payments-table')
        @include('admin.payments.partials.index.modal-approve')
        @include('admin.payments.partials.index.modal-reject')
        @include('admin.payments.partials.index.modal-refund')
        @include('admin.payments.partials.index.modal-screenshot')
    </div>

    <!-- App Script -->
    <script src="{{ asset('js/admin/payments/payments-index-app.js') }}?v={{ file_exists(public_path('js/admin/payments/payments-index-app.js')) ? filemtime(public_path('js/admin/payments/payments-index-app.js')) : time() }}"></script>
</x-admin-layout>
