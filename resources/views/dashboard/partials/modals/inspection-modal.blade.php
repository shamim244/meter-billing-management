<!-- Field Inspection & Physical Meter Modal (PRD-13) -->
<div x-show="showInspectionModal" 
     x-cloak 
     id="inspection-modal"
     class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div @click.away="showInspectionModal = false" 
         class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-5 shadow-2xl relative text-left">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div class="flex items-center gap-2">
                <span class="text-xl">🔍</span>
                <div>
                    <h3 class="text-sm font-bold text-white">Physical Meter Inspection</h3>
                    <p class="text-[11px] text-slate-400 font-mono" x-text="'CA: ' + (selectedInspectionBill?.ca_number || '')"></p>
                </div>
            </div>
            <button type="button" @click="showInspectionModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg">✕</button>
        </div>

        <!-- Current Status Context Pill -->
        <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 space-y-2 text-xs">
            <div class="flex justify-between items-center text-slate-400">
                <span>Last Posted Bill Reading:</span>
                <span class="font-mono font-bold text-white" x-text="selectedInspectionBill?.previous_reading || selectedInspectionBill?.working_reading || '0'"></span>
            </div>
            <div class="flex justify-between items-center text-slate-400">
                <span>Current Cached Physical:</span>
                <span class="font-mono font-bold text-cyan-400" x-text="selectedInspectionBill?.reading_gap_info?.last_physical_reading || 'Not Recorded'"></span>
            </div>
            <template x-if="selectedInspectionBill?.reading_gap_info?.net_gap !== undefined">
                <div class="flex justify-between items-center pt-1 border-t border-slate-800/80 text-[11px]">
                    <span>Current Gap:</span>
                    <span class="font-mono font-bold" 
                          :class="selectedInspectionBill.reading_gap_info.net_gap < 0 ? 'text-amber-400' : (selectedInspectionBill.reading_gap_info.net_gap > 0 ? 'text-rose-400' : 'text-emerald-400')"
                          x-text="(selectedInspectionBill.reading_gap_info.net_gap > 0 ? '+' : '') + selectedInspectionBill.reading_gap_info.net_gap + ' kWh'"></span>
                </div>
            </template>
            <div class="flex justify-end pt-1 border-t border-slate-800/60 text-[10px]">
                <a :href="'/reading-gap?ca=' + (selectedInspectionBill?.ca_number || '')" 
                   class="text-indigo-400 hover:text-indigo-300 hover:underline flex items-center gap-1 font-semibold">
                    <span>📜</span> View & Manage Past Visits →
                </a>
            </div>
        </div>

        <!-- Input Form -->
        <form @submit.prevent="savePhysicalInspection()" class="space-y-4">
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-300">Physical Meter Reading (Wall Meter)</label>
                <input type="number" 
                       x-model="inspectionForm.physical_reading" 
                       required 
                       min="0"
                       step="1"
                       placeholder="e.g. 5200"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm font-mono text-white focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-300">Inspection Date</label>
                    <input type="date" 
                           x-model="inspectionForm.inspection_date" 
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs font-mono text-white focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-300">Hold Mode (If Over-Posted)</label>
                    <select x-model="inspectionForm.gap_recovery_mode" 
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs font-mono text-white focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="hold_exact">+0 Hold Exact</option>
                        <option value="hold_advance">+1 Unit Advance</option>
                        <option value="amortize_safe">Safe Amortize</option>
                    </select>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-300">Field Notes / Remarks</label>
                <input type="text" 
                       x-model="inspectionForm.notes" 
                       placeholder="e.g. Inspected on field visit, meter running normal"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- Footer Buttons -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                <button type="button" @click="showInspectionModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-white">Cancel</button>
                <button type="submit" 
                        :disabled="isInspectionSubmitting"
                        class="px-5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white flex items-center gap-1.5 transition active:scale-95 disabled:opacity-50">
                    <span x-show="isInspectionSubmitting" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                    <span>Save Inspection</span>
                </button>
            </div>
        </form>
    </div>
</div>
