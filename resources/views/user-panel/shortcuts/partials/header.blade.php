{{-- Top Overview & Workflow Banner --}}
<div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl relative overflow-hidden">
    <div class="absolute top-0 right-0 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-brand-50 dark:bg-brand-950/80 text-brand-700 dark:text-cyan-300 border border-brand-200/60 dark:border-brand-800/60">
                    Workflow Optimization
                </span>
                <span class="text-xs text-slate-400">•</span>
                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Single-Key & Multi-Key Keybindings</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>⌨️</span> Review Keyboard Shortcuts
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
                Audit bills at lightspeed with 100% hands-on-keyboard control. Assign single keys (<kbd class="font-mono text-[10px] px-1 bg-slate-100 dark:bg-slate-800 rounded">C</kbd>, <kbd class="font-mono text-[10px] px-1 bg-slate-100 dark:bg-slate-800 rounded">R</kbd>) or multi-key combinations (<kbd class="font-mono text-[10px] px-1 bg-slate-100 dark:bg-slate-800 rounded">Ctrl+C</kbd>, <kbd class="font-mono text-[10px] px-1 bg-slate-100 dark:bg-slate-800 rounded">Shift+M</kbd>).
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-2">
            <button type="button" @click="resetToDefaults()" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition text-center border border-slate-200 dark:border-slate-700">
                🔄 Reset Defaults
            </button>
            <button type="button" @click="saveShortcuts()" :disabled="isSaving" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-500/20 transition flex items-center justify-center gap-1.5 text-center">
                <span x-show="!isSaving">💾 Save Keybindings</span>
                <span x-show="isSaving" x-cloak>⏳ Saving...</span>
            </button>
        </div>
    </div>

    {{-- Quick Presets --}}
    <div class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-800/80 flex flex-wrap items-center gap-3">
        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Quick Presets:</span>
        <button type="button" @click="applyPreset('single')" class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-brand-50 hover:text-brand-700 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-medium border border-slate-200 dark:border-slate-700 transition">
            ⚡ Fast Single-Key (c, r, 2, 3)
        </button>
        <button type="button" @click="applyPreset('combo')" class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-brand-50 hover:text-brand-700 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-medium border border-slate-200 dark:border-slate-700 transition">
            🛡️ Multi-Key Combos (Ctrl+C, Alt+R, Shift+M)
        </button>
    </div>
</div>
