<!-- Physical Meter Inspection History & Management Modal -->
<div x-show="showHistoryModal" 
     x-cloak 
     id="inspection-history-modal"
     class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div @click.away="closeHistoryModal()" 
         class="bg-slate-900 border border-slate-800 rounded-3xl max-w-2xl w-full p-6 space-y-4 shadow-2xl relative text-left">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <span class="text-xl p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">📜</span>
                <div>
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        Physical Visit History & Audit
                        <span class="text-xs font-mono px-2 py-0.5 rounded-full bg-slate-800 text-slate-300" x-text="inspectionsList.length + ' visits'"></span>
                    </h3>
                    <p class="text-[11px] text-slate-400 font-mono" x-text="(selectedConsumer?.consumer_name || '') + ' • CA: ' + (selectedConsumer?.ca_number || '')"></p>
                </div>
            </div>
            <button type="button" @click="closeHistoryModal()" class="text-slate-400 hover:text-white p-1 rounded-lg">✕</button>
        </div>

        <!-- Management Action Bar -->
        <div class="flex flex-wrap items-center justify-between gap-2 p-2.5 rounded-2xl bg-slate-950 border border-slate-800 text-xs">
            <div class="flex items-center gap-2">
                <label class="flex items-center gap-1.5 text-slate-400 font-semibold cursor-pointer">
                    <input type="checkbox" @change="toggleSelectAllInspections($event)" :checked="isAllInspectionsSelected()" class="rounded border-slate-700 bg-slate-800 text-brand-600 focus:ring-0">
                    <span>Select All</span>
                </label>
                <template x-if="selectedInspectionIds.length > 0">
                    <button type="button" @click="bulkDeleteInspections()" 
                            class="px-2.5 py-1 rounded-lg bg-rose-950/60 hover:bg-rose-900 text-rose-300 border border-rose-800/60 font-bold transition flex items-center gap-1 cursor-pointer">
                        <span>🗑️</span> Delete Selected (<span x-text="selectedInspectionIds.length"></span>)
                    </button>
                </template>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="clearAllInspections()" 
                        class="px-2.5 py-1 rounded-lg bg-amber-950/40 hover:bg-amber-900/60 text-amber-300 border border-amber-800/40 font-bold transition cursor-pointer">
                    ⚠️ Clear All Visits
                </button>
                <button type="button" @click="switchToRecordVisit()" 
                        class="px-3 py-1 rounded-lg bg-brand-600 hover:bg-brand-500 text-white font-bold transition flex items-center gap-1 cursor-pointer">
                    <span>+</span> Record New Visit
                </button>
            </div>
        </div>

        <!-- Historical List Container -->
        <div class="max-h-80 overflow-y-auto space-y-2.5 pr-1">
            <template x-if="loadingHistory">
                <div class="py-12 text-center text-slate-400 text-xs">
                    <span class="inline-block animate-spin text-lg mr-2">⏳</span> Loading inspection trail...
                </div>
            </template>
            <template x-if="!loadingHistory && inspectionsList.length === 0">
                <div class="py-12 text-center text-slate-500 text-xs">
                    No physical inspection entries found for this consumer.
                </div>
            </template>
            <template x-for="item in inspectionsList" :key="item.id">
                <div class="p-3 rounded-2xl border transition text-xs"
                     :class="editingInspectionId === item.id ? 'bg-indigo-950/20 border-indigo-500/50' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700'">
                    <!-- Standard Display Row -->
                    <template x-if="editingInspectionId !== item.id">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-2.5">
                                <input type="checkbox" :value="item.id" x-model="selectedInspectionIds" class="mt-1 rounded border-slate-700 bg-slate-800 text-brand-600 focus:ring-0">
                                <div>
                                    <div class="flex items-center gap-2 font-mono">
                                        <span class="font-bold text-sm text-cyan-400" x-text="item.physical_reading + ' kWh'"></span>
                                        <span class="text-slate-500">•</span>
                                        <span class="text-slate-300" x-text="item.inspected_at"></span>
                                        <span class="text-[10px] text-slate-500" x-text="'(' + (item.inspected_at_human || '') + ')'"></span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5" x-text="item.notes || 'No remarks logged.'"></div>
                                    <div class="text-[10px] text-slate-500 mt-1 flex items-center gap-2">
                                        <span x-text="'Logged by: ' + (item.inspected_by_name || 'System')"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button" @click="startEditInspection(item)" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition" title="Edit this entry">✏️</button>
                                <button type="button" @click="deleteSingleInspection(item)" class="p-1.5 rounded-lg bg-rose-950/40 hover:bg-rose-900 text-rose-400 hover:text-rose-200 transition" title="Delete this entry">🗑️</button>
                            </div>
                        </div>
                    </template>
                    <!-- Inline Edit Form Row -->
                    <template x-if="editingInspectionId === item.id">
                        <div class="space-y-2.5">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 uppercase">Physical Reading (kWh)</label>
                                    <input type="number" x-model="editForm.physical_reading" required min="0" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1 text-xs text-white font-mono focus:ring-brand-500">
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 uppercase">Inspection Date</label>
                                    <input type="date" x-model="editForm.inspected_at" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1 text-xs text-white font-mono focus:ring-brand-500">
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase">Remarks / Notes</label>
                                <input type="text" x-model="editForm.notes" placeholder="Update remarks..." class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1 text-xs text-white focus:ring-brand-500">
                            </div>
                            <div class="flex items-center justify-end gap-2 pt-1 border-t border-slate-800">
                                <button type="button" @click="cancelEditInspection()" class="px-3 py-1 rounded-lg text-xs font-bold text-slate-400 hover:text-white">Cancel</button>
                                <button type="button" @click="saveEditInspection(item)" class="px-3 py-1 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white flex items-center gap-1">💾 Save Update</button>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-end pt-3 border-t border-slate-800">
            <button type="button" @click="closeHistoryModal()" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-200">Close</button>
        </div>
    </div>
</div>
