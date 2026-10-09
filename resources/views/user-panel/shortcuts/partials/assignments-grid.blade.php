{{-- Shortcuts Action Grid --}}
<div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
        <div>
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Active Key Assignments</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Click any key badge below to re-assign it. Multi-key combinations (e.g. <kbd class="font-mono text-[10px] px-1 bg-slate-100 dark:bg-slate-800 rounded">Ctrl+C</kbd>) are fully supported.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 pt-2">
        <template x-for="(label, actionKey) in labels" :key="actionKey">
            <div :class="isActionInConflict(actionKey) ? 'border-amber-400 bg-amber-50/50 dark:bg-amber-950/20' : 'border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 hover:border-brand-300 dark:hover:border-slate-700'"
                 class="flex items-center justify-between p-4 rounded-2xl border transition group">
                <div class="space-y-0.5">
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                        <span x-text="label"></span>
                        <span x-show="isActionInConflict(actionKey)" class="text-[10px] font-bold text-amber-600 dark:text-amber-400">⚠️ Conflict</span>
                    </div>
                    <div class="text-[10px] text-slate-400 font-mono" x-text="'Identifier: ' + actionKey"></div>
                </div>
                <div>
                    <button type="button" 
                            @click="startRebind(actionKey)" 
                            :class="rebindingAction === actionKey ? 'bg-amber-500 text-white animate-pulse ring-2 ring-amber-400' : 'bg-white dark:bg-slate-800 hover:bg-brand-50 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100'" 
                            class="px-3.5 py-2 rounded-xl text-xs font-mono font-bold transition min-w-[100px] text-center shadow-xs flex items-center justify-center gap-1">
                        <template x-if="rebindingAction === actionKey">
                            <span class="text-[11px] font-bold text-white">Press Key...</span>
                        </template>
                        <template x-if="rebindingAction !== actionKey">
                            <span x-html="renderBadge(shortcuts[actionKey])"></span>
                        </template>
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>
