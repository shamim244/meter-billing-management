<x-admin-layout>
    <x-slot name="header">
        Payment Gateway Testing & Sandbox Simulator
    </x-slot>

    <div x-data="paymentSimulatorApp(window.paymentSimulatorConfig)" class="space-y-6">
        @include('admin.payments.nav')
        @include('admin.payments.partials.simulator.alerts')
        @include('admin.payments.partials.simulator.banner')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @include('admin.payments.partials.simulator.checkout-simulator', ['users' => $users])
            @include('admin.payments.partials.simulator.webhook-tester')
        </div>

        @include('admin.payments.partials.simulator.diagnostics', ['diagnostics' => $diagnostics])
        @include('admin.payments.partials.simulator.recent-feed', ['recentPayments' => $recentPayments])
    </div>

    <!-- Script Configuration Bridge & Decoupled Script -->
    <script>
        window.paymentSimulatorConfig = {
            webhookGateway: 'razorpay',
            webhookEvent: 'payment.captured',
            webhookAmount: 1500,
            webhookUrl: '{{ route('admin.payments.simulator.webhook', [], false) }}',
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
    <script src="{{ asset('js/admin/payments/payments-simulator-app.js') }}?v={{ file_exists(public_path('js/admin/payments/payments-simulator-app.js')) ? filemtime(public_path('js/admin/payments/payments-simulator-app.js')) : time() }}"></script>
</x-admin-layout>
