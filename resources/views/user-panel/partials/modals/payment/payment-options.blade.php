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
