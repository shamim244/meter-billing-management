<!-- Status Action Buttons (Mobile-Optimized Clean Widths) -->
<div class="px-3 sm:px-5 py-2.5 bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
    <button @click="updateBillStatus(bill, 'submitted')" :class="bill.review_status === 'submitted' ? 'bg-emerald-600 text-white shadow-md ring-2 ring-emerald-400 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 hover:text-emerald-700'" class="flex-1 py-2 sm:py-2.5 rounded-xl font-bold text-xs transition active:scale-95 flex items-center justify-center gap-1">
        <span>✅ Submitted</span>
        <span class="hidden sm:inline-block text-[9px] font-mono opacity-60 bg-black/10 dark:bg-white/10 px-1 rounded" x-text="'[' + (shortcuts.submit_ok || 'Enter') + ']'"></span>
    </button>
    <button @click="updateBillStatus(bill, 'critical')" :class="bill.review_status === 'critical' ? 'bg-rose-600 text-white shadow-md ring-2 ring-rose-400 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-rose-50 dark:hover:bg-rose-950/50 hover:text-rose-700'" class="flex-1 py-2 sm:py-2.5 rounded-xl font-bold text-xs transition active:scale-95 flex items-center justify-center gap-1">
        <span>❌ Critical</span>
        <span class="hidden sm:inline-block text-[9px] font-mono opacity-60 bg-black/10 dark:bg-white/10 px-1 rounded" x-text="'[' + (shortcuts.mark_critical || '3') + ']'"></span>
    </button>
    <button @click="updateBillStatus(bill, 'doubt')" :class="bill.review_status === 'doubt' ? 'bg-amber-600 text-white shadow-md ring-2 ring-amber-400 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-amber-50 dark:hover:bg-amber-950/50 hover:text-amber-700'" class="flex-1 py-2 sm:py-2.5 rounded-xl font-bold text-xs transition active:scale-95 flex items-center justify-center gap-1">
        <span>⚠️ Doubt</span>
        <span class="hidden sm:inline-block text-[9px] font-mono opacity-60 bg-black/10 dark:bg-white/10 px-1 rounded" x-text="'[' + (shortcuts.mark_doubt || '2') + ']'"></span>
    </button>
</div>
