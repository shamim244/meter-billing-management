<div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
    <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-base">
            ⚡
        </div>
        <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">New Billing Cycle</h3>
            <p class="text-[11px] text-slate-400 dark:text-slate-500">Launch monthly cycle for MRU {{ $mru->code }}</p>
        </div>
    </div>
    <button @click="showStartBillingModal = false" :disabled="billingInProgress" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 disabled:opacity-40 p-1">✕</button>
</div>
