<!-- MODAL: Start Billing Cycle for this MRU -->
<div x-show="showStartBillingModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div @click.outside="if(!billingInProgress) showStartBillingModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        @include('mrus.show-partials.modals.start-billing.header')

        <div class="overflow-y-auto p-4 sm:p-6 space-y-4">
            @include('mrus.show-partials.modals.start-billing.mru-info')
            @include('mrus.show-partials.modals.start-billing.date-controls')
            @include('mrus.show-partials.modals.start-billing.status-feedback')
        </div>

        @include('mrus.show-partials.modals.start-billing.footer')
    </div>
</div>
