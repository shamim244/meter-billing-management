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
