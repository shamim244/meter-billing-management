{{-- Success / Error Notice --}}
<template x-if="saveMessage">
    <div :class="saveStatus === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-200 dark:border-emerald-500/30 text-emerald-800 dark:text-emerald-300' : 'bg-rose-50 dark:bg-rose-950/60 border-rose-200 dark:border-rose-500/30 text-rose-800 dark:text-rose-300'"
         class="p-4 rounded-2xl border text-xs font-bold shadow-xs animate-in fade-in duration-200" 
         x-text="saveMessage"></div>
</template>

{{-- Conflict Warning Banner --}}
<template x-if="conflicts.length > 0">
    <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/60 border border-amber-300 dark:border-amber-700/60 text-amber-900 dark:text-amber-300 text-xs shadow-xs space-y-1">
        <div class="font-bold flex items-center gap-1.5 text-amber-800 dark:text-amber-200">
            <span>⚠️</span> Key Conflict Detected
        </div>
        <template x-for="c in conflicts" :key="c.key">
            <p class="text-[11px] leading-relaxed">
                Shortcut <strong class="font-mono bg-amber-100 dark:bg-amber-900/80 px-1.5 py-0.5 rounded text-amber-950 dark:text-amber-100" x-text="c.key"></strong> is assigned to multiple actions (<span class="font-semibold" x-text="c.actions.map(a => labels[a] || a).join(', ')"></span>). Please assign unique keys before continuing.
            </p>
        </template>
    </div>
</template>
