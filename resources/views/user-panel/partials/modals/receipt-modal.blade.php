{{-- Modal: Post-Action Confirmation & Receipt --}}
<div x-show="showReceiptModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="receipt-modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="showReceiptModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div x-show="showReceiptModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 dark:border-slate-800 p-6 sm:p-8 space-y-5 text-center">

            <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-3xl mx-auto shadow-inner">
                ✓
            </div>

            <div class="space-y-1">
                <h3 class="text-lg font-black text-slate-900 dark:text-white">
                    <span x-text="receiptData?.actionType === 'downgrade' ? 'Plan Downgrade Applied!' : 'Plan Activated Successfully!'"></span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400" x-text="receiptData?.message"></p>
            </div>

            <!-- Receipt Details Box -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 text-xs space-y-2 text-left">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 dark:text-slate-400">Activated Plan:</span>
                    <strong class="text-slate-900 dark:text-white" x-text="receiptData?.planName"></strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 dark:text-slate-400">Duration:</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="receiptData?.duration"></span>
                </div>
                <template x-if="receiptData?.actionType === 'downgrade'">
                    <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-700">
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">Prorated Refund Credited:</span>
                        <span class="font-mono font-black text-emerald-600 dark:text-emerald-400" x-text="'+₹' + receiptData?.amountCredited?.toLocaleString('en-IN')"></span>
                    </div>
                </template>
                <template x-if="receiptData?.actionType !== 'downgrade'">
                    <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-700">
                        <span class="text-slate-700 dark:text-slate-300 font-bold">Amount Paid:</span>
                        <span class="font-mono font-black text-slate-900 dark:text-white" x-text="'₹' + receiptData?.amountPaid?.toLocaleString('en-IN')"></span>
                    </div>
                </template>
            </div>

            <div class="pt-2">
                <button type="button" @click="window.location.reload()" class="w-full py-3 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900 font-bold text-xs shadow-md transition">
                    ✓ Back to Subscriptions
                </button>
            </div>
        </div>
    </div>
</div>
