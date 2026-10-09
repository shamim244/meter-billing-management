{{-- 4. Minimum Amount Rule --}}
<div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
    <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
        <span>💵</span> 4. Minimum Payment Amount Threshold
    </h2>

    <div class="max-w-md">
        <label class="block text-xs font-semibold text-slate-300 mb-1">Minimum Allowed Payment (₹) <span class="text-rose-400">*</span></label>
        <div class="relative">
            <span class="absolute left-3.5 top-2.5 text-slate-400 font-bold text-xs">₹</span>
            <input type="number" step="1" min="1" name="min_amount" value="{{ $settings['min_amount'] }}" required class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white pl-8 pr-4 py-2.5 focus:ring-indigo-500 focus:border-indigo-500 font-mono font-bold">
        </div>
        <span class="text-[11px] text-slate-400 mt-1 block">Billing Agents cannot initiate top-up or checkout below this threshold.</span>
    </div>
</div>
