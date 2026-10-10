<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- 1. Total Redemptions -->
    <div class="bg-slate-950 p-5 rounded-3xl border border-slate-800 shadow-lg">
        <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
            <span class="font-bold uppercase tracking-wider text-[10px]">Total Redemptions</span>
            <span>⚡</span>
        </div>
        <div class="text-2xl font-black font-mono text-cyan-400">
            {{ number_format($analytics['times_used']) }}
            <span class="text-xs font-sans font-normal text-slate-500">/ {{ $coupon->usage_limit_total ?? '∞' }}</span>
        </div>
        <div class="text-[11px] text-slate-500 mt-1">Platform-wide uses</div>
    </div>

    <!-- 2. Total Value Given Out -->
    <div class="bg-slate-950 p-5 rounded-3xl border border-slate-800 shadow-lg">
        <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
            <span class="font-bold uppercase tracking-wider text-[10px]">Discount / Bonus Dispatched</span>
            <span>🎁</span>
        </div>
        <div class="text-2xl font-black font-mono text-emerald-400">
            ₹{{ number_format($analytics['total_discount_given'], 2) }}
        </div>
        <div class="text-[11px] text-slate-500 mt-1">Direct savings passed to agents</div>
    </div>

    <!-- 3. Gross Revenue Processed -->
    <div class="bg-slate-950 p-5 rounded-3xl border border-slate-800 shadow-lg">
        <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
            <span class="font-bold uppercase tracking-wider text-[10px]">Original Transaction Volume</span>
            <span>💳</span>
        </div>
        <div class="text-2xl font-black font-mono text-indigo-300">
            ₹{{ number_format($analytics['total_original_revenue'], 2) }}
        </div>
        <div class="text-[11px] text-slate-500 mt-1">Net collected: ₹{{ number_format($analytics['total_final_revenue'], 2) }}</div>
    </div>

    <!-- 4. Unique Operators Converted -->
    <div class="bg-slate-950 p-5 rounded-3xl border border-slate-800 shadow-lg">
        <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
            <span class="font-bold uppercase tracking-wider text-[10px]">Unique Agents</span>
            <span>👥</span>
        </div>
        <div class="text-2xl font-black font-mono text-purple-300">
            {{ number_format($analytics['unique_users_count']) }}
        </div>
        <div class="text-[11px] text-slate-500 mt-1">Distinct operators redeemed</div>
    </div>
</div>
