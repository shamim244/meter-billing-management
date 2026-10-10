<div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.payments.index') }}" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
            ← Back to Queue
        </a>
        <div>
            <h1 class="text-xl font-black text-white flex items-center gap-2">
                <span>💳</span> Payment #{{ $payment->id }}
            </h1>
            <p class="text-xs text-slate-400">Created on {{ $payment->created_at->format('d M Y, h:i:s A') }}</p>
        </div>
    </div>

    <!-- Status Badge -->
    <div>
        @if($payment->status->value === 'pending_verification')
            <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 inline-flex items-center gap-1.5">
                <span>⏳</span> Pending Admin Verification
            </span>
        @elseif($payment->status->value === 'success')
            <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 inline-flex items-center gap-1.5">
                <span>✅</span> Payment Successful
            </span>
        @elseif($payment->status->value === 'rejected')
            <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40 inline-flex items-center gap-1.5">
                <span>❌</span> Payment Rejected
            </span>
        @else
            <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700">
                {{ $payment->status->label() }}
            </span>
        @endif
    </div>
</div>
