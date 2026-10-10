<!-- Activity Table -->
<div class="glass-card rounded-3xl border border-slate-800/80 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="border-b border-slate-800/80 bg-slate-900/60 text-slate-400 font-semibold uppercase tracking-wider">
                    <th class="py-3.5 px-4">Payout ID</th>
                    <th class="py-3.5 px-4">Referrer (Earner)</th>
                    <th class="py-3.5 px-4">Referee (Joined)</th>
                    <th class="py-3.5 px-4">Trigger Ref</th>
                    <th class="py-3.5 px-4">Reward Amount</th>
                    <th class="py-3.5 px-4">Status & Hold Info</th>
                    <th class="py-3.5 px-4">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($payouts as $payout)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3.5 px-4 font-mono text-slate-400">#{{ $payout->id }}</td>
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-white">{{ $payout->referrer?->name ?? 'Unknown' }}</div>
                            <div class="text-[11px] text-slate-400">{{ $payout->referrer?->email }}</div>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-medium text-white">{{ $payout->referee?->name ?? 'Unknown' }}</div>
                            <div class="text-[11px] text-slate-400">{{ $payout->referee?->email }}</div>
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold {{ str_contains($payout->qualifying_payment_reference_type, 'subscription') ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' : 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20' }}">
                                {{ str_contains($payout->qualifying_payment_reference_type, 'subscription') ? 'Subscription' : 'Top-Up' }}
                            </span>
                            <div class="font-mono text-[10px] text-slate-500 mt-0.5">{{ $payout->qualifying_payment_reference_id }}</div>
                        </td>
                        <td class="py-3.5 px-4 font-bold text-emerald-400 text-sm">
                            ₹{{ number_format($payout->reward_amount, 2) }}
                        </td>
                        <td class="py-3.5 px-4">
                            @if($payout->status === 'pending')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                    ⏳ Pending (Hold: {{ $payout->hold_expires_at->diffForHumans() }})
                                </span>
                            @elseif($payout->status === 'paid')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    ✅ Paid to Wallet
                                </span>
                            @elseif($payout->status === 'cancelled')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20" title="{{ $payout->clawback_reason }}">
                                    🚫 Cancelled
                                </span>
                            @elseif($payout->status === 'clawed_back')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-red-500/10 text-red-400 border border-red-500/20" title="{{ $payout->clawback_reason }}">
                                    ↩️ Clawed Back
                                </span>
                            @endif

                            @if($payout->clawback_reason)
                                <div class="text-[10px] text-slate-500 mt-0.5 truncate max-w-xs">{{ $payout->clawback_reason }}</div>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-slate-400 text-[11px]">
                            {{ $payout->created_at->format('M d, Y H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-500">
                            No referral payouts found matching the filter criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($payouts->hasPages())
        <div class="p-4 border-t border-slate-800/80">
            {{ $payouts->links() }}
        </div>
    @endif
</div>
