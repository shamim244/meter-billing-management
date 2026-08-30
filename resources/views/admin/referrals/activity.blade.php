<x-admin-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </span>
                    <div>
                        <h1 class="text-2xl font-bold text-white tracking-tight">Referral Activity Log</h1>
                        <p class="text-xs text-slate-400">Complete audit trail of all generated referral rewards, hold periods, and payouts</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.referrals.settings') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold border border-slate-700 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Settings
                </a>
                <a href="{{ route('admin.referrals.top_referrers') }}" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-semibold shadow-lg shadow-purple-600/20 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                    Top Referrers
                </a>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="glass-card rounded-2xl p-4 border border-slate-800/80">
            <form action="{{ route('admin.referrals.activity') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search agent, referee, code..." class="w-full px-3.5 py-2 rounded-xl bg-slate-900/80 border border-slate-700 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500">
                </div>
                <div>
                    <select name="status" class="w-full px-3.5 py-2 rounded-xl bg-slate-900/80 border border-slate-700 text-xs text-white focus:outline-none focus:border-purple-500">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>⏳ Pending Hold</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>✅ Paid to Wallet</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>🚫 Cancelled</option>
                        <option value="clawed_back" {{ request('status') === 'clawed_back' ? 'selected' : '' }}>↩️ Clawed Back</option>
                    </select>
                </div>
                <div>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-3.5 py-2 rounded-xl bg-slate-900/80 border border-slate-700 text-xs text-white focus:outline-none focus:border-purple-500">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition">Filter</button>
                    @if(request()->anyFilled(['search', 'status', 'start_date', 'referrer_id']))
                        <a href="{{ route('admin.referrals.activity') }}" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white text-xs font-semibold transition">Reset</a>
                    @endif
                </div>
            </form>
        </div>

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
    </div>
</x-admin-layout>
