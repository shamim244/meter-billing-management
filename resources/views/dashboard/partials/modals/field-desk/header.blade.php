<!-- Header -->
<div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
    <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-cyan-500 text-white flex items-center justify-center font-bold text-base shadow-md shadow-emerald-500/20">
            📋
        </div>
        <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                <span>FieldDesk Quick Bridge</span>
                <span class="text-[10px] font-extrabold uppercase px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">Tier 2</span>
            </h3>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 font-mono" x-text="'CA: ' + (fieldDeskBill?.ca_number || '—') + (fieldDeskBill?.consumer_name ? ' • ' + fieldDeskBill.consumer_name : '')"></p>
        </div>
    </div>
    <button type="button" @click="showFieldDeskModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
</div>
