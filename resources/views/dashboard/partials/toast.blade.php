            <!-- FLOATING TOAST NOTIFICATION WITH UNDO (RIGHT-ALIGNED & UNOBSTRUCTED) -->
            <div x-show="toast.show" 
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 translate-y-6 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-6 scale-95"
                 x-cloak
                 class="fixed bottom-6 right-4 sm:right-6 z-[70] w-auto max-w-md bg-slate-900/95 dark:bg-slate-800/95 text-white backdrop-blur-md px-5 py-3.5 rounded-2xl shadow-2xl border border-slate-700/80 flex items-center justify-between gap-5 ring-1 ring-white/10 pointer-events-auto">
                <div class="flex items-center gap-3">
                    <span class="text-xl" x-text="toast.icon"></span>
                    <div>
                        <div class="text-xs font-bold text-white tracking-wide" x-text="toast.message"></div>
                        <div class="text-[10px] text-slate-400 font-medium">Auto-saved to database</div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <template x-if="toast.undoData">
                        <button type="button" 
                                @click.stop="undoLastAction()" 
                                class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-lg shadow-blue-600/30 cursor-pointer group">
                            <svg class="w-3.5 h-3.5 group-hover:-rotate-45 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                            <span>Undo</span>
                            <span class="hidden sm:inline text-[10px] text-blue-200 font-mono bg-blue-700/60 px-1 py-0.2 rounded ml-0.5">Ctrl+Z</span>
                        </button>
                    </template>
                    <button type="button" @click="toast.show = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition cursor-pointer">
                        ✕
                    </button>
                </div>
            </div>
