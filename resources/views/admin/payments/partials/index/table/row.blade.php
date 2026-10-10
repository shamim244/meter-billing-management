<tr class="hover:bg-slate-900/50 transition">
    <td class="px-4 py-3.5 whitespace-nowrap">
        <div class="font-mono font-bold text-white">#{{ $payment->id }}</div>
        <div class="text-[11px] text-slate-400 mt-0.5">{{ $payment->created_at->format('d M Y, h:i A') }}</div>
    </td>
    <td class="px-4 py-3.5">
        <div class="font-semibold text-white">{{ $payment->user->name ?? 'Unknown Agent' }}</div>
        <div class="text-[11px] text-slate-400">{{ $payment->user->email ?? '-' }}</div>
        @if($payment->user?->phone)
            <div class="text-[10px] text-cyan-400/80 font-mono">{{ $payment->user->phone }}</div>
        @endif
    </td>
    <td class="px-4 py-3.5 whitespace-nowrap">
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 font-semibold text-slate-200">
            <span>{{ $payment->mode->icon() }}</span>
            <span>{{ $payment->mode->label() }}</span>
        </div>
        <div class="text-[11px] text-slate-400 mt-1">
            {{ $payment->purpose->label() }}
        </div>
    </td>
    <td class="px-4 py-3.5 whitespace-nowrap">
        <div class="text-sm font-black text-white font-mono">
            ₹{{ number_format((float)$payment->amount, 2) }}
        </div>
        <span class="text-[10px] text-slate-400 uppercase font-semibold">{{ $payment->currency }}</span>
    </td>
    <td class="px-4 py-3.5">
        @if($payment->utr_number)
            <div class="flex items-center gap-1.5">
                <span class="text-[10px] uppercase font-bold text-slate-400">UTR:</span>
                <span class="font-mono font-bold text-cyan-300">{{ $payment->utr_number }}</span>
            </div>
        @endif
        @if($payment->bank_reference)
            <div class="flex items-center gap-1.5">
                <span class="text-[10px] uppercase font-bold text-slate-400">Ref:</span>
                <span class="font-mono font-bold text-purple-300">{{ $payment->bank_reference }}</span>
            </div>
        @endif
        @if($payment->gateway_payment_id)
            <div class="flex items-center gap-1.5">
                <span class="text-[10px] uppercase font-bold text-slate-400">PG ID:</span>
                <span class="font-mono text-[11px] text-emerald-300">{{ $payment->gateway_payment_id }}</span>
            </div>
        @endif
        @if($payment->screenshot_url)
            <button @click="openScreenshot('{{ $payment->screenshot_url }}')" class="mt-1 text-[11px] text-blue-400 hover:text-blue-300 underline font-semibold flex items-center gap-1">
                <span>📷</span> View Screenshot
            </button>
        @endif
    </td>
    <td class="px-4 py-3.5 whitespace-nowrap">
        @if($payment->status->value === 'pending_verification')
            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 inline-flex items-center gap-1 animate-pulse">
                <span>⏳</span> Pending Review
            </span>
        @elseif($payment->status->value === 'success')
            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 inline-flex items-center gap-1">
                <span>✅</span> Successful
            </span>
        @elseif($payment->status->value === 'rejected')
            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40 inline-flex items-center gap-1" title="{{ $payment->rejection_reason }}">
                <span>❌</span> Rejected
            </span>
        @elseif($payment->status->value === 'failed')
            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40 inline-flex items-center gap-1">
                <span>⚠️</span> Failed
            </span>
        @else
            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700 inline-flex items-center gap-1">
                <span>⏱️</span> Pending
            </span>
        @endif
    </td>
    <td class="px-4 py-3.5 text-right whitespace-nowrap">
        <div class="flex items-center justify-end gap-1.5">
            @if($payment->status->value === 'pending_verification')
                <button @click="openApprove({{ json_encode($payment) }})" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-sm">
                    <span>✅</span> Approve
                </button>
                <button @click="openReject({{ json_encode($payment) }})" class="px-2.5 py-1.5 bg-rose-600/80 hover:bg-rose-600 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-sm">
                    <span>❌</span> Reject
                </button>
            @elseif($payment->status->value === 'success')
                <button @click="openRefund({{ json_encode($payment) }})" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs font-semibold transition flex items-center gap-1" title="Log manual refund">
                    <span>🔄</span> Refund
                </button>
            @endif

            <a href="{{ route('admin.payments.show', $payment->id) }}" class="p-1.5 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg transition" title="Inspect Full Payment Trail">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </a>
        </div>
    </td>
</tr>
