<x-admin-layout>
    <x-slot name="header">
        System Keyboard Shortcuts & Defaults
    </x-slot>

    <div x-data="{
        shortcuts: {{ Js::from($systemShortcuts) }},
        labels: {{ Js::from($labels) }},
        rebindingAction: null,
        rebindKey(actionKey) {
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
                    <h1 class="text-2xl font-black text-white tracking-tight">Platform Default Keybindings</h1>
                    <p class="text-xs text-slate-400 mt-1 max-w-2xl leading-relaxed">
                        These keyboard shortcuts serve as the system-wide baseline for all operators reviewing consumer cards and entering working readings on the Dashboard.
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

        <!-- Shortcut Configuration Card Form -->
        <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-white">System Keybinding Assignments</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Click any action's key badge to listen and assign a physical keyboard key.</p>
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

            <!-- Rebinding Banner Modal Alert (Live Key Listening) -->
            <div x-show="rebindingAction" class="p-4 rounded-2xl bg-indigo-950/80 border-2 border-indigo-500 text-center animate-pulse" x-cloak>
                <div class="text-xs font-bold uppercase tracking-wider text-indigo-300">Listening for keypress...</div>
                <div class="text-base font-black text-white mt-1">
                    Press any key on your keyboard to assign to: <span class="text-cyan-300" x-text="labels[rebindingAction] || rebindingAction"></span>
                </div>
                <button type="button" @click="rebindingAction = null" class="mt-2 px-3 py-1 bg-slate-800 text-slate-300 hover:text-white text-xs font-semibold rounded-lg">
                    Cancel (Escape)
                </button>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('admin.shortcuts.update') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($systemShortcuts as $key => $binding)
                        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/90 hover:border-slate-700 transition flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-white">
                                        {{ $labels[$key] ?? ucwords(str_replace('_', ' ', $key)) }}
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
                                            @click="rebindKey('{{ $key }}')"
                                            class="px-3 py-1.5 rounded-xl font-mono font-extrabold text-xs bg-indigo-500/15 hover:bg-indigo-500/30 text-indigo-300 border border-indigo-500/40 hover:border-indigo-400 shadow-sm transition active:scale-95 flex items-center gap-1.5"
                                            :class="rebindingAction === '{{ $key }}' ? 'ring-2 ring-indigo-400 animate-pulse bg-indigo-500/40' : ''">
                                        <span x-text="shortcuts['{{ $key }}'] ? shortcuts['{{ $key }}'].toUpperCase() : 'NONE'"></span>
                                        <span class="text-[9px] text-slate-400">✎</span>
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
