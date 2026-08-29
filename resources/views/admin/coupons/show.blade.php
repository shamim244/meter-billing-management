<x-admin-layout>
    <x-slot name="header">
        Coupon Analytics — {{ $coupon->code }}
    </x-slot>

    <div class="space-y-6">
        <!-- Top Back & Action Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <a href="{{ route('admin.coupons.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-white transition">
                <span>←</span> Back to All Coupons
            </a>

            <div class="flex items-center gap-2">
                <!-- Toggle Status Form -->
                <form method="POST" action="{{ route('admin.coupons.toggle', $coupon) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ $coupon->is_active ? 'bg-amber-950/70 hover:bg-amber-900 text-amber-300 border border-amber-500/30' : 'bg-emerald-950/70 hover:bg-emerald-900 text-emerald-300 border border-emerald-500/30' }}">
                        {{ $coupon->is_active ? '🚫 Deactivate' : '✓ Activate' }}
                    </button>
                </form>

                <!-- Edit Button -->
                <a href="{{ route('admin.coupons.edit', $coupon) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 rounded-xl text-xs font-bold transition">
                    <span>✏️</span> Edit Campaign
                </a>

                <!-- Delete Button -->
                <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('Delete coupon campaign {{ $coupon->code }}?');" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-rose-950 hover:bg-rose-900 text-rose-300 border border-rose-500/40 transition">
                        <span>🗑️</span> Delete
                    </button>
                </form>
            </div>
        </div>

        <!-- Hero Identity Card -->
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

        <!-- 4 Analytics Metric Cards -->
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

        <!-- Top-Up Slabs Table (If applicable) -->
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

        <!-- Redemptions Audit Log Table -->
        <div class="bg-slate-950 rounded-3xl border border-slate-800 shadow-xl overflow-hidden space-y-4 p-6">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <span>📜</span> Redemption Audit Logs ({{ $redemptions->total() }})
                </h2>
                <span class="text-xs text-slate-400 font-medium">Immutable transaction ledger</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/80 text-slate-400 uppercase font-bold text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Operator / Agent</th>
                            <th class="py-3 px-4">Action Type</th>
                            <th class="py-3 px-4 text-center">Original ₹</th>
                            <th class="py-3 px-4 text-center">Discount/Bonus ₹</th>
                            <th class="py-3 px-4 text-center">Final Amount ₹</th>
                            <th class="py-3 px-4 text-center">Redeemed Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-medium">
                        @forelse($redemptions as $redemption)
                            <tr class="hover:bg-slate-900/40 transition">
                                <td class="py-3 px-4">
                                    @if($redemption->user)
                                        <a href="{{ route('admin.users.show', $redemption->user) }}" class="font-bold text-white hover:text-indigo-400 transition">
                                            {{ $redemption->user->name }}
                                        </a>
                                        <div class="text-[10px] text-slate-400">{{ $redemption->user->email }}</div>
                                    @else
                                        <span class="text-slate-500">Deleted User (#{{ $redemption->user_id }})</span>
                                    @endif
                                </td>

                                <td class="py-3 px-4 capitalize">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $redemption->redeemed_for_type === 'subscription_payment' ? 'bg-purple-950 text-purple-300 border border-purple-500/30' : 'bg-cyan-950 text-cyan-300 border border-cyan-500/30' }}">
                                        {{ str_replace('_', ' ', $redemption->redeemed_for_type) }}
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-center font-mono font-bold text-slate-300">
                                    ₹{{ number_format($redemption->original_amount, 2) }}
                                </td>

                                <td class="py-3 px-4 text-center font-mono font-bold text-emerald-400">
                                    {{ $redemption->redeemed_for_type === 'subscription_payment' ? '-' : '+' }}₹{{ number_format($redemption->discount_or_bonus_amount, 2) }}
                                </td>

                                <td class="py-3 px-4 text-center font-mono font-black text-white">
                                    ₹{{ number_format($redemption->final_amount, 2) }}
                                </td>

                                <td class="py-3 px-4 text-center text-slate-400 font-mono text-[11px]">
                                    {{ $redemption->redeemed_at->format('M d, Y h:i A') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-500">
                                    No agents have redeemed this coupon code yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($redemptions->hasPages())
                <div class="pt-4 border-t border-slate-800">
                    {{ $redemptions->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
