<x-app-layout>
    <!-- Razorpay Standard Checkout JS -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <!-- Cashfree Web JS SDK v3 -->
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>

    <div x-data="paymentCheckoutApp(window.paymentCheckoutConfig)" class="py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @include('payments.partials.header')
            @include('payments.partials.alerts')

            <form action="{{ route('payments.store') }}" method="POST" enctype="multipart/form-data" @submit="handleCheckout($event)" class="space-y-6">
                @csrf
                <input type="hidden" name="purpose" value="wallet_topup">
                @if(request('plan_id'))
                    <input type="hidden" name="plan_id" value="{{ request('plan_id') }}">
                @endif
                @if(request('duration_id'))
                    <input type="hidden" name="duration_id" value="{{ request('duration_id') }}">
                @endif
                @if(request('auto_activate'))
                    <input type="hidden" name="auto_activate" value="1">
                @endif

                @include('payments.partials.amount-card')
                @include('payments.partials.mode-selector')
                @include('payments.partials.mode-pg')
                @include('payments.partials.mode-upi')
                @include('payments.partials.mode-bank')
                @include('payments.partials.actions')
            </form>
        </div>
    </div>

    <!-- Configuration Bridge & App Script -->
    <script>
        window.paymentCheckoutConfig = {
            mode: '{{ $settings['pg_enabled'] ? 'pg' : ($settings['manual_upi_enabled'] ? 'manual_upi' : 'bank_transfer') }}',
            purpose: 'wallet_topup',
            amount: {{ $presetAmount }},
            minAmount: {{ $settings['min_amount'] }},
            planId: '{{ request('plan_id') }}',
            durationId: '{{ request('duration_id') }}',
            autoActivate: '{{ request('auto_activate') }}',
            activePgDriver: '{{ $settings['active_pg_driver'] }}',
            businessUpiId: '{{ $settings['business_upi_id'] }}',
            businessUpiName: '{{ $settings['business_upi_name'] }}',
            couponValidationUrl: '{{ route('payments.validate-coupon', [], false) }}',
            paymentStoreUrl: '{{ route('payments.store', [], false) }}',
            paymentVerifyUrl: '{{ route('payments.verify', [], false) }}',
            paymentsIndexUrl: '{{ route('payments.index', [], false) }}',
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
    <script src="{{ asset('js/payments/payment-checkout-app.js') }}?v={{ file_exists(public_path('js/payments/payment-checkout-app.js')) ? filemtime(public_path('js/payments/payment-checkout-app.js')) : time() }}"></script>
</x-app-layout>
