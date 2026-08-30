<x-user-panel-layout>
    <x-slot name="header">
        Refer & Earn Program
    </x-slot>

    <div class="space-y-8" x-data="{
        showRegenerateModal: false,
        copiedCode: false,
        copiedLink: false,
        copyToClipboard(text, type) {
            navigator.clipboard.writeText(text).then(() => {
                if (type === 'code') {
                    this.copiedCode = true;
                    setTimeout(() => this.copiedCode = false, 2500);
                } else {
                    this.copiedLink = true;
                    setTimeout(() => this.copiedLink = false, 2500);
                }
            });
        }
    }">
        <!-- Alert banners -->
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Hero Share & Invitation Card -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-purple-900/40 via-slate-900/90 to-slate-950 border border-purple-500/30 p-6 sm:p-10 shadow-2xl">
            <div class="absolute -right-12 -top-12 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-12 -bottom-12 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 max-w-3xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 text-xs font-bold mb-4 tracking-wider uppercase">
                    <span>🎁 Invite Fellow Billing Agents</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                    Earn Real Wallet Rewards for Every Agent You Refer
                </h1>
                <p class="text-sm text-slate-300 mt-2 leading-relaxed">
                    Share your unique referral code or link. When your invited agent joins and makes their first qualifying subscription or top-up, you earn automatic wallet credits directly into your balance!
                </p>

                <!-- Code & Link Box -->
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Referral Code Box -->
                    <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-700/80 flex flex-col justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Your Referral Code</span>
                        <div class="flex items-center justify-between mt-2">
                            <span class="font-mono text-xl sm:text-2xl font-black text-purple-300 tracking-wider">
                                {{ $stats['referral_code'] }}
                            </span>
                            <button type="button" 
                                    @click="copyToClipboard('{{ $stats['referral_code'] }}', 'code')"
                                    class="px-3 py-1.5 rounded-xl bg-purple-600/20 hover:bg-purple-600 text-purple-300 hover:text-white text-xs font-bold border border-purple-500/30 transition flex items-center gap-1.5">
                                <span x-show="!copiedCode">📋 Copy Code</span>
                                <span x-show="copiedCode" class="text-emerald-400" x-cloak>✓ Copied!</span>
                            </button>
                        </div>
                    </div>

                    <!-- Shareable Link Box -->
                    <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-700/80 flex flex-col justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Shareable Invite Link</span>
                        <div class="flex items-center justify-between mt-2 gap-2">
                            <input type="text" readonly value="{{ $stats['share_url'] }}" class="w-full bg-transparent font-mono text-xs text-slate-300 truncate focus:outline-none select-all">
                            <button type="button" 
                                    @click="copyToClipboard('{{ $stats['share_url'] }}', 'link')"
                                    class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-600 transition flex items-center gap-1.5 whitespace-nowrap">
                                <span x-show="!copiedLink">🔗 Copy Link</span>
                                <span x-show="copiedLink" class="text-emerald-400" x-cloak>✓ Copied!</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons: WhatsApp Share & Regenerate -->
                @php
                    $waText = urlencode("Hey! Join the NBPDCL Electricity Meter Billing Management platform for seamless PDF ledger downloads and billing automation. Use my referral link to get started: " . $stats['share_url']);
                    $waUrl = "https://api.whatsapp.com/send?text=" . $waText;
                @endphp
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs sm:text-sm shadow-lg shadow-emerald-600/20 transition flex items-center gap-2">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.043.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.203c.043.072.043.419-.101.824z"/>
                        </svg>
                        Share via WhatsApp
                    </a>

                    <button type="button" 
                            @click="showRegenerateModal = true"
                            class="px-4 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs sm:text-sm font-semibold border border-slate-700 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Regenerate Code
                    </button>
                </div>
            </div>
        </div>

        <!-- 4 Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="glass-panel p-5 rounded-2xl border border-slate-800">
                <span class="text-xs text-slate-400 font-medium">Referred Agents</span>
                <p class="text-2xl font-black text-white mt-1">{{ number_format($stats['total_referred']) }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Friends who joined via your link</p>
            </div>
            <div class="glass-panel p-5 rounded-2xl border border-slate-800">
                <span class="text-xs text-slate-400 font-medium">Pending Rewards</span>
                <p class="text-2xl font-black text-amber-400 mt-1">₹{{ number_format($stats['pending_rewards'], 2) }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">In hold period (matures soon)</p>
            </div>
            <div class="glass-panel p-5 rounded-2xl border border-slate-800">
                <span class="text-xs text-slate-400 font-medium">Paid Rewards</span>
                <p class="text-2xl font-black text-emerald-400 mt-1">₹{{ number_format($stats['paid_rewards'], 2) }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Credited directly to your wallet</p>
            </div>
            <div class="glass-panel p-5 rounded-2xl border border-slate-800">
                <span class="text-xs text-slate-400 font-medium">Lifetime Earned</span>
                <p class="text-2xl font-black text-purple-400 mt-1">₹{{ number_format($stats['total_earned'], 2) }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Total earnings from program</p>
            </div>
        </div>

        <!-- Referrals Activity Log Table -->
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

        <!-- Regenerate Confirmation Modal -->
        <div x-show="showRegenerateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" x-cloak>
            <div @click.outside="showRegenerateModal = false" class="w-full max-w-md rounded-3xl bg-slate-900 border border-slate-800 p-6 shadow-2xl space-y-4">
                <div class="flex items-center gap-3">
                    <span class="p-2.5 rounded-2xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-white">Regenerate Referral Code?</h3>
                        <p class="text-xs text-slate-400">Issue a fresh referral code & invite link</p>
                    </div>
                </div>

                <p class="text-xs text-slate-300 leading-relaxed">
                    Generating a new code will immediately <strong>deactivate your current link ({{ $stats['referral_code'] }})</strong> for new registrations. Any referrals currently in the hold period will <strong>remain completely protected and continue to pay out normally</strong>.
                </p>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="showRegenerateModal = false" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition">
                        Cancel
                    </button>
                    <form action="{{ route('referrals.regenerate') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition">
                            Yes, Issue New Code
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-user-panel-layout>
