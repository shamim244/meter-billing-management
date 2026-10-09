<x-user-panel-layout>
    <x-slot name="header">
        Subscription & Storage Allocation
    </x-slot>

    <div class="space-y-8" x-data="subscriptionApp()">
        <!-- 1. Session Flash Alerts -->
        @include('user-panel.partials.subscription-alerts')

        <!-- 2. Storage & Quota Status Hero -->
        @include('user-panel.partials.subscription-hero')

        <!-- 3. Available Subscription Plans Grid -->
        @include('user-panel.partials.subscription-plans-grid')

        <!-- 4. Subscription & Plan Transition History Table -->
        @include('user-panel.partials.subscription-history-table')

        <!-- 5. Modal: Checkout & Plan Transition Wizard -->
        @include('user-panel.partials.modals.checkout-modal')

        <!-- 6. Modal: Post-Action Confirmation & Receipt -->
        @include('user-panel.partials.modals.receipt-modal')
    </div>

    <!-- Server Configuration Bridge -->
    <script>
        window.subscriptionConfig = {
            hasActiveSubscription: {{ $activeSubscription ? 'true' : 'false' }},
            currentPlanId: {{ $activeSubscription ? $activeSubscription->plan_id : 'null' }},
            walletBalance: {{ (float) $walletBalance }},
            subscribeWalletUrl: '{{ route('subscription.subscribe_wallet', [], false) }}',
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
    <script src="{{ asset('js/user-panel/subscription-app.js') }}?v={{ filemtime(public_path('js/user-panel/subscription-app.js')) }}"></script>
</x-user-panel-layout>
