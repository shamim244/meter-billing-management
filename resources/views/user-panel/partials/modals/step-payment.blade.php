{{-- Step 3: Payment, Confirmation & Coupon Checkout --}}
<div x-show="currentStep === 3" class="space-y-4">
    <!-- Downgrade Refund Theme -->
    <template x-if="quote.action_type === 'downgrade'">
        <div class="space-y-4">
            <div class="p-5 rounded-3xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-800 text-center space-y-2">
                <span class="text-3xl">💰</span>
                <h4 class="text-sm font-black text-emerald-900 dark:text-emerald-100">Wallet Credit Refund</h4>
                <div class="text-3xl font-black font-mono text-emerald-700 dark:text-emerald-300">
                    +₹<span x-text="quote.prorated_credit.toLocaleString('en-IN')"></span>
                </div>
                <p class="text-xs text-emerald-800 dark:text-emerald-300 max-w-xs mx-auto">
                    You will NOT be charged. The unused balance of your previous plan will be deposited into your wallet balance instantly.
                </p>
            </div>

            <div x-show="walletError" x-cloak class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 text-rose-800 text-xs font-bold">
                <span>❌ </span><span x-text="walletError"></span>
            </div>

            <button type="button" @click="confirmWalletPayment()" :disabled="isProcessingWallet || mruConflict" class="w-full py-3.5 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-sm shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer">
                <span x-show="!isProcessingWallet">✓ Confirm & Receive +₹<span x-text="quote.prorated_credit.toLocaleString('en-IN')"></span></span>
                <span x-show="isProcessingWallet" x-cloak>Applying...</span>
            </button>
        </div>
    </template>

    <!-- Normal Payment Theme (Upgrade, Renewal, or New) -->
    <template x-if="quote.action_type !== 'downgrade'">
        <div class="space-y-3">
            <div class="p-4 rounded-2xl bg-indigo-50/90 dark:bg-indigo-950/70 border border-indigo-200 dark:border-indigo-800 flex items-center justify-between">
                <div>
                    <span class="text-[10px] uppercase tracking-wider font-bold text-indigo-700 dark:text-indigo-300 block">Total Payable</span>
                    <span class="text-2xl font-black font-mono text-indigo-950 dark:text-white">
                        ₹<span x-text="quote.final_amount.toLocaleString('en-IN')"></span>
                    </span>
                    <template x-if="quote.coupon && quote.coupon.valid">
                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold block">
                            Coupon discount applied: -₹<span x-text="quote.coupon.discount_amount.toLocaleString('en-IN')"></span>
                        </span>
                    </template>
                </div>
                <div class="text-right text-xs">
                    <span class="text-indigo-800 dark:text-indigo-300 block font-medium">Your Wallet Balance</span>
                    <strong class="font-mono text-slate-900 dark:text-white text-sm font-bold">₹<span x-text="walletBalance.toLocaleString('en-IN')"></span></strong>
                </div>
            </div>

            <!-- Coupon Code Box -->
            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                        <span>🎟️</span>
                        <span>Have a coupon code?</span>
                    </span>
                    <template x-if="appliedCoupon">
                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                            Applied
                        </span>
                    </template>
                </div>

                <template x-if="!appliedCoupon">
                    <div class="space-y-1.5">
                        <div class="flex gap-2">
                            <input type="text" x-model="couponCodeInput" @keydown.enter.prevent="applyCoupon()" placeholder="e.g. WELCOME20" class="flex-1 text-xs font-mono font-bold uppercase bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-indigo-500">
                            <button type="button" @click="applyCoupon()" :disabled="isValidatingCoupon || !couponCodeInput.trim()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer">
                                <span x-show="!isValidatingCoupon">Apply</span>
                                <span x-show="isValidatingCoupon" x-cloak>...</span>
                            </button>
                        </div>
                        <p x-show="couponError" x-text="couponError" class="text-[11px] text-rose-500 font-semibold"></p>
                    </div>
                </template>

                <template x-if="appliedCoupon">
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-emerald-50/80 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/80">
                        <div>
                            <div class="font-mono font-bold text-xs text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                                <span>✓</span>
                                <span x-text="appliedCoupon.code"></span>
                                <span>(-₹<span x-text="appliedCoupon.discount_amount.toLocaleString('en-IN')"></span>)</span>
                            </div>
                            <div class="text-[10px] text-emerald-700 dark:text-emerald-400 mt-0.5" x-text="appliedCoupon.message"></div>
                        </div>
                        <button type="button" @click="removeCoupon()" class="text-xs text-rose-500 hover:text-rose-600 font-bold px-2 py-1 hover:bg-rose-50 dark:hover:bg-rose-950/50 rounded-lg transition">
                            Remove
                        </button>
                    </div>
                </template>
            </div>

            <div x-show="walletError" x-cloak class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 text-rose-800 text-xs font-bold">
                <span>❌ </span><span x-text="walletError"></span>
            </div>

            <!-- 1-Click Free Activation (Zero Cost) -->
            <template x-if="quote.final_amount <= 0">
                <div class="p-4 rounded-2xl border-2 border-emerald-500/50 bg-emerald-50/50 dark:bg-emerald-950/30 space-y-3">
                    <div class="flex items-center gap-2.5">
                        <span class="text-2xl">🎉</span>
                        <div>
                            <h4 class="text-xs font-bold text-emerald-950 dark:text-emerald-200">100% Free Plan — No Payment Needed</h4>
                            <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-0.5">Instant 1-click activation without deducting any wallet funds.</p>
                        </div>
                    </div>
                    <button type="button" @click="confirmWalletPayment()" :disabled="isProcessingWallet || mruConflict" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer">
                        <span x-show="!isProcessingWallet" x-text="hasActiveSubscription ? '⚡ Renew Free Plan (₹0)' : '⚡ Activate Free Plan Now (₹0)'"></span>
                        <span x-show="isProcessingWallet" x-cloak>Activating Free Plan...</span>
                    </button>
                </div>
            </template>

            <!-- Paid Options (Wallet & Gateway) -->
            <template x-if="quote.final_amount > 0">
                <div class="space-y-3">
                    <!-- Pay from Wallet -->
                    <div class="p-4 rounded-2xl border-2 transition" :class="walletBalance >= quote.final_amount ? 'border-emerald-500/40 bg-emerald-50/30 dark:bg-emerald-950/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950'">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">👛</span>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white">Pay from Wallet Balance</h4>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Instant activation with no payment redirect.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <template x-if="walletBalance >= quote.final_amount">
                                <button type="button" @click="confirmWalletPayment()" :disabled="isProcessingWallet || mruConflict" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer">
                                    <span x-show="!isProcessingWallet">✓ Pay ₹<span x-text="quote.final_amount.toLocaleString('en-IN')"></span> from Wallet Balance</span>
                                    <span x-show="isProcessingWallet" x-cloak>Processing Payment...</span>
                                </button>
                            </template>

                            <template x-if="walletBalance < quote.final_amount">
                                <div class="space-y-2">
                                    <p class="text-[11px] text-rose-500 font-semibold">
                                        Insufficient balance (Deficit: ₹<span x-text="(quote.final_amount - walletBalance).toLocaleString('en-IN')"></span>).
                                    </p>
                                    <a href="{{ route('payments.create') }}" class="w-full py-2 px-3 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 font-bold text-xs flex items-center justify-center gap-1 hover:bg-indigo-100 transition">
                                        <span>👛 Top-Up Wallet First →</span>
                                    </a>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Pay Directly -->
                    <div class="p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">💳</span>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white">Pay Directly</h4>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400">Gateway / UPI QR / Bank Transfer</p>
                            </div>
                        </div>
                        <a :href="directPurchaseUrl" class="py-2 px-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900 font-bold text-xs shadow-xs transition">
                            <span>Pay ₹<span x-text="quote.final_amount.toLocaleString('en-IN')"></span> →</span>
                        </a>
                    </div>
                </div>
            </template>
        </div>
    </template>

    <!-- Back to Step 2 Button -->
    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-start">
        <button type="button" @click="goToStep(2)" class="py-2 px-4 rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs transition cursor-pointer">
            <span x-text="isSamePlan ? '⬅ Back to Duration' : '⬅ Back to Comparison'"></span>
        </button>
    </div>
</div>
