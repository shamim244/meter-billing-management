<!-- 1. Top-Up Amount Card -->
<div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-5">
    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
        <span>1️⃣</span> Top-Up Amount
    </h2>

    <!-- Amount Input & Quick Presets -->
    <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">
            Enter Amount (Minimum ₹<span x-text="minAmount"></span>)
        </label>
        <div class="relative max-w-sm">
            <span class="absolute left-4 top-3 text-slate-400 font-bold text-base">₹</span>
            <input type="number" step="1" :min="minAmount" name="amount" x-model.number="amount" required class="w-full text-base font-black font-mono pl-9 pr-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <!-- Quick Presets -->
        <div class="flex flex-wrap items-center gap-2 mt-3">
            <span class="text-[11px] text-slate-400 font-medium">Quick Select:</span>
            <button type="button" @click="setAmount(500)" class="px-3 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 transition">₹500</button>
            <button type="button" @click="setAmount(1000)" class="px-3 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 transition">₹1,000</button>
            <button type="button" @click="setAmount(2500)" class="px-3 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 transition">₹2,500</button>
            <button type="button" @click="setAmount(5000)" class="px-3 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 transition">₹5,000</button>
        </div>
    </div>

    <!-- Coupon Code Card (Recharge Bonus Promo) -->
    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2.5">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                <span>🎟️</span>
                <span>Have a Top-Up Bonus Promo Code?</span>
            </span>
            <template x-if="appliedCoupon">
                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                    Bonus Activated
                </span>
            </template>
        </div>

        <template x-if="!appliedCoupon">
            <div class="space-y-1.5">
                <div class="flex gap-2 max-w-sm">
                    <input type="text" x-model="couponCodeInput" @keydown.enter.prevent="validateCoupon()" placeholder="e.g. EXTRAWALLET" class="flex-1 text-xs font-mono font-bold uppercase bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-indigo-500">
                    <button type="button" @click="validateCoupon()" :disabled="isValidatingCoupon || !couponCodeInput.trim()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer">
                        <span x-show="!isValidatingCoupon">Apply</span>
                        <span x-show="isValidatingCoupon" x-cloak>...</span>
                    </button>
                </div>
                <p x-show="couponError" x-text="couponError" class="text-[11px] text-rose-500 font-semibold"></p>
            </div>
        </template>

        <template x-if="appliedCoupon">
            <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/80 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/80">
                <div>
                    <div class="font-mono font-bold text-xs text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                        <span>🎁</span>
                        <span x-text="appliedCoupon.code"></span>
                        <span>(+₹<span x-text="appliedCoupon.discount_or_bonus_amount.toLocaleString('en-IN')"></span> Bonus Credit)</span>
                    </div>
                    <div class="text-[10px] text-emerald-700 dark:text-emerald-400 mt-0.5" x-text="appliedCoupon.message"></div>
                </div>
                <button type="button" @click="removeCoupon()" class="text-xs text-rose-500 hover:text-rose-600 font-bold px-2 py-1 hover:bg-rose-50 dark:hover:bg-rose-950/50 rounded-lg transition">
                    Remove
                </button>
            </div>
        </template>
    </div>
</div>

<input type="hidden" name="coupon_code" :value="appliedCoupon ? appliedCoupon.code : ''">
