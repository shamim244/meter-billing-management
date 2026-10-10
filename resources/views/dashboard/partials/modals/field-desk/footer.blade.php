<!-- Footer Bridge: Jump to Dedicated FieldDesk Workspace -->
<div class="p-4 sm:p-5 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
    <a :href="'/field-desk?ca=' + (fieldDeskBill?.ca_number || '')"
       class="inline-flex items-center gap-1.5 font-bold text-blue-600 dark:text-cyan-400 hover:underline">
        <span>↗ Open in FieldDesk Hub</span>
    </a>
    <button type="button" @click="showFieldDeskModal = false" class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 font-bold transition">
        Close
    </button>
</div>
