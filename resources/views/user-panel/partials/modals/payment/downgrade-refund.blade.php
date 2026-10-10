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
