<!-- MODAL: Create New MRU Workspace -->
<div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div @click.outside="if(!isSubmittingMru) showCreateModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-base">
                    🏘️
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Create MRU Workspace</h3>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">Define permanent meter reading zone</p>
                </div>
            </div>
            <button @click="showCreateModal = false" :disabled="isSubmittingMru" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 disabled:opacity-40 p-1">✕</button>
        </div>

        <form @submit.prevent="submitCreateMru(false)" class="overflow-y-auto p-4 sm:p-6 space-y-4">
            <div x-show="createMruError && !mruOverageRequired" class="p-3 bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-semibold rounded-xl" x-text="createMruError"></div>

            <!-- Overage & Insufficient Balance Confirmation Alert -->
            <div x-show="mruOverageRequired" class="p-4 rounded-2xl space-y-3 transition border" :class="mruOverageInsufficient ? 'bg-rose-50/90 dark:bg-rose-950/50 border-rose-200 dark:border-rose-800/80' : 'bg-amber-50 dark:bg-amber-950/60 border-amber-300 dark:border-amber-700/80'">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 font-bold text-xs" :class="mruOverageInsufficient ? 'text-rose-800 dark:text-rose-300' : 'text-amber-800 dark:text-amber-300'">
                        <span x-text="mruOverageInsufficient ? '⛔' : '⚠️'"></span>
                        <span x-text="mruOverageInsufficient ? 'Insufficient Wallet Balance' : 'Plan Quota Notice'"></span>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase" :class="mruOverageInsufficient ? 'bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300' : 'bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300'">
                        Overage ₹<span x-text="mruOverageAmount"></span>
                    </span>
                </div>

                <p class="text-xs leading-relaxed" :class="mruOverageInsufficient ? 'text-rose-900 dark:text-rose-200' : 'text-amber-900 dark:text-amber-200'" x-text="mruOverageMessage"></p>

                <!-- Balance comparison strip -->
                <div class="flex items-center justify-between text-xs py-2 px-3 rounded-xl border" :class="mruOverageInsufficient ? 'bg-white/80 dark:bg-slate-900/80 border-rose-200/80 dark:border-rose-900/60' : 'bg-amber-100/60 dark:bg-amber-900/40 border-amber-200 dark:border-amber-800/60'">
                    <span class="text-slate-600 dark:text-slate-400">Creation Fee: <strong class="font-mono text-slate-900 dark:text-white">₹<span x-text="mruOverageAmount"></span></strong></span>
                    <span>Wallet Balance: <strong class="font-mono font-bold" :class="mruOverageInsufficient ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'">₹<span x-text="Number(mruOverageWalletBalance).toFixed(2)"></span></strong></span>
                </div>

                <!-- Case A: Balance is Sufficient -> Confirm & Pay Button -->
                <template x-if="!mruOverageInsufficient">
                    <div class="space-y-2 pt-1">
                        <button type="button" @click="submitCreateMru(true)" :disabled="isSubmittingMru" class="w-full py-2.5 px-4 bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow transition flex items-center justify-center gap-1.5">
                            <svg x-show="isSubmittingMru" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>✓ Confirm & Pay ₹<span x-text="mruOverageAmount"></span> from Wallet</span>
                        </button>
                        <div class="text-center">
                            <a :href="mruUpgradeUrl || '{{ route('user-panel.subscription') }}'" class="text-[11px] text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-cyan-400 underline transition">
                                Or upgrade your plan to increase included MRUs →
                            </a>
                        </div>
                    </div>
                </template>

                <!-- Case B: Balance is Insufficient -> Direct Links to Top Up & Upgrade -->
                <template x-if="mruOverageInsufficient">
                    <div class="space-y-2 pt-1">
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a :href="mruTopupUrl || '{{ route('wallet.index') }}'" target="_blank" class="flex-1 py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold text-center shadow-sm transition flex items-center justify-center gap-1.5">
                                <span>💳</span> Add Funds / Top Up Wallet
                            </a>
                            <a :href="mruUpgradeUrl || '{{ route('user-panel.subscription') }}'" class="flex-1 py-2.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold text-center shadow-sm transition flex items-center justify-center gap-1.5">
                                <span>⚡</span> Upgrade Plan
                            </a>
                        </div>
                        <button type="button" @click="submitCreateMru(true)" :disabled="isSubmittingMru" class="w-full py-2 px-3 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold transition flex items-center justify-center gap-1.5">
                            <svg x-show="isSubmittingMru" class="animate-spin h-3.5 w-3.5 text-slate-600 dark:text-slate-300" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>🔄 I've Added Funds — Retry Creation</span>
                        </button>
                    </div>
                </template>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">MRU Code *</label>
                <input type="text" x-model="newMruCode" placeholder="e.g. 0477, 0473, LAHGARIYA_LALPUR" required class="w-full text-xs font-mono uppercase rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                <span class="text-[10px] text-slate-400 mt-1 block">Used as physical directory folder for PDFs.</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Village / Area Name *</label>
                <input type="text" x-model="newMruName" placeholder="e.g. Gerua, Hala, Lalpur" required class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Full Identifier / Sub-Division (Optional)</label>
                <input type="text" x-model="newMruIdentifier" placeholder="e.g. Sub-division 04 / Gerua North" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
            </div>

            <div class="pt-4 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-2.5 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="showCreateModal = false" :disabled="isSubmittingMru" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-center">
                    Cancel
                </button>
                <button x-show="!mruOverageRequired" type="submit" :disabled="isSubmittingMru" class="w-full sm:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-1.5">
                    <svg x-show="isSubmittingMru" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="isSubmittingMru ? 'Verifying & Creating...' : 'Create Workspace'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
