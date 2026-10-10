<x-admin-layout>
    <x-slot name="header">
        Agent Wallet Ledgers & System Balances
    </x-slot>

    <div class="space-y-6">
        @include('admin.wallets.partials.index.header')
        @include('admin.wallets.partials.index.kpi-cards')
        @include('admin.wallets.partials.index.filter-bar')
        @include('admin.wallets.partials.index.table')
    </div>
</x-admin-layout>
