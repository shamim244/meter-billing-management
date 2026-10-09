<!-- Box 4: 📄 Official PDF Reading -->
<div class="bg-white dark:bg-slate-800 p-2.5 sm:p-3 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm flex flex-col justify-between">
    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block truncate">📄 PDF Read</span>
    <div class="text-base sm:text-lg font-black my-0.5 sm:my-1 font-mono text-center" :class="bill.official_pdf_reading ? 'text-slate-800 dark:text-white' : 'text-slate-400'" x-text="bill.official_pdf_reading ?? '—'"></div>
    <div class="border-t border-slate-100 dark:border-slate-700/60 pt-1 truncate">
        <template x-if="bill.pdf_sync_status === 'ahead'">
            <span class="inline-flex items-center gap-0.5 text-[8px] sm:text-[9px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-1 sm:px-1.5 py-0.2 rounded" x-text="'⚡ +' + (bill.pdf_delta ?? 0) + ' Ahead'"></span>
        </template>
        <template x-if="bill.pdf_sync_status === 'matched'">
            <span class="inline-flex items-center gap-0.5 text-[8px] sm:text-[9px] font-bold text-blue-600 dark:text-cyan-400 bg-blue-50 dark:bg-blue-950/60 px-1 sm:px-1.5 py-0.2 rounded">✅ Exact Match</span>
        </template>
        <template x-if="bill.pdf_sync_status === 'invalid_behind'">
            <span class="inline-flex items-center gap-0.5 text-[8px] sm:text-[9px] font-black text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-950 px-1 sm:px-1.5 py-0.2 rounded animate-pulse" x-text="'🚨 ' + (bill.pdf_delta ?? 0) + ' Behind!'"></span>
        </template>
        <template x-if="bill.pdf_sync_status === 'awaiting'">
            <span class="inline-flex items-center gap-0.5 text-[8px] sm:text-[9px] font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-1 sm:px-1.5 py-0.2 rounded">⏳ Awaiting</span>
        </template>
    </div>
</div>
