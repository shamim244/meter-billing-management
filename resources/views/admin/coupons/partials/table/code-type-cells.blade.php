<!-- Checkbox -->
<td class="py-3.5 px-4 text-center">
    <input type="checkbox" value="{{ $coupon->id }}" x-model="selectedCoupons" class="coupon-checkbox rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
</td>

<!-- Code -->
<td class="py-3.5 px-4">
    <a href="{{ route('admin.coupons.show', $coupon) }}" class="font-mono font-black text-sm text-indigo-300 hover:text-indigo-200 tracking-wider flex items-center gap-1.5 transition">
        <span>🎟️</span>
        <span>{{ $coupon->code }}</span>
    </a>
    <div class="text-[10px] text-slate-500 mt-0.5">
        Per user: <strong class="text-slate-400">{{ $coupon->usage_limit_per_user }}x</strong>
        @if($coupon->minimum_amount)
            • Min: ₹{{ number_format($coupon->minimum_amount, 0) }}
        @endif
    </div>
</td>

<!-- Type -->
<td class="py-3.5 px-4">
    @if($coupon->type === 'subscription_discount')
        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-purple-950 text-purple-300 border border-purple-500/30">
            📋 Subscription Discount
        </span>
        @if($coupon->restrictedPlan)
            <div class="text-[10px] text-indigo-400 font-bold mt-1">
                Locked: {{ $coupon->restrictedPlan->name }}
            </div>
        @else
            <div class="text-[10px] text-slate-500 mt-1">All Plans</div>
        @endif
    @else
        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-cyan-950 text-cyan-300 border border-cyan-500/30">
            👛 Top-Up Bonus
        </span>
        <div class="text-[10px] text-cyan-400 font-mono mt-1">
            {{ $coupon->slabs->count() }} Tiered Slab(s)
        </div>
    @endif
</td>
