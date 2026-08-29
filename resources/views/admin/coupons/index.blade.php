<x-admin-layout>
    <x-slot name="header">
        Coupon Code Campaigns & Discounts
    </x-slot>

    <div class="space-y-6" x-data="{
        selectedCoupons: [],
        selectAll: false,
        toggleAll() {
            if (this.selectAll) {
                this.selectedCoupons = Array.from(document.querySelectorAll('.coupon-checkbox')).map(cb => parseInt(cb.value));
            } else {
                this.selectedCoupons = [];
            }
        }
    }">
        <!-- Top Metrics Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Coupons</div>
                    <div class="text-xl font-black text-white font-mono mt-0.5">{{ number_format($stats['total_coupons']) }}</div>
                </div>
                <span class="p-2 bg-indigo-500/10 rounded-xl text-indigo-400 text-base">🎟️</span>
            </div>

            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Campaigns</div>
                    <div class="text-xl font-black text-emerald-400 font-mono mt-0.5">{{ number_format($stats['active_campaigns']) }}</div>
                </div>
                <span class="p-2 bg-emerald-500/10 rounded-xl text-emerald-400 text-base">✓</span>
            </div>

            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Redemptions</div>
                    <div class="text-xl font-black text-cyan-400 font-mono mt-0.5">{{ number_format($stats['total_redemptions']) }}</div>
                </div>
                <span class="p-2 bg-cyan-500/10 rounded-xl text-cyan-400 text-base">⚡</span>
            </div>

            <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Discount / Bonus</div>
                    <div class="text-xl font-black text-amber-400 font-mono mt-0.5">₹{{ number_format($stats['total_discount_given'], 2) }}</div>
                </div>
                <span class="p-2 bg-amber-500/10 rounded-xl text-amber-400 text-base">🎁</span>
            </div>
        </div>

        <!-- Filters & Actions Toolbar -->
        <div class="bg-slate-950 p-4 rounded-3xl border border-slate-800 shadow-lg flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <form method="GET" action="{{ route('admin.coupons.index') }}" class="flex flex-wrap items-center gap-2 flex-1">
                <div class="flex-1 min-w-[180px]">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search coupon code..." class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl px-3.5 py-2 text-white placeholder-slate-500 focus:ring-indigo-500 focus:border-indigo-500 uppercase">
                </div>

                <select name="type" class="text-xs bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-white focus:ring-indigo-500">
                    <option value="all" {{ $typeFilter === 'all' ? 'selected' : '' }}>All Types</option>
                    <option value="subscription_discount" {{ $typeFilter === 'subscription_discount' ? 'selected' : '' }}>Subscription Discount</option>
                    <option value="topup_bonus" {{ $typeFilter === 'topup_bonus' ? 'selected' : '' }}>Top-Up Bonus (Slabs)</option>
                </select>

                <select name="status" class="text-xs bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-white focus:ring-indigo-500">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition shrink-0">
                    Filter
                </button>

                @if(!empty($search) || $typeFilter !== 'all' || $statusFilter !== 'all')
                    <a href="{{ route('admin.coupons.index') }}" class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-slate-400 rounded-xl text-xs font-medium transition shrink-0">
                        Clear
                    </a>
                @endif
            </form>

            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('admin.coupons.create') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/20 transition text-center shrink-0">
                    <span>+</span> Create Coupon Code
                </a>
            </div>
        </div>

        <!-- Bulk Action Bar -->
        <div x-show="selectedCoupons.length > 0" x-cloak class="bg-indigo-950/90 border border-indigo-500/40 p-4 rounded-2xl shadow-xl flex items-center justify-between gap-3 animate-in fade-in duration-150">
            <div class="flex items-center gap-2 text-xs font-bold text-indigo-200">
                <span class="px-2 py-0.5 bg-indigo-600 text-white rounded-md font-mono" x-text="selectedCoupons.length"></span>
                <span>coupon(s) selected</span>
            </div>

            <form method="POST" action="{{ route('admin.coupons.bulk-deactivate') }}" class="flex items-center gap-2">
                @csrf
                <template x-for="id in selectedCoupons" :key="id">
                    <input type="hidden" name="coupon_ids[]" :value="id">
                </template>

                <button type="submit" onclick="return confirm('Deactivate selected coupon campaigns?');" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold shadow transition">
                    🚫 Deactivate Selected
                </button>
            </form>
        </div>

        <!-- Coupons Table -->
        <div class="bg-slate-950 rounded-3xl border border-slate-800 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/90 border-b border-slate-800 text-[11px] uppercase font-bold text-slate-400 tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4 text-center w-10">
                                <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="py-3.5 px-4">Coupon Code</th>
                            <th class="py-3.5 px-4">Type</th>
                            <th class="py-3.5 px-4">Discount / Slabs</th>
                            <th class="py-3.5 px-4 text-center">Redemptions</th>
                            <th class="py-3.5 px-4 text-center">Validity Window</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-5 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70 font-medium">
                        @forelse($coupons as $coupon)
                            <tr class="hover:bg-slate-900/40 transition">
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

                                <!-- Status -->
                                <td class="py-3.5 px-4 text-center">
                                    <form method="POST" action="{{ route('admin.coupons.toggle', $coupon) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase transition {{ $coupon->is_active ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-900' : 'bg-slate-800 text-slate-400 border border-slate-700 hover:bg-slate-700' }}">
                                            {{ $coupon->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </form>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-5 text-center">
                                    <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                        <!-- Analytics / Show -->
                                        <a href="{{ route('admin.coupons.show', $coupon) }}" class="px-2 py-1 rounded-lg text-xs font-bold bg-slate-900 hover:bg-slate-800 text-indigo-300 border border-indigo-500/30 transition" title="View Analytics & Logs">
                                            👁️ Show
                                        </a>

                                        <!-- Edit -->
                                        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="px-2 py-1 rounded-lg text-xs font-bold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 transition" title="Edit Coupon">
                                            ✏️ Edit
                                        </a>

                                        <!-- Delete -->
                                        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Delete coupon {{ $coupon->code }}?');" class="px-2 py-1 rounded-lg text-xs font-bold bg-rose-950/60 hover:bg-rose-900 text-rose-300 border border-rose-500/30 transition" title="Delete Coupon">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-500">
                                    No coupon campaigns created yet. Click "+ Create Coupon Code" to launch your first promotion!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($coupons->hasPages())
                <div class="px-6 py-4 border-t border-slate-800 bg-slate-900/60">
                    {{ $coupons->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
