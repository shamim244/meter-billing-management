<x-admin-layout>
    <x-slot name="header">
        Payment Gateway & Channel Settings
    </x-slot>

    <div x-data="paymentSettingsApp(window.paymentSettingsConfig)" class="space-y-6">
        @include('admin.payments.nav')
        @include('admin.payments.partials.settings.alerts')

        <form action="{{ route('admin.payments.settings.update') }}" method="POST" class="space-y-6">
            @csrf
            @include('admin.payments.partials.settings.channel-toggles')
            @include('admin.payments.partials.settings.razorpay-card')
            @include('admin.payments.partials.settings.cashfree-card')
            @include('admin.payments.partials.settings.min-amount-card')
            @include('admin.payments.partials.settings.manual-upi-card')
            @include('admin.payments.partials.settings.bank-transfer-card')
            @include('admin.payments.partials.settings.wallet-thresholds-card')
            @include('admin.payments.partials.settings.form-actions')
        </form>
    </div>

    <!-- Server Configuration Bridge & App Script -->
    <script>
        window.paymentSettingsConfig = {
            pgEnabled: {{ $settings['pg_enabled'] ? 'true' : 'false' }},
            cashfreeEnabled: {{ $settings['cashfree_enabled'] ? 'true' : 'false' }},
            razorpayEnabled: {{ $settings['razorpay_enabled'] ? 'true' : 'false' }},
            manualUpiEnabled: {{ $settings['manual_upi_enabled'] ? 'true' : 'false' }},
            bankTransferEnabled: {{ $settings['bank_transfer_enabled'] ? 'true' : 'false' }},
            activePgDriver: '{{ $settings['active_pg_driver'] }}'
        };
    </script>
    <script src="{{ asset('js/admin/payments/payment-settings-app.js') }}?v={{ file_exists(public_path('js/admin/payments/payment-settings-app.js')) ? filemtime(public_path('js/admin/payments/payment-settings-app.js')) : time() }}"></script>
</x-admin-layout>
