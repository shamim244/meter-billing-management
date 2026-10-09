<!-- TABLE VIEW -->
<div x-show="items.length > 0 && viewMode === 'table'"
     :class="loading ? 'opacity-40 pointer-events-none transition-opacity duration-150' : 'opacity-100 transition-opacity duration-150'"
     class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
            @include('dashboard.partials.table.table-head')
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                <template x-for="(bill, index) in items" :key="bill.id">
                    <tr :id="'row-' + bill.ca_number" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition duration-150" :class="{
                        'ring-2 ring-rose-500 bg-rose-50/50 dark:bg-rose-950/40': bill._syncError,
                        'bg-emerald-50/30 dark:bg-emerald-950/25': bill.review_status === 'submitted' && !bill._syncError,
                        'bg-rose-50/30 dark:bg-rose-950/25': bill.review_status === 'critical' && !bill._syncError,
                        'bg-amber-50/30 dark:bg-amber-950/25': bill.review_status === 'doubt' && !bill._syncError
                    }">
                        @include('dashboard.partials.table.cell-consumer')
                        @include('dashboard.partials.table.cell-readings')
                        @include('dashboard.partials.table.cell-actions')
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    @include('dashboard.partials.table.table-pagination')
</div>
