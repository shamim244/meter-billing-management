<x-admin-layout>
    <x-slot name="header">
        System Keyboard Shortcuts & Defaults
    </x-slot>

    <div x-data="{
        shortcuts: {{ Js::from($systemShortcuts) }},
        labels: {{ Js::from($labels) }},
        rebindingAction: null,
        rebindDisplay: '',
        rebindSession: null,

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
        }
    }" class="space-y-8">

        <!-- Top Overview & Info Card -->
        <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-500/15 text-indigo-400 border border-indigo-500/30">
                            Global Configuration
                        </span>
                        <span class="text-xs text-slate-500">•</span>
                        <span class="text-xs text-slate-400 font-medium">NBPDCL Billing Engine</span>
                    </div>
                    <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                        <span>⌨️</span> Platform Default Keybindings
                    </h1>
                    <p class="text-xs text-slate-400 mt-1 max-w-2xl leading-relaxed">
                        These keyboard shortcuts serve as the platform baseline for all operators reviewing consumer cards on the Dashboard. Single keys (<kbd class="font-mono text-[10px] px-1 bg-slate-800 rounded">C</kbd>, <kbd class="font-mono text-[10px] px-1 bg-slate-800 rounded">R</kbd>) and multi-key combos (<kbd class="font-mono text-[10px] px-1 bg-slate-800 rounded">Ctrl+C</kbd>, <kbd class="font-mono text-[10px] px-1 bg-slate-800 rounded">Shift+M</kbd>) are fully supported.
                    </p>
                </div>

                <!-- Adoption Stats -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Users</div>
                        <div class="text-xl font-black text-white font-mono mt-0.5">{{ $stats['total_users'] }}</div>
                    </div>
                    <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">On Defaults</div>
                        <div class="text-xl font-black text-emerald-400 font-mono mt-0.5">{{ $stats['default_users'] }}</div>
                    </div>
                    <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Customized</div>
                        <div class="text-xl font-black text-cyan-400 font-mono mt-0.5">{{ $stats['customized_users'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conflict Warning Banner -->
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

        <!-- Rebinding Banner Modal Alert (Live Key Listening) -->
        <div x-show="rebindingAction" class="p-6 rounded-3xl bg-indigo-950/90 border-2 border-indigo-500 text-center shadow-xl transition-all" x-cloak>
            <div class="flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-wider text-indigo-300">
                <span class="w-2 h-2 rounded-full bg-indigo-400 animate-ping"></span>
                Listening for System Keypress
            </div>
            <div class="text-lg font-black text-white mt-1.5">
                Assign key for: <span class="text-cyan-300 underline decoration-2 underline-offset-4" x-text="labels[rebindingAction] || rebindingAction"></span>
            </div>
            <div class="mt-3 inline-block px-5 py-2 rounded-2xl bg-slate-900 border border-indigo-700 shadow-sm">
                <span class="text-sm font-mono font-black text-cyan-300" x-text="rebindDisplay"></span>
            </div>
            <div class="mt-4 flex items-center justify-center gap-2">
                <button type="button" @click="cancelRebind()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold rounded-xl transition">
                    Cancel (Escape)
                </button>
            </div>
        </div>

        <!-- Shortcut Configuration Card Form -->
        <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-white">System Keybinding Assignments</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Click any action's key badge to assign single keys or multi-key combinations.</p>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                    <!-- Reset to Factory -->
                    <form method="POST" action="{{ route('admin.shortcuts.reset-factory') }}" onsubmit="return confirm('Restore all system defaults to factory configuration?');" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" class="w-full sm:w-auto px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 text-xs font-semibold transition text-center">
                            🔄 Factory Reset
                        </button>
                    </form>

                    <!-- Force Reset All Users -->
                    <form method="POST" action="{{ route('admin.shortcuts.reset-all-users') }}" onsubmit="return confirm('Reset all billing agent & operator custom overrides so every user strictly inherits these system defaults?');" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" class="w-full sm:w-auto px-3.5 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 text-xs font-bold transition text-center">
                            ⚡ Reset All Users to Defaults
                        </button>
                    </form>
                </div>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('admin.shortcuts.update') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($systemShortcuts as $key => $binding)
                        <div class="p-4 rounded-2xl bg-slate-900/60 border transition flex flex-col justify-between space-y-3"
                             :class="isActionInConflict('{{ $key }}') ? 'border-amber-500/60 bg-amber-950/20' : 'border-slate-800/90 hover:border-slate-700'">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-white flex items-center gap-1.5">
                                        <span>{{ $labels[$key] ?? ucwords(str_replace('_', ' ', $key)) }}</span>
                                        <span x-show="isActionInConflict('{{ $key }}')" class="text-[10px] font-bold text-amber-400">⚠️</span>
                                    </span>
                                    <span class="text-[10px] font-mono text-slate-500 font-bold uppercase">
                                        {{ $key }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1">
                                    @if($key === 'copy_ca')
                                        Copies the 11-digit consumer CA number to OS clipboard.
                                    @elseif($key === 'focus_reading')
                                        Focuses and highlights Working Reading input for direct typing.
                                    @elseif($key === 'auto_fill_reading')
                                        Calculates Prev + Avg units enforcing &ge; PDF reading.
                                    @elseif($key === 'submit_ok')
                                        Saves ledger reading, marks status as Submitted and advances.
                                    @elseif($key === 'mark_doubt')
                                        Sets consumer review status to ⚠️ Doubt for follow-up.
                                    @elseif($key === 'mark_critical')
                                        Sets consumer review status to ❌ Critical (Meter Burnt/Stopped).
                                    @elseif($key === 'next_card')
                                        Slides carousel forward to the next consumer card.
                                    @elseif($key === 'prev_card')
                                        Slides carousel backward to the previous consumer card.
                                    @elseif($key === 'open_remark')
                                        Focuses observation notes textarea field.
                                    @elseif($key === 'exit_box')
                                        Unfocuses input field and re-enables navigation.
                                    @else
                                        Action trigger keybinding.
                                    @endif
                                </p>
                            </div>

                            <div class="flex items-center justify-between pt-2 border-t border-slate-800/80">
                                <span class="text-[11px] text-slate-500 font-medium">Assigned Key:</span>
                                
                                <div class="flex items-center gap-2">
                                    <input type="hidden" :name="'shortcuts[' + '{{ $key }}' + ']'" :value="shortcuts['{{ $key }}']">
                                    
                                    <button type="button" 
                                            @click="startRebind('{{ $key }}')"
                                            class="px-3 py-1.5 rounded-xl font-mono text-xs transition active:scale-95 flex items-center gap-1.5 shadow-xs"
                                            :class="rebindingAction === '{{ $key }}' ? 'ring-2 ring-indigo-400 animate-pulse bg-indigo-500 text-white' : 'bg-slate-900 hover:bg-slate-800 text-cyan-300 border border-slate-700'">
                                        <template x-if="rebindingAction === '{{ $key }}'">
                                            <span class="font-bold text-white">Press Key...</span>
                                        </template>
                                        <template x-if="rebindingAction !== '{{ $key }}'">
                                            <span x-html="renderBadge(shortcuts['{{ $key }}'])"></span>
                                        </template>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pt-4 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 border-t border-slate-800">
                    <a href="{{ route('admin.dashboard') }}" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-semibold transition text-center">
                        Cancel
                    </a>

                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-2 active:scale-95">
                        <span>💾</span>
                        <span>Save System Defaults</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-admin-layout>
