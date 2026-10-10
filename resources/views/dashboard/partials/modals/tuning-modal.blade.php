<!-- ⚡ SMART AVERAGE TUNING & SEQUENTIAL COMPOUNDING MODAL -->
<div x-show="showTuningModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-xl w-full shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150" @click.away="showTuningModal = false">
        @include('dashboard.partials.modals.tuning.header')

        <!-- Modal Body -->
        <div class="p-5 sm:p-6 space-y-5">
            @include('dashboard.partials.modals.tuning.simulation-preview')
            @include('dashboard.partials.modals.tuning.step-controls')
        </div>

        @include('dashboard.partials.modals.tuning.footer')
    </div>
</div>
