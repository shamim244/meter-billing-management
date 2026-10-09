{{-- Recent Delivery Attempts Log --}}
<div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
    <div class="p-4 border-b border-slate-800/80">
        <h2 class="text-xs font-bold text-slate-300 uppercase tracking-wider">
            Recent Email Delivery Attempts (Last 25)
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-950/80 border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                    <th class="py-2.5 px-3">Time</th>
                    <th class="py-2.5 px-3">Recipient</th>
                    <th class="py-2.5 px-3">Event</th>
                    <th class="py-2.5 px-3">Provider Used</th>
                    <th class="py-2.5 px-3 text-center">Status</th>
                    <th class="py-2.5 px-3">Failure Reason</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-slate-300 font-mono text-[11px]">
                @forelse($recentDeliveries as $del)
                    <tr>
                        <td class="py-2 px-3 text-slate-400">{{ $del->created_at?->diffForHumans() }}</td>
                        <td class="py-2 px-3 font-sans font-semibold text-white">{{ $del->notification?->user?->email ?? '—' }}</td>
                        <td class="py-2 px-3 text-cyan-400">{{ $del->notification?->event_type }}</td>
                        <td class="py-2 px-3">{{ $del->emailProviderInstance?->label ?? 'None' }}</td>
                        <td class="py-2 px-3 text-center">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $del->status === 'sent' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300' }}">
                                {{ $del->status }}
                            </span>
                        </td>
                        <td class="py-2 px-3 text-slate-400 truncate max-w-[200px]">{{ $del->failed_reason ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-4 text-center text-slate-500 font-sans">No recent email deliveries logged.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
