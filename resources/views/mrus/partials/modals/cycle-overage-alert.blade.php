<!-- Cycle Overage Confirmation Alert -->
<div x-show="cycleOverageRequired" class="p-4 rounded-2xl space-y-3 transition border" :class="cycleOverageInsufficient ? 'bg-rose-50/90 dark:bg-rose-950/50 border-rose-200 dark:border-rose-800/80' : 'bg-amber-50 dark:bg-amber-950/60 border-amber-300 dark:border-amber-700/80'">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 font-bold text-xs" :class="cycleOverageInsufficient ? 'text-rose-800 dark:text-rose-300' : 'text-amber-800 dark:text-amber-300'">
            <span x-text="cycleOverageInsufficient ? '⛔' : '⚠️'"></span>
            <span x-text="cycleOverageInsufficient ? 'Insufficient Wallet Balance' : 'Consumer Quota Notice'"></span>
        </div>
        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase" :class="cycleOverageInsufficient ? 'bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300' : 'bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300'">
            Overage ₹<span x-text="cycleOverageAmount"></span>
        </span>
    </div>

    <p class="text-xs leading-relaxed" :class="cycleOverageInsufficient ? 'text-rose-900 dark:text-rose-200' : 'text-amber-900 dark:text-amber-200'" x-text="cycleOverageMessage"></p>

    <!-- Balance comparison strip -->
    <div class="flex items-center justify-between text-xs py-2 px-3 rounded-xl border" :class="cycleOverageInsufficient ? 'bg-white/80 dark:bg-slate-900/80 border-rose-200/80 dark:border-rose-900/60' : 'bg-amber-100/60 dark:bg-amber-900/40 border-amber-200 dark:border-amber-800/60'">
        <span class="text-slate-600 dark:text-slate-400">Overage Fee: <strong class="font-mono text-slate-900 dark:text-white">₹<span x-text="cycleOverageAmount"></span></strong></span>
        <span>Wallet Balance: <strong class="font-mono font-bold" :class="cycleOverageInsufficient ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'">₹<span x-text="Number(cycleOverageWalletBalance).toFixed(2)"></span></strong></span>
    </div>

    <!-- Case A: Balance is Sufficient -> Confirm & Pay Button -->
    <template x-if="!cycleOverageInsufficient">
        <div class="space-y-2 pt-1">
            <button type="button" @click="launchBillingCycle(executingAction, true)" :disabled="cycleInProgress" class="w-full py-2.5 px-4 bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow transition flex items-center justify-center gap-1.5">
                <svg x-show="cycleInProgress" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span>✓ Confirm & Pay ₹<span x-text="cycleOverageAmount"></span> from Wallet</span>
            </button>
            <div class="text-center">
                <a :href="cycleUpgradeUrl || '{{ route('user-panel.subscription') }}'" class="text-[11px] text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-cyan-400 underline transition">
                    Or upgrade your plan to increase consumer quota →
                </a>
            </div>
        </div>
    </template>

    <!-- Case B: Balance is Insufficient -> Direct Links to Top Up & Upgrade -->
    <template x-if="cycleOverageInsufficient">
        <div class="space-y-2 pt-1">
            <div class="flex flex-col sm:flex-row gap-2">
                <a :href="cycleTopupUrl || '{{ route('wallet.index') }}'" target="_blank" class="flex-1 py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold text-center shadow-sm transition flex items-center justify-center gap-1.5">
                    <span>💳</span> Add Funds / Top Up Wallet
                </a>
                <a :href="cycleUpgradeUrl || '{{ route('user-panel.subscription') }}'" class="flex-1 py-2.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold text-center shadow-sm transition flex items-center justify-center gap-1.5">
                    <span>⚡</span> Upgrade Plan
                </a>
            </div>
            <button type="button" @click="launchBillingCycle(executingAction, true)" :disabled="cycleInProgress" class="w-full py-2 px-3 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold transition flex items-center justify-center gap-1.5">
                <svg x-show="cycleInProgress" class="animate-spin h-3.5 w-3.5 text-slate-600 dark:text-slate-300" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span>🔄 I've Added Funds — Retry Cycle</span>
            </button>
        </div>
    </template>
</div>
