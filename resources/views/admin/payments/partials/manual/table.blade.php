{{-- Verification Table --}}
<div class="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-900/80 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800 font-bold">
                <tr>
                    <th class="py-3 px-4">Payment ID & Time</th>
                    <th class="py-3 px-4">Billing Agent</th>
                    <th class="py-3 px-4">Mode & Transaction Reference</th>
                    <th class="py-3 px-4">Amount</th>
                    <th class="py-3 px-4">Receipt Proof</th>
                    <th class="py-3 px-4 text-right">Verification Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($pendingPayments as $p)
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="py-3.5 px-4">
                            <div class="font-mono font-bold text-white">#{{ $p->id }}</div>
                            <div class="text-[10px] text-slate-400">{{ $p->created_at->format('d M Y, h:i A') }}</div>
                            <span class="text-[9px] text-slate-500">({{ $p->created_at->diffForHumans() }})</span>
                        </td>

                        <td class="py-3.5 px-4">
                            <div class="font-semibold text-white">{{ $p->user->name ?? 'Agent #' . $p->user_id }}</div>
                            <div class="text-[11px] text-slate-400">{{ $p->user->email ?? 'No email' }}</div>
                            @if(!empty($p->user->phone))
                                <div class="text-[10px] text-slate-500 font-mono">{{ $p->user->phone }}</div>
                            @endif
                        </td>

                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-1.5 mb-1">
                                @if($p->mode->value === 'manual_upi')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-400 border border-purple-500/20">📱 UPI QR</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">🏦 Bank Transfer</span>
                                @endif
                            </div>
                            <div class="font-mono text-slate-200 text-xs font-bold flex items-center gap-1">
                                <span>Ref:</span>
                                <span class="text-cyan-300">{{ $p->utr_number ?: ($p->bank_reference ?: '—') }}</span>
                            </div>
                            <span class="text-[10px] text-slate-400">Purpose: {{ $p->purpose->label() }}</span>
                        </td>

                        <td class="py-3.5 px-4">
                            <span class="text-base font-black text-white font-mono">₹{{ number_format((float)$p->amount, 2) }}</span>
                        </td>

                        <td class="py-3.5 px-4">
                            @if($p->screenshot_path)
                                <button type="button" @click="openPreview('{{ Storage::url($p->screenshot_path) }}')" class="flex items-center gap-1.5 p-1.5 rounded-xl bg-slate-900 border border-slate-800 hover:border-indigo-500/50 transition group">
                                    <img src="{{ Storage::url($p->screenshot_path) }}" alt="Receipt" class="w-10 h-10 object-cover rounded-lg">
                                    <span class="text-[11px] text-indigo-400 group-hover:text-indigo-300 font-bold pr-1">View Slip</span>
                                </button>
                            @else
                                <span class="text-slate-500 text-[11px] italic">No image uploaded</span>
                            @endif
                        </td>

                        <td class="py-3.5 px-4 text-right space-x-1.5">
                            <button type="button" @click="openApprove({{ json_encode($p) }})" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition shadow-sm shadow-emerald-600/30">
                                ✓ Approve
                            </button>
                            <button type="button" @click="openReject({{ json_encode($p) }})" class="px-3 py-1.5 bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30 rounded-xl text-xs font-bold transition">
                                ✕ Reject
                            </button>
                            <a href="{{ route('admin.payments.show', $p->id) }}" class="px-2 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                                Details →
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">
                            <div class="text-3xl mb-2">🎉</div>
                            <div class="text-sm font-bold text-slate-300">All Manual Payments Verified!</div>
                            <div class="text-xs text-slate-500 mt-1">No pending UPI or Bank Transfer records in queue.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($pendingPayments->hasPages())
        <div class="p-4 border-t border-slate-800">
            {{ $pendingPayments->links() }}
        </div>
    @endif
</div>
