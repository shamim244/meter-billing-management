<div class="glass-panel rounded-3xl border border-slate-800 overflow-hidden">
    <div class="p-5 border-b border-slate-800 flex items-center justify-between">
        <div>
            <h3 class="text-base font-bold text-white">Your Referrals & Earnings History</h3>
            <p class="text-xs text-slate-400 mt-0.5">Track reward milestones and payout clearance</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="border-b border-slate-800 bg-slate-900/60 text-slate-400 font-semibold uppercase tracking-wider">
                    <th class="py-3.5 px-5">Referred Agent</th>
                    <th class="py-3.5 px-5">Payment Trigger</th>
                    <th class="py-3.5 px-5">Reward Earned</th>
                    <th class="py-3.5 px-5">Status & Hold Info</th>
                    <th class="py-3.5 px-5">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($stats['payouts'] as $payout)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3.5 px-5">
                            <div class="font-bold text-white">{{ $payout->referee?->name ?? 'Referred Agent' }}</div>
                            <div class="text-[11px] text-slate-400">{{ $payout->referee?->email }}</div>
                        </td>
                        <td class="py-3.5 px-5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold {{ str_contains($payout->qualifying_payment_reference_type, 'subscription') ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' : 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20' }}">
                                {{ str_contains($payout->qualifying_payment_reference_type, 'subscription') ? '📦 Subscription' : '💳 Wallet Top-Up' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-5 font-bold text-emerald-400 text-sm">
                            +₹{{ number_format($payout->reward_amount, 2) }}
                        </td>
                        <td class="py-3.5 px-5">
                            @if($payout->status === 'pending')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20">
                                    ⏳ Hold: {{ $payout->hold_expires_at->diffForHumans() }}
                                </span>
                            @elseif($payout->status === 'paid')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">
                                    ✅ Credited to Wallet
                                </span>
                            @elseif($payout->status === 'cancelled')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-300 border border-rose-500/20">
                                    🚫 Cancelled
                                </span>
                            @elseif($payout->status === 'clawed_back')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-500/10 text-red-300 border border-red-500/20">
                                    ↩️ Reversed
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-5 text-slate-400 text-[11px]">
                            {{ $payout->created_at->format('M d, Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-10 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <span class="text-3xl">🎁</span>
                                <p class="text-sm font-semibold text-slate-300">No referral rewards logged yet</p>
                                <p class="text-xs text-slate-500">Share your invite link above with fellow billing agents to start earning!</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($stats['payouts']->hasPages())
        <div class="p-4 border-t border-slate-800">
            {{ $stats['payouts']->links() }}
        </div>
    @endif
</div>
