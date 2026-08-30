<x-admin-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </span>
                    <div>
                        <h1 class="text-2xl font-bold text-white tracking-tight">Top Referrers Leaderboard</h1>
                        <p class="text-xs text-slate-400">Identify top performing billing agents driving platform acquisition</p>
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
                <a href="{{ route('admin.referrals.activity') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold border border-slate-700 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Activity Log
                </a>
            </div>
        </div>

        <!-- Leaderboard Table Card -->
        <div class="glass-card rounded-3xl border border-slate-800/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800/80 bg-slate-900/60 text-slate-400 font-semibold uppercase tracking-wider">
                            <th class="py-3.5 px-4 text-center w-12">Rank</th>
                            <th class="py-3.5 px-4">Agent Name & Email</th>
                            <th class="py-3.5 px-4 text-center">Total Signups</th>
                            <th class="py-3.5 px-4 text-center">Paid Conversions</th>
                            <th class="py-3.5 px-4 text-right">Pending Rewards</th>
                            <th class="py-3.5 px-4 text-right">Lifetime Earnings</th>
                            <th class="py-3.5 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($leaderboard as $index => $agent)
                            @php
                                $rank = ($leaderboard->currentPage() - 1) * $leaderboard->perPage() + $index + 1;
                            @endphp
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-3.5 px-4 text-center font-black">
                                    @if($rank === 1)
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 text-xs">🥇</span>
                                    @elseif($rank === 2)
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-400/20 text-slate-300 border border-slate-400/40 text-xs">🥈</span>
                                    @elseif($rank === 3)
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-700/20 text-amber-500 border border-amber-700/40 text-xs">🥉</span>
                                    @else
                                        <span class="text-slate-500 text-xs font-bold">#{{ $rank }}</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white flex items-center gap-1.5">
                                        {{ $agent->name }}
                                        <a href="{{ route('admin.users.show', $agent) }}" class="text-slate-500 hover:text-purple-400 transition" title="Inspect User Dossier">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    </div>
                                    <div class="text-[11px] text-slate-400">{{ $agent->email }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-white">
                                    {{ number_format($agent->total_signups) }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-semibold text-cyan-400">
                                    {{ number_format($agent->paid_payouts_count) }}
                                    <span class="text-[10px] text-slate-500">({{ $agent->total_signups > 0 ? round(($agent->paid_payouts_count / $agent->total_signups) * 100, 1) : 0 }}%)</span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-medium text-amber-400">
                                    ₹{{ number_format((float) $agent->pending_earnings, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-black text-emerald-400 text-sm">
                                    ₹{{ number_format((float) $agent->total_earnings, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.referrals.activity', ['referrer_id' => $agent->id]) }}" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-semibold border border-slate-700 transition">
                                            Logs
                                        </a>
                                        <a href="{{ route('admin.users.show', $agent) }}" class="px-2.5 py-1 rounded-lg bg-purple-600/20 hover:bg-purple-600 text-purple-300 hover:text-white text-[11px] font-semibold border border-purple-500/30 transition">
                                            Dossier
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-500">
                                    No referral records logged yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($leaderboard->hasPages())
                <div class="p-4 border-t border-slate-800/80">
                    {{ $leaderboard->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
