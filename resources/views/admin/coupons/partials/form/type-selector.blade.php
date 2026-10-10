@if(isset($coupon))
    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
        <div>
            <span class="text-[10px] uppercase font-bold text-slate-400">Coupon Category</span>
            <div class="text-base font-black text-white capitalize mt-0.5">
                {{ str_replace('_', ' ', $coupon->type) }}
            </div>
        </div>

        <div class="text-right">
            <span class="text-[10px] uppercase font-bold text-slate-400">Times Redeemed</span>
            <div class="text-base font-black text-cyan-400 font-mono mt-0.5">
                {{ $coupon->times_used_total }}
            </div>
        </div>
    </div>
@else
    <div>
        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">1. Choose Coupon Category</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label @click="type = 'subscription_discount'" :class="type === 'subscription_discount' ? 'border-indigo-500 bg-indigo-500/10' : 'border-slate-800 bg-slate-900/60'" class="p-4 rounded-2xl border cursor-pointer flex items-start gap-3 transition">
                <input type="radio" name="type" value="subscription_discount" x-model="type" class="mt-1 text-indigo-600 focus:ring-indigo-500">
                <div>
                    <div class="font-bold text-white text-sm">📋 Subscription Discount</div>
                    <div class="text-xs text-slate-400 mt-0.5">Percentage or flat ₹ off plan purchases. Stacks on duration discounts.</div>
                </div>
            </label>

            <label @click="type = 'topup_bonus'" :class="type === 'topup_bonus' ? 'border-cyan-500 bg-cyan-500/10' : 'border-slate-800 bg-slate-900/60'" class="p-4 rounded-2xl border cursor-pointer flex items-start gap-3 transition">
                <input type="radio" name="type" value="topup_bonus" x-model="type" class="mt-1 text-cyan-600 focus:ring-cyan-500">
                <div>
                    <div class="font-bold text-white text-sm">👛 Top-Up Bonus (Tiered Slabs)</div>
                    <div class="text-xs text-slate-400 mt-0.5">Automatic % bonus credited into agent wallet based on recharge amount slabs.</div>
                </div>
            </label>
        </div>
    </div>
@endif
