<div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
    <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
        <span>💳</span> Payment Channel Distribution
    </h3>
    <p class="text-xs text-slate-400">Total volume and revenue collected across each payment method.</p>

    <div class="space-y-3">
        <!-- PG Gateway -->
        <div class="p-4 bg-slate-900 rounded-xl border border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-lg font-bold">⚡</div>
                <div>
                    <div class="font-bold text-xs text-white">Instant PG (Cashfree / Razorpay)</div>
                    <div class="text-[11px] text-slate-400">{{ $modeBreakdown['pg']['count'] }} successful transactions</div>
                </div>
            </div>
            <div class="text-right">
                <div class="font-mono font-black text-white text-sm">₹{{ number_format($modeBreakdown['pg']['amount'], 2) }}</div>
                <span class="text-[10px] text-emerald-400 font-semibold">Auto-verified</span>
            </div>
        </div>

        <!-- Manual UPI -->
        <div class="p-4 bg-slate-900 rounded-xl border border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center text-lg font-bold">📱</div>
                <div>
                    <div class="font-bold text-xs text-white">Manual UPI Transfers</div>
                    <div class="text-[11px] text-slate-400">{{ $modeBreakdown['manual_upi']['count'] }} verified payments</div>
                </div>
            </div>
            <div class="text-right">
                <div class="font-mono font-black text-white text-sm">₹{{ number_format($modeBreakdown['manual_upi']['amount'], 2) }}</div>
                <span class="text-[10px] text-purple-400 font-semibold">Zero Fee UPI</span>
            </div>
        </div>

        <!-- Bank Transfer -->
        <div class="p-4 bg-slate-900 rounded-xl border border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center text-lg font-bold">🏦</div>
                <div>
                    <div class="font-bold text-xs text-white">Bank Transfer (NEFT / IMPS)</div>
                    <div class="text-[11px] text-slate-400">{{ $modeBreakdown['bank_transfer']['count'] }} verified deposits</div>
                </div>
            </div>
            <div class="text-right">
                <div class="font-mono font-black text-white text-sm">₹{{ number_format($modeBreakdown['bank_transfer']['amount'], 2) }}</div>
                <span class="text-[10px] text-blue-400 font-semibold">High Value</span>
            </div>
        </div>
    </div>
</div>
