            <!-- MODAL 5: Interactive Keyboard Shortcuts Customizer -->
            <div x-show="showShortcutsModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="if(!rebindingAction) showShortcutsModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <span class="text-xl">⌨️</span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Keyboard Shortcuts & Combos</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Supports single keys & multi-key combinations (e.g. Ctrl+C)</p>
                            </div>
                        </div>
                        <button @click="showShortcutsModal = false" :disabled="rebindingAction !== null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
                    </div>

                    <!-- Live Rebinding Listening Banner inside Modal -->
                    <div x-show="rebindingAction" class="p-4 bg-brand-50 dark:bg-brand-950/90 border-b border-brand-200 dark:border-cyan-800 text-center shrink-0" x-cloak>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-brand-700 dark:text-cyan-300 flex items-center justify-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-ping"></span>
                            Listening for Input
                        </div>
                        <div class="text-xs font-black text-slate-900 dark:text-white mt-0.5">
                            Assigning: <span class="text-brand-600 dark:text-cyan-400" x-text="shortcutLabels[rebindingAction] || rebindingAction"></span>
                        </div>
                        <div class="mt-1 text-xs font-mono font-bold text-brand-700 dark:text-cyan-300" x-text="rebindDisplay"></div>
                    </div>

                    <!-- Shortcut Action Rows -->
                    <div class="p-4 sm:p-6 space-y-3 overflow-y-auto">
                        <div class="p-3 bg-blue-50/60 dark:bg-blue-950/40 rounded-2xl border border-blue-100 dark:border-blue-900/60 text-[11px] text-blue-800 dark:text-cyan-300 leading-relaxed flex items-start gap-2">
                            <span class="text-sm">💡</span>
                            <span><strong>Review Speed Tip:</strong> Single-key and multi-key combos are supported. Press <strong>Escape (Esc)</strong> anytime to exit input boxes. Press <strong>?</strong> to open this shortcuts sheet anytime.</span>
                        </div>

                        <template x-for="(label, actionKey) in shortcutLabels" :key="actionKey">
                            <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800/80">
                                <div>
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200" x-text="label"></div>
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5" x-text="'action: ' + actionKey"></div>
                                </div>
                                <div>
                                    <button type="button" 
                                             @click="startRebind(actionKey)" 
                                             :class="rebindingAction === actionKey ? 'bg-amber-500 text-white animate-pulse ring-2 ring-amber-400' : 'bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-100 hover:bg-slate-300 dark:hover:bg-slate-600 border border-slate-300 dark:border-slate-600'" 
                                             class="px-3.5 py-1.5 rounded-xl text-xs font-mono font-bold transition min-w-[80px] text-center shadow-xs flex items-center justify-center">
                                        <template x-if="rebindingAction === actionKey">
                                            <span class="text-[10px] font-bold text-white">Press Key...</span>
                                        </template>
                                        <template x-if="rebindingAction !== actionKey">
                                            <span x-html="renderShortcutBadge(shortcuts[actionKey])"></span>
                                        </template>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <button type="button" @click="resetToDefaults()" class="text-xs text-rose-600 dark:text-rose-400 hover:underline font-bold text-left">
                            🔄 Reset Defaults
                        </button>
                        <div class="flex flex-col-reverse sm:flex-row items-center gap-2 w-full sm:w-auto">
                            <button type="button" @click="showShortcutsModal = false" class="w-full sm:w-auto px-4 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition text-center">
                                Close
                            </button>
                            <button type="button" @click="saveCustomShortcuts()" class="w-full sm:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 transition text-center">
                                💾 Save Shortcuts
                            </button>
                        </div>
                    </div>
                </div>
            </div>
