<!-- Subscription Transition Logs -->
<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <h2 class="text-sm font-bold text-white flex items-center gap-1.5">
            <span>🔄</span> Plan Transition History
        </h2>
    </div>

    <div class="space-y-2.5">
        @forelse($transitions as $trans)
            <div class="p-3 bg-slate-900/60 rounded-2xl border border-slate-800/80 text-xs space-y-1">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-white capitalize">
                        {{ $trans->action_type }}
                    </span>
                    <span class="text-[10px] font-mono font-bold text-indigo-400">
                        ₹{{ number_format($trans->amount_charged, 2) }}
                    </span>
                </div>
                <div class="text-[11px] text-slate-400">
                    {{ $trans->fromPlan->name ?? 'Start' }} → <strong class="text-slate-200">{{ $trans->toPlan->name ?? 'Target' }}</strong>
                </div>
                <div class="text-[10px] text-slate-500 font-mono">
                    {{ $trans->created_at->format('M d, Y h:i A') }}
                </div>
            </div>
        @empty
            <div class="py-6 text-center text-xs text-slate-500">
                No plan transitions on record.
            </div>
        @endforelse
    </div>
</div>
