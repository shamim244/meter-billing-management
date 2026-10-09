<!-- Box 2: 📅 Previous Reading (DB) -->
<div class="bg-white dark:bg-slate-800 p-2.5 sm:p-3 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm flex flex-col justify-between">
    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block truncate">📅 Prev (DB)</span>
    <div class="text-base sm:text-lg font-black text-slate-700 dark:text-slate-200 my-0.5 sm:my-1 font-mono text-center" x-text="bill.db_prev_reading ?? '—'"></div>
    <div class="text-[9px] sm:text-[10px] text-slate-400 border-t border-slate-100 dark:border-slate-700/60 pt-1 truncate" x-text="bill.db_prev_label ? (bill.db_prev_label.startsWith('From ') ? bill.db_prev_label : 'From: ' + bill.db_prev_label) : 'Baseline'"></div>
</div>
