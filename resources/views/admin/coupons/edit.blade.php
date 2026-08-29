<x-admin-layout>
    <x-slot name="header">
        Edit Coupon Campaign — {{ $coupon->code }}
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6" x-data="{
        type: '{{ $coupon->type }}',
        discountKind: '{{ $coupon->discount_kind ?? 'percentage' }}',
        slabs: {{ json_encode($coupon->slabs->map(fn($s) => ['min_amount' => (float)$s->min_amount, 'max_amount' => $s->max_amount ? (float)$s->max_amount : '', 'bonus_percent' => (float)$s->bonus_percent])->values()->all()) }},
        addSlab() {
            const last = this.slabs[this.slabs.length - 1];
            const nextMin = last && last.max_amount ? parseInt(last.max_amount) + 1 : 10000;
            this.slabs.push({ min_amount: nextMin, max_amount: '', bonus_percent: 20 });
        },
        removeSlab(index) {
            if (this.slabs.length > 1) {
                this.slabs.splice(index, 1);
            }
        }
    }">
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.coupons.show', $coupon) }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-white transition">
                <span>←</span> Back to Coupon Details
            </a>
        </div>

        <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}" class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-semibold space-y-1">
                    <div class="font-bold flex items-center gap-1.5">
                        <span>❌</span> Validation Errors:
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-400">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Code & Type Info -->
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

            <!-- Code Input -->
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Coupon Code <span class="text-rose-400">*</span></label>
                <input type="text" name="code" value="{{ old('code', $coupon->code) }}" required class="w-full text-sm font-mono font-bold bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-white uppercase tracking-wider focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Subscription Discount Specific Settings -->
            @if($coupon->type === 'subscription_discount')
                <div class="space-y-4 bg-slate-900/50 p-5 rounded-2xl border border-slate-800">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-400">Subscription Discount Rules</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Discount Kind</label>
                            <select name="discount_kind" x-model="discountKind" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl py-2 px-3 text-white">
                                <option value="percentage" {{ $coupon->discount_kind === 'percentage' ? 'selected' : '' }}>Percentage (% OFF)</option>
                                <option value="flat" {{ $coupon->discount_kind === 'flat' ? 'selected' : '' }}>Flat Amount (₹ FLAT OFF)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">
                                <span x-text="discountKind === 'percentage' ? 'Discount Percentage (%)' : 'Flat Discount Amount (₹)'"></span>
                                <span class="text-rose-400">*</span>
                            </label>
                            <input type="number" step="0.01" min="0.01" name="discount_value" value="{{ old('discount_value', $coupon->discount_value) }}" class="w-full text-xs font-mono font-bold bg-slate-950 border-slate-800 rounded-xl py-2 px-3 text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Plan Restriction (Optional)</label>
                            <select name="plan_restriction_id" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl py-2 px-3 text-white">
                                <option value="">All Subscription Plans</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" {{ $coupon->plan_restriction_id == $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Minimum Purchase (₹)</label>
                            <input type="number" step="0.01" name="minimum_amount" value="{{ old('minimum_amount', $coupon->minimum_amount) }}" placeholder="Optional (e.g. 299.00)" class="w-full text-xs font-mono bg-slate-950 border-slate-800 rounded-xl py-2 px-3 text-white">
                        </div>
                    </div>
                </div>
            @endif

            <!-- Top-Up Bonus Slabs Builder -->
            @if($coupon->type === 'topup_bonus')
                <div class="space-y-4 bg-slate-900/50 p-5 rounded-2xl border border-slate-800">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-cyan-400">Recharge Amount Bonus Slabs</h3>
                        <button type="button" @click="addSlab()" class="px-3 py-1 bg-cyan-600/20 hover:bg-cyan-600/30 text-cyan-300 border border-cyan-500/30 rounded-lg text-xs font-bold transition">
                            + Add Tier Slab
                        </button>
                    </div>

                    <div class="space-y-2.5">
                        <template x-for="(slab, index) in slabs" :key="index">
                            <div class="grid grid-cols-12 gap-2 items-center bg-slate-950 p-3 rounded-xl border border-slate-800">
                                <div class="col-span-4">
                                    <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Min Amount (₹)</label>
                                    <input type="number" :name="'slabs['+index+'][min_amount]'" x-model="slab.min_amount" required min="0" class="w-full text-xs font-mono bg-slate-900 border-slate-800 rounded-lg py-1.5 px-2 text-white">
                                </div>
                                <div class="col-span-4">
                                    <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Max Amount (₹)</label>
                                    <input type="number" :name="'slabs['+index+'][max_amount]'" x-model="slab.max_amount" placeholder="No limit" class="w-full text-xs font-mono bg-slate-900 border-slate-800 rounded-lg py-1.5 px-2 text-white">
                                </div>
                                <div class="col-span-3">
                                    <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Bonus %</label>
                                    <input type="number" step="0.1" :name="'slabs['+index+'][bonus_percent]'" x-model="slab.bonus_percent" required min="0.01" max="100" class="w-full text-xs font-mono font-bold bg-slate-900 border-slate-800 rounded-lg py-1.5 px-2 text-emerald-400">
                                </div>
                                <div class="col-span-1 text-center pt-3">
                                    <button type="button" @click="removeSlab(index)" x-show="slabs.length > 1" class="text-rose-400 hover:text-rose-300 p-1 font-bold">✕</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            @endif

            <!-- Limits & Schedule (Shared) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Usage Limit Per User</label>
                    <input type="number" name="usage_limit_per_user" value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user) }}" min="1" max="1000" required class="w-full text-xs font-mono bg-slate-900 border-slate-800 rounded-xl py-2 px-3 text-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Total Platform Redemptions Cap</label>
                    <input type="number" name="usage_limit_total" value="{{ old('usage_limit_total', $coupon->usage_limit_total) }}" min="1" placeholder="Leave empty for unlimited" class="w-full text-xs font-mono bg-slate-900 border-slate-800 rounded-xl py-2 px-3 text-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Schedule Start Date (Optional)</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon->starts_at ? $coupon->starts_at->format('Y-m-d\TH:i') : '') }}" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl py-2 px-3 text-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Expiration Date (Optional)</label>
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $coupon->expires_at ? $coupon->expires_at->format('Y-m-d\TH:i') : '') }}" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl py-2 px-3 text-white">
                </div>
            </div>

            <!-- Status Checkbox -->
            <div class="pt-2">
                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-white">
                    <input type="checkbox" name="is_active" value="1" {{ $coupon->is_active ? 'checked' : '' }} class="rounded bg-slate-900 border-slate-800 text-indigo-600 focus:ring-indigo-500">
                    <span>Coupon campaign is active and eligible for redemptions</span>
                </label>
            </div>

            <!-- Submit Buttons -->
            <div class="border-t border-slate-800 pt-5 flex justify-end gap-3">
                <a href="{{ route('admin.coupons.show', $coupon) }}" class="px-5 py-2 rounded-xl text-xs font-bold bg-slate-900 hover:bg-slate-800 text-slate-300 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 rounded-xl text-xs font-black bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
