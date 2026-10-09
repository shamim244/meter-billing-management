<x-app-layout>
    <!-- Razorpay Standard Checkout JS -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <!-- Cashfree Web JS SDK v3 -->
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>

    <div x-data="subscriptionPurchaseApp(window.subscriptionPurchaseConfig)" class="py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @include('subscription.purchase.partials.header')
            @include('subscription.purchase.partials.alerts')
            @include('subscription.purchase.partials.summary-card')

            <!-- Payment Method Form -->
            <form action="{{ route('subscription.purchase.process', ['plan' => $plan->id, 'duration' => $duration->id]) }}" method="POST" enctype="multipart/form-data" @submit="handleCheckout($event)" class="space-y-6">
                @csrf
                <input type="hidden" name="action_mode" value="{{ $pricingDetails['action_mode'] }}">

                <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6">
                    @include('subscription.purchase.partials.mode-selector')
                    @include('subscription.purchase.partials.mode-pg')
                    @include('subscription.purchase.partials.mode-upi')
                    @include('subscription.purchase.partials.mode-bank')
                    @include('subscription.purchase.partials.actions')
                </div>
            </form>
        </div>
    </div>

    <!-- Configuration Bridge & App Script -->
    <script>
        window.subscriptionPurchaseConfig = {
            mode: '{{ $settings['pg_enabled'] ? 'pg' : ($settings['manual_upi_enabled'] ? 'manual_upi' : 'bank_transfer') }}',
            amount: {{ $pricingDetails['final_amount'] }},
            activePgDriver: '{{ $settings['active_pg_driver'] }}',
            businessUpiId: '{{ $settings['business_upi_id'] }}',
            businessUpiName: '{{ $settings['business_upi_name'] }}',
            planId: {{ $plan->id }},
            planName: '{{ addslashes($plan->name) }}',
            durationMonths: {{ $duration->duration_months }},
            actionMode: '{{ $pricingDetails['action_mode'] }}',
            processUrl: '{{ route('subscription.purchase.process', ['plan' => $plan->id, 'duration' => $duration->id], false) }}',
            verifyUrl: '{{ route('payments.verify', [], false) }}',
            indexUrl: '{{ route('payments.index', [], false) }}',
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
    <script src="{{ asset('js/subscription/purchase-app.js') }}?v={{ file_exists(public_path('js/subscription/purchase-app.js')) ? filemtime(public_path('js/subscription/purchase-app.js')) : time() }}"></script>
</x-app-layout>
