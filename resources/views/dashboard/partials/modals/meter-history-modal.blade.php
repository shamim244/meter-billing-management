<!-- 📊 2D METER READING HISTORY MODAL -->
<div x-show="showMeterHistoryModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-4xl w-full shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150" @click.away="showMeterHistoryModal = false">
        @include('dashboard.partials.modals.meter-history.header')

        <!-- Modal Body -->
        <div class="p-5 sm:p-6 space-y-4">
            <!-- Loading Indicator -->
            <div x-show="meterHistoryLoading" class="py-12 flex flex-col items-center justify-center gap-3 text-slate-400">
                <div class="w-8 h-8 border-3 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                <span class="text-xs font-semibold">Loading 2D reading history...</span>
            </div>

            <div x-show="!meterHistoryLoading && meterHistoryData">
                @include('dashboard.partials.modals.meter-history.summary-bar')
                @include('dashboard.partials.modals.meter-history.table')
            </div>
        </div>

        @include('dashboard.partials.modals.meter-history.footer')
    </div>
</div>
