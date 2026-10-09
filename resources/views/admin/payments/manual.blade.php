<x-admin-layout>
    <x-slot name="header">
        Manual Payment Verification Queue
    </x-slot>

    <div x-data="paymentsManualApp()" class="space-y-6">
        @include('admin.payments.nav')
        @include('admin.payments.partials.manual.alerts')
        @include('admin.payments.partials.manual.summary-cards', [
            'pendingPayments' => $pendingPayments,
            'totalPendingAmount' => $totalPendingAmount,
            'upiPendingCount' => $upiPendingCount,
            'bankPendingCount' => $bankPendingCount
        ])
        @include('admin.payments.partials.manual.filter-bar', [
            'modeFilter' => $modeFilter,
            'pendingPayments' => $pendingPayments,
            'upiPendingCount' => $upiPendingCount,
            'bankPendingCount' => $bankPendingCount,
            'search' => $search
        ])
        @include('admin.payments.partials.manual.table', [
            'pendingPayments' => $pendingPayments
        ])
        @include('admin.payments.partials.manual.modal-approve')
        @include('admin.payments.partials.manual.modal-reject')
        @include('admin.payments.partials.manual.modal-preview')
    </div>

    <!-- Decoupled Script & Cache Busting -->
    <script src="{{ asset('js/admin/payments/payments-manual-app.js') }}?v={{ file_exists(public_path('js/admin/payments/payments-manual-app.js')) ? filemtime(public_path('js/admin/payments/payments-manual-app.js')) : time() }}"></script>
</x-admin-layout>
