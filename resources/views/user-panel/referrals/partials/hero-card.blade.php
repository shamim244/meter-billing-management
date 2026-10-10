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
