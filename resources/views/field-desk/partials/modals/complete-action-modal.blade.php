        <!-- 2. COMPLETE / RESOLVE MODAL -->
        <div x-show="modals.complete"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="modals.complete = false"
                 class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>✅</span> Complete FieldDesk Action
                    </h3>
                    <button @click="modals.complete = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                </div>

                <form @submit.prevent="submitComplete()" class="space-y-3.5 text-xs">
                    <div>
                        <span class="text-slate-500 dark:text-slate-400">Resolving action for CA:</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white ml-1" x-text="activeAction?.ca_number"></span>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Amount Collected (₹)</label>
                        <input type="number"
                               step="0.01"
                               x-model="completeForm.collected_amount"
                               placeholder="0.00"
                               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Resolution Remark</label>
                        <input type="text"
                               x-model="completeForm.note"
                               placeholder="e.g. Paid in full via PhonePe / Meter inspected OK"
                               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="modals.complete = false" class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-md shadow-emerald-500/20">
                            Confirm Resolution
                        </button>
                    </div>
                </form>

            </div>
        </div>