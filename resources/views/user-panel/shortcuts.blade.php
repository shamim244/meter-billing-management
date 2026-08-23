<x-user-panel-layout>
    <x-slot name="header">
        Keyboard Shortcuts Customizer
    </x-slot>

    <div x-data="{
        shortcuts: {{ Js::from($shortcuts) }},
        labels: {{ Js::from($labels) }},
        rebindingAction: null,
        isSaving: false,
        saveMessage: null,

        startRebind(actionKey) {
            this.rebindingAction = actionKey;
            
            const handleKey = (e) => {
                e.preventDefault();
                e.stopPropagation();

                let keyName = e.key;
                if (keyName === ' ') keyName = 'Space';
                
                this.shortcuts[actionKey] = keyName;
                this.rebindingAction = null;
                window.removeEventListener('keydown', handleKey, { capture: true });
            };

            window.addEventListener('keydown', handleKey, { capture: true, once: true });
        },

        saveShortcuts() {
            this.isSaving = true;
            this.saveMessage = null;

            fetch('{{ route('user.shortcuts.save') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ shortcuts: this.shortcuts })
            })
            .then(r => r.json())
            .then(data => {
                this.isSaving = false;
                if (data.success) {
                    if (data.shortcuts) this.shortcuts = data.shortcuts;
                    this.saveMessage = '✅ Custom shortcuts saved successfully!';
                    setTimeout(() => this.saveMessage = null, 4000);
                }
            })
            .catch(err => {
                this.isSaving = false;
                this.saveMessage = '❌ Failed to save shortcuts: ' + err.message;
            });
        },

        resetToDefaults() {
            if (!confirm('Reset all keyboard shortcuts back to system defaults?')) return;
            this.isSaving = true;

            fetch('{{ route('user.shortcuts.reset') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(r => r.json())
            .then(data => {
                this.isSaving = false;
                if (data.success) {
                    if (data.shortcuts) this.shortcuts = data.shortcuts;
                    this.saveMessage = '🔄 Restored to system defaults!';
                    setTimeout(() => this.saveMessage = null, 4000);
                }
            })
            .catch(err => {
                this.isSaving = false;
                this.saveMessage = '❌ Failed to reset shortcuts.';
            });
        }
    }" class="space-y-8">

        <!-- Top Overview Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-brand-50 dark:bg-brand-950/80 text-brand-700 dark:text-cyan-300 border border-brand-200/60 dark:border-brand-800/60">
                            Workflow Optimization
                        </span>
                        <span class="text-xs text-slate-400">•</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Single-Key Fast Card Review</span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Review Keyboard Shortcuts</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
                        Customize any key to fit your keyboard habits. During card review, these hotkeys allow 100% hands-on-keyboard auditing without touching the mouse.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-2">
                    <button type="button" @click="resetToDefaults()" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition text-center">
                        🔄 Reset Defaults
                    </button>
                    <button type="button" @click="saveShortcuts()" :disabled="isSaving" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-500/20 transition flex items-center justify-center gap-1.5 text-center">
                        <span x-show="!isSaving">💾 Save Shortcuts</span>
                        <span x-show="isSaving" x-cloak>⏳ Saving...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Success/Alert Notice -->
        <template x-if="saveMessage">
            <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-500/30 text-emerald-800 dark:text-emerald-300 text-xs font-bold shadow-xs animate-in fade-in duration-200" x-text="saveMessage"></div>
        </template>

        <!-- Live Rebinding Listening Banner -->
        <div x-show="rebindingAction" class="p-6 rounded-3xl bg-brand-50 dark:bg-brand-950/80 border-2 border-brand-500 dark:border-cyan-400 text-center animate-pulse shadow-lg" x-cloak>
            <div class="text-xs font-bold uppercase tracking-wider text-brand-700 dark:text-cyan-300">Listening for keyboard input...</div>
            <div class="text-lg font-black text-slate-900 dark:text-white mt-1">
                Press any key on your keyboard to assign to: <span class="text-brand-600 dark:text-cyan-400" x-text="labels[rebindingAction] || rebindingAction"></span>
            </div>
            <button type="button" @click="rebindingAction = null" class="mt-3 px-4 py-1.5 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-700 text-xs font-semibold rounded-xl transition">
                Cancel (Esc)
            </button>
        </div>

        <!-- Shortcuts Action Grid -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Active Key Assignments</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Click any key badge below to re-assign it to a new physical key.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 pt-2">
                <template x-for="(label, actionKey) in labels" :key="actionKey">
                    <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800 hover:border-brand-300 dark:hover:border-slate-700 transition group">
                        <div>
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200" x-text="label"></div>
                            <div class="text-[10px] text-slate-400 font-mono mt-0.5" x-text="'Action Key: ' + actionKey"></div>
                        </div>
                        <div>
                            <button type="button" 
                                     @click="startRebind(actionKey)" 
                                     :class="rebindingAction === actionKey ? 'bg-amber-500 text-white animate-pulse ring-2 ring-amber-400' : 'bg-white dark:bg-slate-800 text-brand-600 dark:text-cyan-300 hover:bg-brand-50 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700'" 
                                     class="px-4 py-2 rounded-xl text-xs font-mono font-black transition min-w-[85px] text-center shadow-xs">
                                <span x-text="rebindingAction === actionKey ? 'Press Key...' : (shortcuts[actionKey] || 'Unset')"></span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Working Mode Exit Tip -->
        <div class="p-5 bg-blue-50/60 dark:bg-blue-950/40 rounded-3xl border border-blue-100 dark:border-blue-900/60 text-xs text-blue-800 dark:text-cyan-300 leading-relaxed flex items-start gap-3">
            <span class="text-xl shrink-0">💡</span>
            <div>
                <strong class="block mb-0.5">Input Box Focus & Exit Guide</strong>
                Press <strong>R</strong> to edit Working Reading or <strong>M</strong> to edit Remark. While typing, press <strong>Escape (Esc)</strong> anytime to exit the box and return to 1-key navigation mode. Press <strong>Ctrl + Enter</strong> in Remark to save and exit in one stroke.
            </div>
        </div>
    </div>
</x-user-panel-layout>
