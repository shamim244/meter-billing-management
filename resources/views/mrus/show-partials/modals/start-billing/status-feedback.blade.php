<!-- Live Progress Box -->
<div x-show="billingInProgress" class="bg-slate-950 text-cyan-300 p-4 rounded-2xl font-mono text-xs space-y-1.5 border border-slate-800 shadow-inner">
    <div class="flex items-center gap-2 text-white font-bold">
        <svg class="animate-spin h-4 w-4 text-cyan-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <span x-text="executingAction === 'create_only' ? 'Initializing cycle workspace...' : 'Launching cycle & pulling PDFs...'"></span>
    </div>
    <div class="text-slate-400 text-[10px]">Processing consumers concurrently. Please wait...</div>
</div>

<!-- Cycle Overage Confirmation Alert -->
<div x-show="cycleOverageRequired" class="p-4 bg-amber-50 dark:bg-amber-950/60 border border-amber-300 dark:border-amber-700/80 rounded-2xl space-y-2">
    <div class="flex items-center gap-2 text-amber-800 dark:text-amber-300 font-bold text-xs">
        <span>⚠️</span> Consumer Quota Notice
    </div>
    <p class="text-xs text-amber-900 dark:text-amber-200" x-text="cycleOverageMessage"></p>
    <button type="button" @click="triggerMruBilling(executingAction, true)" :disabled="billingInProgress" class="w-full mt-2 py-2.5 px-4 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow transition flex items-center justify-center gap-1.5">
        <span>✓</span> Confirm & Pay ₹<span x-text="cycleOverageAmount"></span> from Wallet
    </button>
</div>

<!-- Result message -->
<div x-show="billingResult && !cycleOverageRequired" class="p-3.5 rounded-2xl text-xs font-semibold" :class="billingResult?.success ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800'" x-text="billingResult?.message"></div>
