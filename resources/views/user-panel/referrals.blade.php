<x-user-panel-layout>
    <x-slot name="header">
        Refer & Earn Program
    </x-slot>

    <div class="space-y-8" x-data="userReferralsApp()">
        @include('user-panel.referrals.partials.alerts')
        @include('user-panel.referrals.partials.hero-card')
        @include('user-panel.referrals.partials.stats-grid')
        @include('user-panel.referrals.partials.payouts-table')
        @include('user-panel.referrals.partials.regenerate-modal')
    </div>

    <!-- Decoupled Alpine Component -->
    <script src="{{ asset('js/user-panel/referrals-app.js') }}?v={{ file_exists(public_path('js/user-panel/referrals-app.js')) ? filemtime(public_path('js/user-panel/referrals-app.js')) : time() }}"></script>
</x-user-panel-layout>
