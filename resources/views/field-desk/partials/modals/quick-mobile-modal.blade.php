        <!-- 6. QUICK MOBILE UPDATE MODAL -->
        <div x-show="modals.quickMobile"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="modals.quickMobile = false"
                 class="bg-white dark:bg-slate-900 rounded-3xl max-w-sm w-full p-5 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span>📱</span> Update Consumer Mobile
                    </h3>
                    <button @click="modals.quickMobile = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                </div>
                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-slate-500 dark:text-slate-400">Updating CA:</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white ml-1" x-text="quickMobileForm.ca_number"></span>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">10-Digit Mobile Number</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs font-mono">📱 +91</span>
                            <input type="tel"
                                   x-model="quickMobileForm.mobile"
                                   maxlength="10"
                                   placeholder="9876543210"
                                   class="w-full pl-14 pr-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="modals.quickMobile = false" class="px-3 py-1.5 rounded-xl text-slate-500 font-bold">
                            Cancel
                        </button>
                        <button type="button" @click="submitQuickMobile()" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold">
                            💾 Save Mobile
                        </button>
                    </div>
                </div>
            </div>
        </div>