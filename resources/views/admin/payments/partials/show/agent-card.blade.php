<div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
    <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400">Billing Agent Profile</h2>

    @if($payment->user)
        <div class="space-y-3 text-xs">
            <div>
                <span class="text-slate-400 block text-[11px]">Billing Agent Name</span>
                <span class="text-sm font-bold text-white">{{ $payment->user->name }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px]">Email Address</span>
                <span class="text-slate-200 font-mono">{{ $payment->user->email }}</span>
            </div>
            @if($payment->user->phone)
                <div>
                    <span class="text-slate-400 block text-[11px]">Phone Number</span>
                    <span class="text-cyan-300 font-mono">{{ $payment->user->phone }}</span>
                </div>
            @endif
            <div>
                <span class="text-slate-400 block text-[11px]">Account Status</span>
                <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-200 font-semibold text-[10px] uppercase">
                    {{ $payment->user->status ?? 'Active' }}
                </span>
            </div>
        </div>
    @else
        <p class="text-xs text-slate-500">Billing agent record not found.</p>
    @endif
</div>
