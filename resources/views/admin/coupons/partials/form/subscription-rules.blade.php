<div x-show="type === 'subscription_discount'" class="space-y-4 bg-slate-900/50 p-5 rounded-2xl border border-slate-800">
    <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-400">Subscription Discount Rules</h3>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">Discount Kind</label>
            <select name="discount_kind" x-model="discountKind" :disabled="type !== 'subscription_discount'" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl py-2 px-3 text-white">
                <option value="percentage">Percentage (% OFF)</option>
                <option value="flat">Flat Amount (₹ FLAT OFF)</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">
                <span x-text="discountKind === 'percentage' ? 'Discount Percentage (%)' : 'Flat Discount Amount (₹)'"></span>
                <span class="text-rose-400">*</span>
            </label>
            <input type="number" step="0.01" min="0.01" name="discount_value" value="{{ old('discount_value', $coupon->discount_value ?? 20) }}" :disabled="type !== 'subscription_discount'" class="w-full text-xs font-mono font-bold bg-slate-950 border-slate-800 rounded-xl py-2 px-3 text-white">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">Plan Restriction (Optional)</label>
            <select name="plan_restriction_id" :disabled="type !== 'subscription_discount'" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl py-2 px-3 text-white">
                <option value="">All Subscription Plans</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" {{ (old('plan_restriction_id', $coupon->plan_restriction_id ?? '') == $plan->id) ? 'selected' : '' }}>{{ $plan->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">Minimum Purchase (₹)</label>
            <input type="number" step="0.01" name="minimum_amount" value="{{ old('minimum_amount', $coupon->minimum_amount ?? '') }}" :disabled="type !== 'subscription_discount'" placeholder="Optional (e.g. 299.00)" class="w-full text-xs font-mono bg-slate-950 border-slate-800 rounded-xl py-2 px-3 text-white">
        </div>
    </div>
</div>
