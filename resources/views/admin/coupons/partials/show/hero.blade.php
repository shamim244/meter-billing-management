<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
    <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
        <div class="flex items-start gap-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white font-black text-2xl flex items-center justify-center shadow-lg shadow-indigo-600/30 shrink-0">
                🎟️
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-2xl font-black font-mono text-white tracking-widest">{{ $coupon->code }}</h1>

                    <!-- Status Pill -->
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $coupon->is_active ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' }}">
                        {{ $coupon->is_active ? 'Active Campaign' : 'Inactive' }}
                    </span>

                    <!-- Type Pill -->
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-950 text-indigo-300 border border-indigo-500/40">
                        {{ str_replace('_', ' ', $coupon->type) }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400 mt-2">
                    @if($coupon->type === 'subscription_discount')
                        <span class="text-emerald-400 font-bold font-mono">
                            {{ $coupon->discount_kind === 'percentage' ? ((float)$coupon->discount_value . '% OFF') : ('₹' . number_format((float)$coupon->discount_value, 2) . ' FLAT OFF') }}
                        </span>
                        <span>•</span>
                        <span>{{ $coupon->restrictedPlan ? ('Locked: ' . $coupon->restrictedPlan->name) : 'All Plans Eligible' }}</span>
                    @else
                        <span class="text-cyan-400 font-bold font-mono">
                            {{ $coupon->slabs->count() }} Tiered Recharge Slabs
                        </span>
                    @endif

                    <span>•</span>
                    <span>Limit: {{ $coupon->usage_limit_per_user }}x per user</span>

                    @if($coupon->expires_at)
                        <span>•</span>
                        <span>Expires: {{ $coupon->expires_at->format('M d, Y') }}</span>
                    @endif

                    @if($coupon->creator)
                        <span>•</span>
                        <span>Created by {{ $coupon->creator->name }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-slate-900/90 px-5 py-3 rounded-2xl border border-slate-800 text-center min-w-[120px]">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Campaign ID</div>
            <div class="text-base font-black text-white font-mono mt-0.5">#{{ $coupon->id }}</div>
        </div>
    </div>
</div>
