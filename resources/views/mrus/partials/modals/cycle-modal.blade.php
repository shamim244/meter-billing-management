<!-- MODAL: Global New Billing Cycle -->
<div x-show="showCycleModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div @click.outside="if(!cycleInProgress) showCycleModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        @include('mrus.partials.modals.cycle.header')

        <div class="overflow-y-auto p-4 sm:p-6 space-y-4">
            @include('mrus.partials.modals.cycle.mru-selector')
            @include('mrus.partials.modals.cycle.date-controls')
            @include('mrus.partials.modals.cycle.status-feedback')
        </div>

        @include('mrus.partials.modals.cycle.footer')
    </div>
</div>
