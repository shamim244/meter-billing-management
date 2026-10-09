{{-- Live Rebinding Listening Banner --}}
<div x-show="rebindingAction" class="p-6 rounded-3xl bg-brand-50 dark:bg-brand-950/90 border-2 border-brand-500 dark:border-cyan-400 text-center shadow-xl transition-all" x-cloak>
    <div class="flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-wider text-brand-700 dark:text-cyan-300">
        <span class="w-2 h-2 rounded-full bg-brand-500 animate-ping"></span>
        Listening for Keyboard Input
    </div>
    <div class="text-lg font-black text-slate-900 dark:text-white mt-1.5">
        Assign key for: <span class="text-brand-600 dark:text-cyan-400 underline decoration-2 underline-offset-4" x-text="labels[rebindingAction] || rebindingAction"></span>
    </div>
    <div class="mt-3 inline-block px-5 py-2 rounded-2xl bg-white dark:bg-slate-900 border border-brand-300 dark:border-cyan-700 shadow-sm">
        <span class="text-sm font-mono font-black text-brand-700 dark:text-cyan-300" x-text="rebindDisplay"></span>
    </div>
    <div class="mt-4 flex items-center justify-center gap-2">
        <button type="button" @click="cancelRebind()" class="px-4 py-2 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl transition">
            Cancel (Escape)
        </button>
    </div>
</div>
