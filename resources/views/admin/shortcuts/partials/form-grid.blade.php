{{-- Shortcut Configuration Card Form --}}
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
