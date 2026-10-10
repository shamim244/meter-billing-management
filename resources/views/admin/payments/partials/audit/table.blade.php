<!-- Audit Table -->
<div class="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-900/80 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800 font-bold">
                <tr>
                    <th class="py-3 px-4">Log ID & Timestamp</th>
                    <th class="py-3 px-4">Action</th>
                    <th class="py-3 px-4">Admin Operator</th>
                    <th class="py-3 px-4">Payment & Billing Agent</th>
                    <th class="py-3 px-4">Details / Notes</th>
                    <th class="py-3 px-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($auditLogs as $log)
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="py-3.5 px-4">
                            <div class="font-mono font-bold text-white">#{{ $log->id }}</div>
                            <div class="text-[10px] text-slate-400">{{ $log->created_at->format('d M Y, h:i:s A') }}</div>
                            <span class="text-[9px] text-slate-500">({{ $log->created_at->diffForHumans() }})</span>
                        </td>

                        <td class="py-3.5 px-4">
                            @php $act = $log->action->value ?? $log->action; @endphp
                            @if($act === 'approved')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    ✓ APPROVED
                                </span>
                            @elseif($act === 'rejected')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                    ✕ REJECTED
                                </span>
                            @elseif($act === 'refunded')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                    🔄 REFUNDED
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                    {{ strtoupper($act) }}
                                </span>
                            @endif
                        </td>

                        <td class="py-3.5 px-4">
                            @if($log->admin)
                                <div class="font-semibold text-white">{{ $log->admin->name }}</div>
                                <div class="text-[10px] text-slate-400">{{ $log->admin->email }}</div>
                            @else
                                <span class="text-slate-500 italic">Automated System Hook</span>
                            @endif
                        </td>

                        <td class="py-3.5 px-4">
                            @if($log->payment)
                                <div class="flex items-center gap-1.5 mb-0.5">
                                    <a href="{{ route('admin.payments.show', $log->payment_id) }}" class="font-mono font-bold text-indigo-400 hover:text-indigo-300">
                                        Payment #{{ $log->payment_id }}
                                    </a>
                                    <span class="text-white font-mono font-bold">₹{{ number_format((float)$log->payment->amount, 2) }}</span>
                                </div>
                                <div class="text-[11px] text-slate-400">Agent: {{ $log->payment->user->name ?? 'User #' . $log->payment->user_id }}</div>
                            @else
                                <span class="font-mono text-slate-500">Payment #{{ $log->payment_id }}</span>
                            @endif
                        </td>

                        <td class="py-3.5 px-4">
                            <div class="text-xs text-slate-300 max-w-sm break-words">
                                {{ $log->notes ?: '—' }}
                            </div>
                        </td>

                        <td class="py-3.5 px-4 text-right">
                            @if($log->payment_id)
                                <a href="{{ route('admin.payments.show', $log->payment_id) }}" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                                    View Payment →
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-500">
                            No audit log records found matching the query.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($auditLogs->hasPages())
        <div class="p-4 border-t border-slate-800">
            {{ $auditLogs->links() }}
        </div>
    @endif
</div>
