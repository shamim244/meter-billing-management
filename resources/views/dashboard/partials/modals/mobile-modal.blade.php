            <!-- MODAL 6: Consumer Mobile Number (Individual Quick Add / Override) -->
            <div x-show="showMobileModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="showMobileModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-base">
                                📱
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Consumer Mobile Number</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500 font-mono" x-text="'CA: ' + (editingMobileBill?.ca_number || '—') + (editingMobileBill?.consumer_name ? ' • ' + editingMobileBill.consumer_name : '')"></p>
                            </div>
                        </div>
                        <button type="button" @click="showMobileModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
                    </div>

                    <div class="overflow-y-auto p-4 sm:p-6 space-y-4">
                        <div>
                            <label for="consumerMobileInput" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Mobile Number (10 Digits)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 font-mono text-xs">+91</span>
                                <input type="tel" id="consumerMobileInput" x-model="mobileInput" placeholder="9876543210" maxlength="15" @keyup.enter="saveConsumerMobile()" class="w-full text-sm font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white pl-11 pr-3.5 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition">
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1.5">Standard 10-digit mobile number. Enter to save, or clear to remove.</p>
                        </div>

                        <!-- Current Saved Status -->
                        <template x-if="editingMobileBill?.mobile">
                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-400 text-[11px]">Currently saved:</span>
                                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="editingMobileBill.mobile"></span>
                                </div>
                                <button type="button" @click="copyMobile(editingMobileBill)" class="text-[11px] text-blue-600 dark:text-cyan-400 hover:underline font-medium cursor-pointer">
                                    Copy Number
                                </button>
                            </div>
                        </template>

                        <!-- Error Message -->
                        <div x-show="mobileModalError" class="p-3 rounded-xl text-xs font-semibold bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800" x-text="mobileModalError"></div>
                    </div>

                    <div class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2.5">
                        <div>
                            <template x-if="editingMobileBill?.mobile">
                                <button type="button" @click="saveConsumerMobile(true)" :disabled="savingMobile" class="w-full sm:w-auto px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition text-center cursor-pointer">
                                    Remove Number
                                </button>
                            </template>
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                            <button type="button" @click="showMobileModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition text-center">
                                Cancel
                            </button>
                            <button type="button" @click="saveConsumerMobile()" :disabled="savingMobile" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-1.5 cursor-pointer">
                                <span x-show="!savingMobile">💾 Save Mobile</span>
                                <span x-show="savingMobile" class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
