<x-app-layout>
    <div class="py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @include('bills.partials.history.header', ['account' => $account])
            @include('bills.partials.history.consumer-overview', ['account' => $account, 'bills' => $bills])
            @include('bills.partials.history.matrix-table', ['meterMatrix' => $meterMatrix])
            @include('bills.partials.history.bills-table', ['bills' => $bills, 'statuses' => $statuses, 'account' => $account])
        </div>
    </div>
</x-app-layout>
