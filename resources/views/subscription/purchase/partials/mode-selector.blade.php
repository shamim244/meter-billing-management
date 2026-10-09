<!-- Payment Mode Tabs -->
<div>
    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
        <span>💳</span> Select Payment Mode
    </h2>
    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Choose how you wish to pay the fixed amount of ₹{{ number_format($pricingDetails['final_amount'], 2) }}.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    @if($settings['pg_enabled'])
        <label :class="mode === 'pg' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 ring-2 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300'" class="p-4 rounded-2xl border-2 cursor-pointer transition flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <input type="radio" name="mode" value="pg" x-model="mode" class="text-indigo-600 focus:ring-indigo-500">
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">Instant</span>
            </div>
            <div>
                <div class="font-bold text-xs">⚡ Online Gateway</div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">UPI, QR, Cards, NetBanking</div>
            </div>
        </label>
    @endif

    @if($settings['manual_upi_enabled'])
        <label :class="mode === 'manual_upi' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 ring-2 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300'" class="p-4 rounded-2xl border-2 cursor-pointer transition flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <input type="radio" name="mode" value="manual_upi" x-model="mode" class="text-indigo-600 focus:ring-indigo-500">
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300">Manual</span>
            </div>
            <div>
                <div class="font-bold text-xs">📱 Direct UPI Transfer</div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">GPay, PhonePe, Paytm QR</div>
            </div>
        </label>
    @endif

    @if($settings['bank_transfer_enabled'])
        <label :class="mode === 'bank_transfer' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 ring-2 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300'" class="p-4 rounded-2xl border-2 cursor-pointer transition flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <input type="radio" name="mode" value="bank_transfer" x-model="mode" class="text-indigo-600 focus:ring-indigo-500">
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300">NEFT/IMPS</span>
            </div>
            <div>
                <div class="font-bold text-xs">🏦 Bank Transfer</div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Direct NEFT, RTGS, IMPS</div>
            </div>
        </label>
    @endif
</div>
