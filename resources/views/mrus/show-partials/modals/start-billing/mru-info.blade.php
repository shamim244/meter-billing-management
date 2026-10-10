<!-- For MRU Info Card -->
<div class="bg-slate-50 dark:bg-slate-800/80 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 flex items-center justify-between">
    <div>
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Target MRU Workspace</span>
        <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $mru->name }} ({{ $mru->code }})</span>
    </div>
    <span class="px-2.5 py-1 rounded-xl text-xs font-mono font-bold {{ $consumers->total() === 0 ? 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700' : 'bg-blue-50 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800/60' }}">
        {{ $consumers->total() }} Consumers
    </span>
</div>

<!-- Notice when 0 consumers -->
@if($consumers->total() === 0)
    <div class="p-4 bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/70 rounded-2xl space-y-2.5">
        <div class="flex items-start gap-2.5">
            <span class="text-base leading-none mt-0.5">⚠️</span>
            <div class="space-y-1">
                <h5 class="text-xs font-bold text-amber-900 dark:text-amber-100">
                    No Active Consumers in this MRU
                </h5>
                <p class="text-[11px] text-amber-800 dark:text-amber-300 leading-relaxed">
                    You cannot start a billing cycle because this MRU workspace has <strong>0 registered consumers</strong>. The creation buttons below are disallowed until you add or import consumer CA numbers.
                </p>
            </div>
        </div>
        <div class="pt-2 border-t border-amber-200/60 dark:border-amber-800/60 flex flex-wrap items-center gap-2">
            <button type="button" @click="showStartBillingModal = false; showAddConsumerModal = true" class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-xs">
                <span>+</span> Add Consumer
            </button>
            <button type="button" @click="showStartBillingModal = false; showImportModal = true" class="px-3.5 py-1.5 bg-white dark:bg-slate-800 hover:bg-amber-100 dark:hover:bg-slate-700 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-700 rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-xs">
                <span>📥</span> Bulk Import CAs
            </button>
        </div>
    </div>
@endif
