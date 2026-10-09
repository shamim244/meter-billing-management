<x-admin-layout>
    <x-slot name="header">
        Agent Wallet Console: {{ $user->name }}
    </x-slot>

    <div x-data="adminWalletShowApp()" class="space-y-6">
        @include('admin.wallets.partials.show.header')
        @include('admin.wallets.partials.show.alerts')
        @include('admin.wallets.partials.show.balance-card')
        @include('admin.wallets.partials.show.referral-override')
        @include('admin.wallets.partials.show.modal-adjust')
        @include('admin.wallets.partials.show.modal-freeze')
        @include('admin.wallets.partials.show.adjustments-history')
        @include('admin.wallets.partials.show.ledger-filters')
        @include('admin.wallets.partials.show.ledger-table')
    </div>

    <!-- App Script -->
    <script src="{{ asset('js/admin/wallets/wallet-show-app.js') }}?v={{ file_exists(public_path('js/admin/wallets/wallet-show-app.js')) ? filemtime(public_path('js/admin/wallets/wallet-show-app.js')) : time() }}"></script>
</x-admin-layout>
