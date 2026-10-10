<div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
    <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
        <span>📜</span> Audit Action History
    </h2>

    <div class="space-y-3">
        @forelse($payment->auditLogs as $log)
            <div class="p-3.5 bg-slate-900 rounded-xl border border-slate-800 text-xs flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        @if($log->action->value === 'approved')
                            <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold text-[10px] uppercase">Approved</span>
                        @elseif($log->action->value === 'rejected')
                            <span class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 font-bold text-[10px] uppercase">Rejected</span>
                        @elseif($log->action->value === 'refunded')
                            <span class="px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 font-bold text-[10px] uppercase">Refunded</span>
                        @endif
                        <span class="text-slate-300 font-semibold">by {{ $log->admin->name ?? 'Admin' }}</span>
                    </div>
                    @if($log->notes)
                        <p class="text-slate-400 italic">"{{ $log->notes }}"</p>
                    @endif
                </div>
                <span class="text-[11px] text-slate-500 whitespace-nowrap">{{ $log->created_at->format('d M Y, h:i A') }}</span>
            </div>
        @empty
            <p class="text-xs text-slate-500 italic">No admin actions recorded yet for this transaction.</p>
        @endforelse
    </div>
</div>
