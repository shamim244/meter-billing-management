<!-- Empty State -->
<div x-show="!loading && items.length === 0" class="bg-white dark:bg-slate-900/90 rounded-3xl p-10 text-center border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
    <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-3xl">
        📋
    </div>
    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">
        No FieldDesk Actions Found
    </h3>
    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto">
        No consumer commitments match the current timeline or filter criteria. Create a new follow-up action to track payment promises or field visits.
    </p>
    <button @click="openCreateModal()" class="mt-2 inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition">
        <span>➕</span> Create First Action
    </button>
</div>
