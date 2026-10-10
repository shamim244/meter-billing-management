@if($coupon->type === 'topup_bonus' && $coupon->slabs->count() > 0)
    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-3">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
            <span>👛</span> Configured Recharge Bonus Slabs
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @foreach($coupon->slabs as $slab)
                <div class="bg-slate-900/80 p-4 rounded-2xl border border-slate-800 text-center">
                    <div class="text-[10px] uppercase font-bold text-slate-400">{{ $slab->formatted_range }}</div>
                    <div class="text-xl font-black text-emerald-400 font-mono mt-1">+{{ $slab->bonus_percent }}% BONUS</div>
                </div>
            @endforeach
        </div>
    </div>
@endif
