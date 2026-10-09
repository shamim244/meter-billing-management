{{-- 7. Wallet System & Alert Thresholds --}}
<div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
            <span>👛</span> 7. Wallet & Balance Alert Thresholds
        </h2>
        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
            CONFIGURABLE
        </span>
    </div>
    <p class="text-xs text-slate-400">Configure balance alert triggers and minimum top-up requirements across the platform.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Low Balance Alert Threshold (₹) <span class="text-rose-400">*</span></label>
            <input type="number" step="1" min="0" name="wallet_low_balance_threshold" value="{{ $settings['wallet_low_balance_threshold'] ?? 200.0 }}" required placeholder="200" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
            <span class="text-[10px] text-slate-500 mt-1 block">Fires WalletLowBalanceEvent when agent balance drops below this amount (default ₹200.00).</span>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Minimum Payment / Top-Up Amount (₹) <span class="text-rose-400">*</span></label>
            <input type="number" step="1" min="1" name="min_amount" value="{{ $settings['min_amount'] }}" required placeholder="100" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
            <span class="text-[10px] text-slate-500 mt-1 block">Minimum allowed transaction amount across all payment checkout forms.</span>
        </div>
    </div>
</div>
