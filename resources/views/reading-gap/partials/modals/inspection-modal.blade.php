<!-- Record Physical Meter Inspection Modal for Reading Gap Hub -->
<div x-show="showInspectionModal" 
     x-cloak 
     id="reading-gap-inspection-modal"
     class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div @click.away="showInspectionModal = false" 
         class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl relative text-left">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <span class="text-xl p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">🔍</span>
                <div>
                    <h3 class="text-sm font-bold text-white">Record Physical Meter Visit</h3>
                    <p class="text-[11px] text-slate-400 font-mono" x-text="(inspectionForm.consumer_name || '') + ' • CA: ' + (inspectionForm.ca_number || '')"></p>
                </div>
            </div>
            <button type="button" @click="showInspectionModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg">✕</button>
        </div>

        <!-- Context Pill -->
        <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 space-y-1.5 text-xs">
            <div class="flex justify-between items-center text-slate-400">
                <span>Latest Posted Reading:</span>
                <span class="font-mono font-bold text-white" x-text="inspectionForm.previous_reading || '0'"></span>
            </div>
            <div class="flex justify-between items-center text-slate-400">
                <span>Current Cached Physical:</span>
                <span class="font-mono font-bold text-cyan-400" x-text="inspectionForm.physical_reading || 'Not Recorded'"></span>
            </div>
        </div>

        <!-- Input Form -->
        <form @submit.prevent="submitInspection()" class="space-y-3.5">
            <div class="space-y-1">
                <label class="text-[11px] font-bold text-slate-300">Physical Meter Reading (Wall Meter)</label>
                <input type="number" 
                       x-model="inspectionForm.physical_reading" 
                       required 
                       min="0"
                       step="1"
                       placeholder="e.g. 5200"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm font-mono text-white focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-300">Inspection Date</label>
                    <input type="date" 
                           x-model="inspectionForm.inspection_date" 
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs font-mono text-white focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-300">Meter Number</label>
                    <input type="text" 
                           x-model="inspectionForm.meter_no" 
                           readonly
                           class="w-full bg-slate-950/50 border border-slate-800 rounded-xl px-3 py-1.5 text-xs font-mono text-slate-400 cursor-not-allowed">
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-[11px] font-bold text-slate-300">Field Notes / Remarks</label>
                <input type="text" 
                       x-model="inspectionForm.notes" 
                       placeholder="e.g. Regular site audit, meter running normal"
                       class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- Footer Buttons -->
            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-800">
                <button type="button" @click="showInspectionModal = false" class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white">Cancel</button>
                <button type="submit" 
                        :disabled="savingInspection"
                        class="px-4 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white flex items-center gap-1.5 transition active:scale-95 disabled:opacity-50">
                    <span x-show="savingInspection" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                    <span>Save Visit</span>
                </button>
            </div>
        </form>
    </div>
</div>
