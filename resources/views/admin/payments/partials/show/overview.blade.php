<div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
    <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400">Transaction Details</h2>
    
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
        <div class="p-3 bg-slate-900 rounded-xl border border-slate-800">
            <span class="text-slate-400 block mb-1">Amount</span>
            <span class="text-lg font-black text-white font-mono">₹{{ number_format((float)$payment->amount, 2) }}</span>
        </div>

        <div class="p-3 bg-slate-900 rounded-xl border border-slate-800">
            <span class="text-slate-400 block mb-1">Payment Mode</span>
            <span class="font-bold text-slate-200 flex items-center gap-1.5 mt-0.5">
                <span>{{ $payment->mode->icon() }}</span>
                <span>{{ $payment->mode->label() }}</span>
            </span>
        </div>

        <div class="p-3 bg-slate-900 rounded-xl border border-slate-800">
            <span class="text-slate-400 block mb-1">Payment Purpose</span>
            <span class="font-bold text-cyan-300 mt-0.5 block">{{ $payment->purpose->label() }}</span>
        </div>
    </div>

    <!-- Mode Specific Identifiers -->
    <div class="p-4 bg-slate-900 rounded-xl border border-slate-800 text-xs space-y-2">
        @if($payment->utr_number)
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                <span class="text-slate-400 font-semibold">UPI UTR Number:</span>
                <span class="font-mono font-bold text-cyan-300 text-sm">{{ $payment->utr_number }}</span>
            </div>
        @endif

        @if($payment->bank_reference)
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                <span class="text-slate-400 font-semibold">Bank Transfer Reference:</span>
                <span class="font-mono font-bold text-purple-300 text-sm">{{ $payment->bank_reference }}</span>
            </div>
        @endif

        @if($payment->gateway_order_id)
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                <span class="text-slate-400 font-semibold">Gateway Order ID:</span>
                <span class="font-mono text-slate-200">{{ $payment->gateway_order_id }}</span>
            </div>
        @endif

        @if($payment->gateway_payment_id)
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                <span class="text-slate-400 font-semibold">Gateway Payment ID:</span>
                <span class="font-mono text-emerald-300 font-bold">{{ $payment->gateway_payment_id }}</span>
            </div>
        @endif

        @if($payment->verified_by)
            <div class="flex items-center justify-between pt-1">
                <span class="text-slate-400 font-semibold">Verified By:</span>
                <span class="font-semibold text-slate-200">
                    {{ $payment->verifiedBy->name ?? 'Admin #' . $payment->verified_by }} ({{ $payment->verified_at?->format('d M Y, h:i A') }})
                </span>
            </div>
        @endif

        @if($payment->rejection_reason)
            <div class="p-3 mt-2 rounded-lg bg-rose-950/60 border border-rose-500/30 text-rose-300">
                <span class="font-bold block mb-0.5">Rejection Reason:</span>
                {{ $payment->rejection_reason }}
            </div>
        @endif
    </div>
</div>
