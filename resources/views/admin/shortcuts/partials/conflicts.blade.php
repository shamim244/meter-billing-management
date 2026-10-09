{{-- Conflict Warning Banner --}}
<template x-if="conflicts.length > 0">
    <div class="p-4 rounded-2xl bg-amber-950/60 border border-amber-500/40 text-amber-300 text-xs shadow-xs space-y-1">
        <div class="font-bold flex items-center gap-1.5 text-amber-200">
            <span>⚠️</span> System Key Conflict Detected
        </div>
        <template x-for="c in conflicts" :key="c.key">
            <p class="text-[11px] leading-relaxed">
                Shortcut <strong class="font-mono bg-amber-900/80 px-1.5 py-0.5 rounded text-amber-100" x-text="c.key"></strong> is assigned to multiple actions (<span class="font-semibold" x-text="c.actions.map(a => labels[a] || a).join(', ')"></span>).
            </p>
        </template>
    </div>
</template>
