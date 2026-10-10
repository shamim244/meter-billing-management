<!-- For MRU Select -->
<div>
    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Target MRU Workspace</label>
    <select x-model="selectedMruId" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500">
        <option value="">Select MRU Workspace...</option>
        <template x-for="m in mruList" :key="m.id">
            <option :value="m.id" x-text="m.name + ' (' + m.code + ') — ' + m.consumer_accounts_count + ' consumers'"></option>
        </template>
    </select>
</div>

<!-- Warning if selected MRU has 0 consumers -->
<div x-show="selectedMruHasNoConsumers" class="p-3.5 bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/70 rounded-2xl space-y-2">
    <div class="flex items-start gap-2.5 text-amber-800 dark:text-amber-200 text-xs">
        <span class="text-base leading-none mt-0.5">⚠️</span>
        <div class="space-y-0.5">
            <span class="font-bold">No Active Consumers in Selected MRU</span>
            <p class="text-[11px] text-amber-700 dark:text-amber-300 leading-relaxed">
                <span class="font-bold" x-text="selectedMru?.name + ' (' + selectedMru?.code + ')'"></span> currently has 0 registered consumers. Add or import consumers to this MRU before launching a billing cycle.
            </p>
        </div>
    </div>
    <div class="pt-2 border-t border-amber-200/60 dark:border-amber-800/60 flex items-center justify-end">
        <a :href="'/mrus/' + selectedMruId" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
            <span>👥 Open MRU & Add Consumers</span>
            <span>→</span>
        </a>
    </div>
</div>
