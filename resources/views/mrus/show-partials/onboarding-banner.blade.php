<!-- No Consumers Onboarding Banner -->
@if($consumers->total() === 0)
    <div class="p-5 rounded-3xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/80 text-amber-900 dark:text-amber-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-2xl bg-amber-100 dark:bg-amber-900/60 text-amber-600 dark:text-amber-300 flex items-center justify-center shrink-0 text-xl font-bold">
                ⚠️
            </div>
            <div>
                <h4 class="text-sm font-bold text-amber-900 dark:text-amber-100">
                    No Consumers in this MRU Workspace Yet
                </h4>
                <p class="text-xs text-amber-800/90 dark:text-amber-300/90 mt-0.5 leading-relaxed max-w-2xl">
                    Before creating billing cycles or downloading bills, you must register consumer CA numbers or bulk import them into this MRU workspace.
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <button type="button" @click="showAddConsumerModal = true" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                <span>+</span> Add Consumer
            </button>
            <button type="button" @click="showImportModal = true" class="px-4 py-2 bg-white dark:bg-slate-800 hover:bg-amber-100 dark:hover:bg-slate-700 text-amber-900 dark:text-amber-200 rounded-xl text-xs font-bold border border-amber-300 dark:border-amber-700 transition flex items-center gap-1.5">
                <span>📥</span> Bulk Paste CAs
            </button>
        </div>
    </div>
@endif
