            <!-- ⚡ SMART AVERAGE TUNING & SEQUENTIAL COMPOUNDING MODAL -->
            <div x-show="showTuningModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-xl w-full shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150" @click.away="showTuningModal = false">
                    <!-- Modal Header -->
                    <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/80 text-indigo-600 dark:text-cyan-400 flex items-center justify-center text-xl font-bold shadow-inner">
                                ⚡
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">Smart Average Tuning</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Sequential compounding percentage adjustments</p>
                            </div>
                        </div>
                        <button type="button" @click="showTuningModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            ✕
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-5 sm:p-6 space-y-5">
                        <!-- Compounding Simulation Display -->
                        <div class="bg-gradient-to-br from-indigo-50/70 via-slate-50 to-indigo-50/40 dark:from-indigo-950/40 dark:via-slate-900 dark:to-indigo-950/20 p-4 rounded-2xl border border-indigo-100 dark:border-indigo-900/60 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Compounded Yield Preview</span>
                                <div class="flex items-center gap-2">
                                    <label class="text-[11px] font-semibold text-slate-500">Base:</label>
                                    <input type="number" x-model.number="tuningBaseUnits" class="w-16 py-0.5 px-2 text-center text-xs font-bold font-mono rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200" />
                                    <span class="text-xs font-bold text-slate-400">kWh</span>
                                </div>
                            </div>

                            <!-- Sequential Chain Visualization -->
                            <div class="flex flex-wrap items-center gap-2 font-mono text-xs">
                                <span class="px-2.5 py-1 rounded-xl bg-slate-200/80 dark:bg-slate-800 font-bold text-slate-700 dark:text-slate-300">
                                    <span x-text="tuningBaseUnits"></span> kWh (Base)
                                </span>

                                <template x-for="(st, idx) in getCompoundedStepsDetails(tuningBaseUnits)" :key="idx">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-slate-400 font-bold">→</span>
                                        <span class="px-2 py-1 rounded-xl font-bold border flex items-center gap-1"
                                              :class="st.percent < 0 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'">
                                            <span x-text="(st.percent > 0 ? '+' : '') + st.percent + '%'"></span>
                                            <span class="text-[10px] opacity-75 font-normal" x-text="'(' + st.after + ' kWh)'"></span>
                                        </span>
                                    </div>
                                </template>
                            </div>

                            <!-- Net Result Box -->
                            <div class="pt-2 border-t border-indigo-100 dark:border-indigo-900/60 flex items-center justify-between">
                                <div>
                                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Effective Tuned Average:</span>
                                    <div class="text-xl font-black text-indigo-700 dark:text-cyan-400 font-mono">
                                        <span x-text="getCompoundedUnits(tuningBaseUnits)"></span> kWh
                                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 font-sans"
                                              x-text="'(' + (getNetTuningPercent(tuningBaseUnits) >= 0 ? '+' : '') + getNetTuningPercent(tuningBaseUnits) + '% net)'"></span>
                                    </div>
                                </div>
                                <template x-if="avgTuningSteps.length > 1">
                                    <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-cyan-300 border border-indigo-200 dark:border-indigo-700">
                                        Sequential Compounding Active
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- Add Compounding Steps -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300 block">
                                Quick Step Additions (Click to chain sequentially):
                            </label>
                            <div class="grid grid-cols-4 sm:grid-cols-8 gap-1.5">
                                <button type="button" @click="addTuningStep(-30)" class="py-1.5 px-1 bg-slate-100 dark:bg-slate-800 hover:bg-amber-100 dark:hover:bg-amber-950/70 text-slate-700 dark:text-slate-300 hover:text-amber-700 rounded-xl text-xs font-bold font-mono transition">-30%</button>
                                <button type="button" @click="addTuningStep(-20)" class="py-1.5 px-1 bg-slate-100 dark:bg-slate-800 hover:bg-amber-100 dark:hover:bg-amber-950/70 text-slate-700 dark:text-slate-300 hover:text-amber-700 rounded-xl text-xs font-bold font-mono transition">-20%</button>
                                <button type="button" @click="addTuningStep(-10)" class="py-1.5 px-1 bg-slate-100 dark:bg-slate-800 hover:bg-amber-100 dark:hover:bg-amber-950/70 text-slate-700 dark:text-slate-300 hover:text-amber-700 rounded-xl text-xs font-bold font-mono transition">-10%</button>
                                <button type="button" @click="addTuningStep(-5)" class="py-1.5 px-1 bg-slate-100 dark:bg-slate-800 hover:bg-amber-100 dark:hover:bg-amber-950/70 text-slate-700 dark:text-slate-300 hover:text-amber-700 rounded-xl text-xs font-bold font-mono transition">-5%</button>
                                <button type="button" @click="addTuningStep(5)" class="py-1.5 px-1 bg-slate-100 dark:bg-slate-800 hover:bg-emerald-100 dark:hover:bg-emerald-950/70 text-slate-700 dark:text-slate-300 hover:text-emerald-700 rounded-xl text-xs font-bold font-mono transition">+5%</button>
                                <button type="button" @click="addTuningStep(10)" class="py-1.5 px-1 bg-slate-100 dark:bg-slate-800 hover:bg-emerald-100 dark:hover:bg-emerald-950/70 text-slate-700 dark:text-slate-300 hover:text-emerald-700 rounded-xl text-xs font-bold font-mono transition">+10%</button>
                                <button type="button" @click="addTuningStep(20)" class="py-1.5 px-1 bg-slate-100 dark:bg-slate-800 hover:bg-emerald-100 dark:hover:bg-emerald-950/70 text-slate-700 dark:text-slate-300 hover:text-emerald-700 rounded-xl text-xs font-bold font-mono transition">+20%</button>
                                <button type="button" @click="addTuningStep(30)" class="py-1.5 px-1 bg-slate-100 dark:bg-slate-800 hover:bg-emerald-100 dark:hover:bg-emerald-950/70 text-slate-700 dark:text-slate-300 hover:text-emerald-700 rounded-xl text-xs font-bold font-mono transition">+30%</button>
                            </div>

                            <!-- Custom Percentage Step -->
                            <div class="flex items-center gap-2 pt-2">
                                <span class="text-xs font-medium text-slate-500">Custom Step:</span>
                                <input type="number" x-model.number="customTuningStep" placeholder="10" class="w-20 py-1 px-2.5 text-xs font-bold font-mono rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500" />
                                <span class="text-xs font-bold text-slate-400">%</span>
                                <button type="button" @click="addTuningStep(customTuningStep)" class="px-3 py-1 bg-indigo-50 dark:bg-indigo-950/80 hover:bg-indigo-100 text-indigo-700 dark:text-cyan-300 font-bold text-xs rounded-xl border border-indigo-200 dark:border-indigo-800 transition">
                                    + Add Step
                                </button>
                            </div>
                        </div>

                        <!-- Active Steps List -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Current Compounding Sequence (<span x-text="avgTuningSteps.length"></span> steps):
                                </label>
                                <button type="button" @click="clearTuning()" class="text-[11px] font-bold text-rose-600 dark:text-rose-400 hover:underline">
                                    ↺ Clear / Reset to Normal
                                </button>
                            </div>

                            <template x-if="avgTuningSteps.length === 0">
                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-xs text-slate-400 italic text-center">
                                    No percentage adjustments added. The baseline average (Normal 0%) will be used.
                                </div>
                            </template>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <template x-for="(st, idx) in avgTuningSteps" :key="idx">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-mono font-bold border"
                                          :class="st < 0 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'">
                                        <span x-text="'Step ' + (idx + 1) + ': ' + (st > 0 ? '+' : '') + st + '%'"></span>
                                        <button type="button" @click="removeTuningStep(idx)" class="hover:text-rose-600 font-sans font-black text-xs leading-none">✕</button>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <button type="button" @click="clearTuning(); showTuningModal = false; fetchData(1);" class="text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-bold">
                            Reset to Normal (0%)
                        </button>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="showTuningModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition">
                                Cancel
                            </button>
                            <button type="button" @click="applyTuningAndFetch()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-500/20 transition">
                                ⚡ Apply & Recalculate
                            </button>
                        </div>
                    </div>
                </div>
            </div>
