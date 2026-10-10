<div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4 shadow-xl">
    <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
        <span>💳</span> 2. Base Price & Overage Rates
    </h2>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Base Monthly Reference Price (₹)</label>
            <input type="number" step="0.01" min="0" name="base_price" x-model.number="basePrice" @input="recalculateDurations()" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono font-bold">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Extra MRU Rate (₹) <span class="text-rose-400">*</span></label>
            <input type="number" step="0.01" min="0" name="extra_mru_rate" value="{{ old('extra_mru_rate', $plan->extra_mru_rate ?? 20.00) }}" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Extra Consumer Rate (₹) <span class="text-rose-400">*</span></label>
            <input type="number" step="0.01" min="0" name="extra_consumer_rate" value="{{ old('extra_consumer_rate', $plan->extra_consumer_rate ?? 0.20) }}" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
        </div>
    </div>
</div>
