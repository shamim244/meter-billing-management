<!-- Discount / Slabs Value -->
<td class="py-3.5 px-4">
    @if($coupon->type === 'subscription_discount')
        <div class="font-mono font-bold text-emerald-400 text-sm">
            @if($coupon->discount_kind === 'percentage')
                {{ rtrim(rtrim(number_format((float)$coupon->discount_value, 2), '0'), '.') }}% OFF
            @else
                ₹{{ number_format((float)$coupon->discount_value, 2) }} FLAT OFF
            @endif
        </div>
        <div class="text-[10px] text-slate-400">Stacks on duration discounts</div>
    @else
        <div class="space-y-0.5 text-[11px] font-mono">
            @foreach($coupon->slabs->take(2) as $slab)
                <div class="text-slate-300">
                    {{ $slab->formatted_range }}: <strong class="text-cyan-400">+{{ $slab->bonus_percent }}%</strong>
                </div>
            @endforeach
            @if($coupon->slabs->count() > 2)
                <div class="text-[10px] text-slate-500">+{{ $coupon->slabs->count() - 2 }} more slabs</div>
            @endif
        </div>
    @endif
</td>

<!-- Redemptions -->
<td class="py-3.5 px-4 text-center">
    <div class="font-mono font-bold text-white text-sm">
        {{ $coupon->times_used_total }}
        <span class="text-xs font-medium text-slate-500">/ {{ $coupon->usage_limit_total ?? '∞' }}</span>
    </div>
    <div class="text-[10px] text-slate-400">
        ₹{{ number_format($coupon->redemptions()->sum('discount_or_bonus_amount'), 2) }} given
    </div>
</td>

<!-- Validity Window -->
<td class="py-3.5 px-4 text-center text-xs">
    @if($coupon->starts_at && $coupon->starts_at->isFuture())
        <span class="text-amber-400 font-bold text-[10px]">
            Starts {{ $coupon->starts_at->format('M d, Y') }}
        </span>
    @elseif($coupon->expires_at && $coupon->expires_at->isPast())
        <span class="text-rose-400 font-bold text-[10px]">
            Expired on {{ $coupon->expires_at->format('M d, Y') }}
        </span>
    @elseif($coupon->expires_at)
        <span class="text-slate-300 text-[11px]">
            Until {{ $coupon->expires_at->format('M d, Y') }}
        </span>
    @else
        <span class="text-emerald-400 font-bold text-[10px]">Lifetime / No Expiry</span>
    @endif
</td>
