<x-user-panel-layout>
    <x-slot name="header">
        Keyboard Shortcuts & Keybinding Combos
    </x-slot>

    <div x-data="{
        shortcuts: {{ Js::from($shortcuts) }},
        labels: {{ Js::from($labels) }},
        rebindingAction: null,
        rebindDisplay: '',
        rebindSession: null,
        isSaving: false,
        saveMessage: null,
        saveStatus: 'success',

        get conflicts() {
            if (!window.KeyboardShortcuts) return [];
            const map = {};
            const conflictingActions = [];
            for (const [action, key] of Object.entries(this.shortcuts)) {
                if (!key) continue;
                const norm = window.KeyboardShortcuts.normalize(key);
                if (map[norm]) {
                    conflictingActions.push({ key: key, actions: [map[norm], action] });
                } else {
                    map[norm] = action;
                }
            }
            return conflictingActions;
        },

        isActionInConflict(actionKey) {
            const currentKey = this.shortcuts[actionKey];
            if (!currentKey || !window.KeyboardShortcuts) return false;
            const norm = window.KeyboardShortcuts.normalize(currentKey);
            let count = 0;
            for (const [act, k] of Object.entries(this.shortcuts)) {
                if (k && window.KeyboardShortcuts.normalize(k) === norm) {
                    count++;
                }
            }
            return count > 1;
        },

        renderBadge(shortcut) {
            if (window.KeyboardShortcuts) {
                return window.KeyboardShortcuts.renderBadgesHtml(shortcut);
            }
            return shortcut || 'Unset';
        },

        startRebind(actionKey) {
            if (this.rebindSession) {
                this.rebindSession.cancel();
            }

            this.rebindingAction = actionKey;
            this.rebindDisplay = 'Press any key or combo (e.g. Ctrl+C)...';

            if (window.KeyboardShortcuts) {
                this.rebindSession = window.KeyboardShortcuts.startRebindSession({
                    onUpdate: (data) => {
                        this.rebindDisplay = data.display;
                    },
                    onComplete: (combo) => {
                        this.shortcuts[actionKey] = combo;
                        this.rebindingAction = null;
                        this.rebindSession = null;
                    },
                    onCancel: () => {
                        this.rebindingAction = null;
                        this.rebindSession = null;
                    }
                });
            }
        },

        cancelRebind() {
            if (this.rebindSession) {
                this.rebindSession.cancel();
            }
            this.rebindingAction = null;
            this.rebindSession = null;
        },

        applyPreset(preset) {
            if (preset === 'single') {
                this.shortcuts = {
                    copy_ca: 'c',
                    focus_reading: 'r',
                    auto_fill_reading: 'a',
                    submit_ok: 'Enter',
                    mark_doubt: '2',
                    mark_critical: '3',
                    next_card: 'ArrowDown',
                    prev_card: 'ArrowUp',
                    open_remark: 'm',
                    exit_box: 'Escape'
                };
            } else if (preset === 'combo') {
                this.shortcuts = {
                    copy_ca: 'Ctrl+C',
                    focus_reading: 'Alt+R',
                    auto_fill_reading: 'Alt+A',
                    submit_ok: 'Ctrl+Enter',
                    mark_doubt: 'Alt+2',
                    mark_critical: 'Alt+3',
                    next_card: 'Alt+ArrowDown',
                    prev_card: 'Alt+ArrowUp',
                    open_remark: 'Shift+M',
                    exit_box: 'Escape'
                };
            }
        },

        saveShortcuts() {
            this.isSaving = true;
            this.saveMessage = null;

            fetch('{{ route('user.shortcuts.save', [], false) }}', {
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
                    this.saveStatus = 'success';
                    this.saveMessage = '✅ Custom keyboard shortcuts saved successfully!';
                    setTimeout(() => this.saveMessage = null, 4500);
                } else {
                    this.saveStatus = 'error';
                    this.saveMessage = '❌ ' + (data.message || 'Validation error');
                }
            })
            .catch(err => {
                this.isSaving = false;
                this.saveStatus = 'error';
                this.saveMessage = '❌ Failed to save shortcuts: ' + err.message;
            });
        },

        resetToDefaults() {
            if (!confirm('Reset all keyboard shortcuts back to system defaults?')) return;
            this.isSaving = true;
            this.saveMessage = null;

            fetch('{{ route('user.shortcuts.reset', [], false) }}', {
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
                    this.saveStatus = 'success';
                    this.saveMessage = '🔄 Restored to system default shortcuts!';
                    setTimeout(() => this.saveMessage = null, 4500);
                }
            })
            .catch(err => {
                this.isSaving = false;
                this.saveStatus = 'error';
                this.saveMessage = '❌ Failed to reset shortcuts.';
            });
        }
    }" class="space-y-8">

        <!-- Top Overview & Workflow Banner -->
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

            <!-- Quick Presets -->
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

        <!-- Success / Error Notice -->
        <template x-if="saveMessage">
            <div :class="saveStatus === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-200 dark:border-emerald-500/30 text-emerald-800 dark:text-emerald-300' : 'bg-rose-50 dark:bg-rose-950/60 border-rose-200 dark:border-rose-500/30 text-rose-800 dark:text-rose-300'"
                 class="p-4 rounded-2xl border text-xs font-bold shadow-xs animate-in fade-in duration-200" 
                 x-text="saveMessage"></div>
        </template>

        <!-- Conflict Warning Banner -->
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

        <!-- Live Rebinding Listening Banner -->
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

        <!-- Shortcuts Action Grid -->
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

        <!-- Input Field Isolation & Safety Matrix -->
        <div class="p-5 bg-blue-50/70 dark:bg-blue-950/40 rounded-3xl border border-blue-100 dark:border-blue-900/60 text-xs text-blue-900 dark:text-cyan-200 leading-relaxed flex items-start gap-3">
            <span class="text-xl shrink-0">💡</span>
            <div class="space-y-1">
                <strong class="block text-blue-950 dark:text-white">Smart Input Field Safety</strong>
                <p>
                    When typing inside a Working Reading or Remark input box, single-character navigation keys (<kbd class="font-mono text-[10px] px-1 bg-white/70 dark:bg-slate-800 rounded">c</kbd>, <kbd class="font-mono text-[10px] px-1 bg-white/70 dark:bg-slate-800 rounded">r</kbd>, <kbd class="font-mono text-[10px] px-1 bg-white/70 dark:bg-slate-800 rounded">2</kbd>, <kbd class="font-mono text-[10px] px-1 bg-white/70 dark:bg-slate-800 rounded">3</kbd>) are safely isolated so your data is never altered accidentally.
                </p>
                <p class="text-[11px] text-blue-700 dark:text-cyan-300">
                    Press <kbd class="font-mono text-[10px] px-1 bg-white/70 dark:bg-slate-800 rounded font-bold">Escape</kbd> anytime to exit the text field and re-enable hands-on-keyboard auditing.
                </p>
            </div>
        </div>
    </div>
</x-user-panel-layout>
