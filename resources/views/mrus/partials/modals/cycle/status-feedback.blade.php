<!-- Live Progress Box -->
<div x-show="cycleInProgress" class="bg-slate-950 text-cyan-300 p-4 rounded-2xl font-mono text-xs space-y-1.5 border border-slate-800 shadow-inner">
    <div class="flex items-center gap-2 text-white font-bold">
        <svg class="animate-spin h-4 w-4 text-cyan-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <span x-text="executingAction === 'create_only' ? 'Initializing cycle workspace...' : 'Launching cycle & pulling PDFs...'"></span>
    </div>
    <div class="text-slate-400 text-[10px]">Processing consumers concurrently. Please wait...</div>
</div>

<!-- Cycle Overage Confirmation Alert -->
@include('mrus.partials.modals.cycle-overage-alert')

<!-- Result notification -->
<div x-show="cycleResult && !cycleOverageRequired" class="p-4 rounded-2xl text-xs font-semibold" :class="cycleResult?.success ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800'">
    <div class="flex items-start gap-2.5">
        <span class="text-base leading-none mt-0.5" x-text="cycleResult?.success ? '✅' : '⚠️'"></span>
        <div class="flex-1 space-y-2">
            <div x-text="cycleResult?.message"></div>
            <template x-if="cycleResult?.requires_subscription || cycleResult?.redirect_url">
                <div class="pt-2 border-t border-rose-200 dark:border-rose-800/60 flex flex-wrap items-center gap-2">
                    <a :href="cycleResult?.redirect_url || '{{ route('user-panel.subscription') }}'" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl text-xs shadow-md transition active:scale-95">
                        <span>⚡ Choose a Plan / Activate Free Tier</span>
                        <span>→</span>
                    </a>
                </div>
            </template>
        </div>
    </div>
</div>
