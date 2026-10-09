        <!-- 5. QUICK GPS TAGGING MODAL -->
        <div x-show="modals.quickGps"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="modals.quickGps = false"
                 class="bg-white dark:bg-slate-900 rounded-3xl max-w-sm w-full p-5 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span>📍</span> Tag GPS Coordinates
                    </h3>
                    <button @click="modals.quickGps = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                </div>
                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-slate-500 dark:text-slate-400">Tagging CA:</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white ml-1" x-text="quickGpsForm.ca_number"></span>
                    </div>
                    <button type="button"
                            @click="captureGpsLocation('quick')"
                            :disabled="gpsLoading"
                            class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold flex items-center justify-center gap-2 shadow-md shadow-rose-500/20 disabled:opacity-50">
                        <template x-if="gpsLoading">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>Locking Hardware GPS...</span>
                            </span>
                        </template>
                        <template x-if="!gpsLoading">
                            <span>📍 Capture My Current Position</span>
                        </template>
                    </button>
                    <template x-if="quickGpsForm.location_accuracy">
                        <div class="p-2.5 rounded-xl border bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 text-center space-y-1">
                            <div class="font-mono text-slate-800 dark:text-slate-200 text-xs">
                                <span x-text="quickGpsForm.latitude"></span>, <span x-text="quickGpsForm.longitude"></span>
                            </div>
                            <div class="text-[10px] font-bold"
                                 :class="quickGpsForm.location_accuracy <= 10 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'"
                                 x-text="'Precision: ±' + Math.round(quickGpsForm.location_accuracy) + 'm (target < 10m)'"></div>
                            <a :href="'https://www.google.com/maps?q=' + quickGpsForm.latitude + ',' + quickGpsForm.longitude"
                               target="_blank"
                               class="text-[11px] font-bold text-blue-600 dark:text-cyan-400 underline block pt-0.5">
                                🗺️ Verify in Google Maps
                            </a>
                        </div>
                    </template>
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="modals.quickGps = false" class="px-3 py-1.5 rounded-xl text-slate-500 font-bold">
                            Cancel
                        </button>
                        <button type="button" @click="submitQuickGps()" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold">
                            💾 Save to Consumer
                        </button>
                    </div>
                </div>
            </div>
        </div>