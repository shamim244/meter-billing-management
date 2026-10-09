<!-- MODAL: Add New Duration -->
<div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div @click.outside="showAddModal = false" class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl p-6 space-y-5 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>➕</span> Add New Duration Tier
            </h3>
            <button @click="showAddModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.plans.durations.store', $plan) }}" class="space-y-4">
            @csrf

            <!-- 1. Duration Unit Selection (Days vs Months) -->
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Duration Unit Type *</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition" :class="form.unit === 'month' ? 'bg-indigo-500/10 border-indigo-500 text-white font-bold' : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'">
                        <input type="radio" name="duration_unit" value="month" x-model="form.unit" @change="recalculatePrice()" class="text-indigo-600 focus:ring-0">
                        <div>
                            <div class="text-xs">📅 Month-Wise</div>
                            <div class="text-[10px] text-slate-400 font-normal">e.g. 1, 3, 6, 12, 24 months</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition" :class="form.unit === 'day' ? 'bg-amber-500/10 border-amber-500 text-white font-bold' : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'">
                        <input type="radio" name="duration_unit" value="day" x-model="form.unit" @change="recalculatePrice()" class="text-amber-500 focus:ring-0">
                        <div>
                            <div class="text-xs">⏱️ Day-Wise</div>
                            <div class="text-[10px] text-slate-400 font-normal">e.g. 7, 14, 15, 30, 45 days</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- 2. Value & Optional Label -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                        Duration Number <span class="text-rose-400">*</span>
                    </label>
                    <input type="number" min="1" max="3650" name="duration_value" x-model.number="form.value" @input="recalculatePrice()" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono font-bold">
                    <span class="text-[10px] text-slate-500 mt-1 block" x-text="'Validity: ' + form.value + ' ' + (form.unit === 'day' ? 'Day(s)' : 'Month(s)')"></span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Display Label (Optional)</label>
                    <input type="text" name="name" x-model="form.name" placeholder="e.g. 7 Days Trial, Annual" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                </div>
            </div>

            <!-- 3. Pricing & Discounts -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Discount %</label>
                    <input type="number" step="0.1" min="0" max="100" name="discount_percent" x-model.number="form.discount" @input="recalculatePrice()" placeholder="0" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Final Payable Price (₹) <span class="text-rose-400">*</span></label>
                    <input type="number" step="0.01" min="0" name="final_price" x-model.number="form.price" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-emerald-400 font-bold p-2.5 focus:ring-indigo-500 font-mono">
                </div>
            </div>

            <!-- 4. Optional Overage Overrides -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-800/80">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Extra MRU Rate (₹)</label>
                    <input type="number" step="0.01" min="0" name="extra_mru_rate" x-model="form.extraMru" placeholder="Base (₹{{ $plan->extra_mru_rate }})" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Extra CA Rate (₹)</label>
                    <input type="number" step="0.01" min="0" name="extra_consumer_rate" x-model="form.extraConsumer" placeholder="Base (₹{{ $plan->extra_consumer_rate }})" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                </div>
            </div>

            <!-- 5. Active Status Toggle -->
            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="is_active" value="1" x-model="form.isActive" id="add_is_active" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-0">
                <label for="add_is_active" class="text-xs font-semibold text-slate-300 cursor-pointer">
                    Enable immediately for subscriber checkouts
                </label>
            </div>

            <!-- Actions -->
            <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                <button type="button" @click="showAddModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30 cursor-pointer">
                    Save Duration Tier
                </button>
            </div>
        </form>
    </div>
</div>
