<x-admin-layout>
    <div class="space-y-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
        basePrice: 499,
        durations: [
            { months: 1, discount: 0, price: 499, extraMru: null, extraConsumer: null },
            { months: 2, discount: 5, price: 948, extraMru: null, extraConsumer: null },
            { months: 3, discount: 10, price: 1347, extraMru: null, extraConsumer: null },
            { months: 6, discount: 15, price: 2545, extraMru: null, extraConsumer: null },
            { months: 12, discount: 20, price: 4790, extraMru: null, extraConsumer: null }
        ],
        recalculateDurations() {
            this.durations.forEach(d => {
                d.price = Math.round((this.basePrice * d.months) * (1 - (d.discount / 100)));
            });
        }
    }">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('admin.plans.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1 mb-2">
                    ← Back to Plans
                </a>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>➕</span> Create Subscription Plan
                </h1>
                <p class="text-sm text-slate-400 mt-1">Define plan quotas, rates, and duration discounts.</p>
            </div>
        </div>

        @if($errors->any())
            <div class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-300 text-xs font-semibold space-y-1">
                @foreach($errors->all() as $err)
                    <div>⚠️ {{ $err }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.plans.store') }}" class="space-y-6">
            @csrf

            <!-- 1. Plan Basic Info -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4 shadow-xl">
                <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>📦</span> 1. Plan Identity & Quota Limits
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Plan Name <span class="text-rose-400">*</span></label>
                        <input type="text" name="name" value="{{ old('name', 'Starter') }}" required placeholder="e.g. Starter / Pro / Enterprise" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-medium">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Status</label>
                        <select name="is_active" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                            <option value="1" selected>Active (Available for subscription)</option>
                            <option value="0">Draft / Inactive</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                        <textarea name="description" rows="2" placeholder="Brief details about who this plan is suitable for..." class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">{{ old('description') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Included MRUs Quota <span class="text-rose-400">*</span></label>
                        <input type="number" name="included_mrus" min="0" value="{{ old('included_mrus', 5) }}" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                        <span class="text-[10px] text-slate-500 mt-1 block">Maximum active MRUs without overage.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Included Consumers Quota / Cycle <span class="text-rose-400">*</span></label>
                        <input type="number" name="included_consumers" min="0" value="{{ old('included_consumers', 2500) }}" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                        <span class="text-[10px] text-slate-500 mt-1 block">Monthly consumer quota across all cycles.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Grace Period Days <span class="text-slate-500 font-normal">(Optional Override)</span></label>
                        <input type="number" name="grace_period_days" min="0" max="90" value="{{ old('grace_period_days') }}" placeholder="Platform Default (3)" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                        <span class="text-[10px] text-slate-500 mt-1 block">Leave empty to use platform default. Set 0 to disable grace.</span>
                    </div>
                </div>
            </div>

            <!-- 2. Base & Overage Rates -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4 shadow-xl">
                <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>💳</span> 2. Base Price & Overage Rates
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Base Monthly Price (₹) <span class="text-rose-400">*</span></label>
                        <input type="number" step="0.01" min="0" name="base_price" x-model.number="basePrice" @input="recalculateDurations()" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono font-bold">
                        <span class="text-[10px] text-slate-500 mt-1 block">1-month standard price.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Extra MRU Rate (₹) <span class="text-rose-400">*</span></label>
                        <input type="number" step="0.01" min="0" name="extra_mru_rate" value="{{ old('extra_mru_rate', 100.0) }}" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                        <span class="text-[10px] text-slate-500 mt-1 block">Per MRU beyond included quota.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Extra Consumer Rate (₹) <span class="text-rose-400">*</span></label>
                        <input type="number" step="0.01" min="0" name="extra_consumer_rate" value="{{ old('extra_consumer_rate', 0.20) }}" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                        <span class="text-[10px] text-slate-500 mt-1 block">Per Consumer beyond quota / cycle.</span>
                    </div>
                </div>
            </div>

            <!-- 3. Duration-Based Pricing Table -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4 shadow-xl">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <span>⏳</span> 3. Duration Pricing Table (1, 2, 3, 6, 12 Months)
                    </h2>
                    <span class="text-[10px] text-slate-400 bg-slate-800 px-2.5 py-1 rounded-lg">Admin Configurable</span>
                </div>
                <p class="text-xs text-slate-400">Specify discount percentages and final prices. You can manually override calculated amounts.</p>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                                <th class="pb-2">Duration</th>
                                <th class="pb-2">Discount %</th>
                                <th class="pb-2">Final Price (₹)</th>
                                <th class="pb-2">Extra MRU Rate (₹) <span class="text-slate-500 font-normal">(optional override)</span></th>
                                <th class="pb-2">Extra CA Rate (₹) <span class="text-slate-500 font-normal">(optional override)</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <template x-for="(d, index) in durations" :key="d.months">
                                <tr>
                                    <td class="py-2.5 font-bold text-white flex items-center gap-1.5">
                                        <span x-text="d.months + ' Month' + (d.months > 1 ? 's' : '')"></span>
                                        <input type="hidden" :name="'durations[' + index + '][duration_months]'" :value="d.months">
                                    </td>
                                    <td class="py-2.5 pr-3">
                                        <input type="number" step="0.1" min="0" max="100" :name="'durations[' + index + '][discount_percent]'" x-model.number="d.discount" @input="recalculateDurations()" class="w-20 text-xs bg-slate-950 border-slate-800 rounded-lg text-white p-1.5 focus:ring-indigo-500 font-mono">
                                    </td>
                                    <td class="py-2.5 pr-3">
                                        <input type="number" step="0.01" min="0" :name="'durations[' + index + '][final_price]'" x-model.number="d.price" class="w-28 text-xs bg-slate-950 border-slate-800 rounded-lg text-white p-1.5 focus:ring-indigo-500 font-mono font-bold text-emerald-400">
                                    </td>
                                    <td class="py-2.5 pr-3">
                                        <input type="number" step="0.01" min="0" :name="'durations[' + index + '][extra_mru_rate]'" placeholder="Base Rate" class="w-28 text-xs bg-slate-950 border-slate-800 rounded-lg text-white p-1.5 focus:ring-indigo-500 font-mono">
                                    </td>
                                    <td class="py-2.5">
                                        <input type="number" step="0.01" min="0" :name="'durations[' + index + '][extra_consumer_rate]'" placeholder="Base Rate" class="w-28 text-xs bg-slate-950 border-slate-800 rounded-lg text-white p-1.5 focus:ring-indigo-500 font-mono">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.plans.index') }}" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30 flex items-center gap-2">
                    <span>💾</span> Save & Publish Plan
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
