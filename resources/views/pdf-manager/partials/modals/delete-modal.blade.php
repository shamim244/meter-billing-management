<!-- Cycle PDF Delete Confirmation Modal -->
<div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div @click.outside="showDeleteModal = false" class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Modal Header -->
        <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400 flex items-center justify-center text-xl font-bold">
                    🗑️
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Delete Physical PDF Files</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Reclaim disk quota while keeping database ledger safe</p>
                </div>
            </div>
            <button type="button" @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
        </div>

        <div class="p-5 sm:p-6 space-y-4 overflow-y-auto">
            <!-- Summary Info Card -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500 font-semibold uppercase">Target Cycle</span>
                    <span class="font-bold text-slate-900 dark:text-white font-mono text-sm" x-text="deleteModalData.label"></span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500 font-semibold uppercase">Physical PDFs</span>
                    <span class="font-bold text-rose-600 dark:text-rose-400 font-mono" x-text="deleteModalData.pdfCount + ' Files'"></span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500 font-semibold uppercase">Disk Space to Reclaim</span>
                    <span class="font-black text-emerald-600 dark:text-emerald-400 font-mono text-sm" x-text="deleteModalData.totalSize"></span>
                </div>
            </div>

            <!-- Ledger Protection Reassurance Box -->
            <div class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300 space-y-1">
                <div class="flex items-center gap-1.5 font-bold">
                    <span>🛡️</span>
                    <span>100% Database Ledger Protection</span>
                </div>
                <p class="text-[11px] text-emerald-700 dark:text-emerald-400 leading-relaxed">
                    Deleting physical PDF files only frees up server disk storage. <strong>All consumer names, meter numbers, kWh readings, dues amounts, and audit remarks remain permanently safe in the database.</strong>
                </p>
            </div>

            <!-- Scope Options -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Deletion Scope</label>
                
                <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 cursor-pointer hover:border-brand-500 transition">
                    <input type="radio" name="deleteScope" value="all" x-model="deleteModalData.targetScope" class="mt-0.5 text-brand-600 focus:ring-brand-500">
                    <div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white">Delete ALL Physical PDFs in this Cycle</div>
                        <div class="text-[11px] text-slate-500">Reclaims maximum storage space (recommended for completed cycles).</div>
                    </div>
                </label>

                <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 cursor-pointer hover:border-brand-500 transition">
                    <input type="radio" name="deleteScope" value="parsed" x-model="deleteModalData.targetScope" class="mt-0.5 text-brand-600 focus:ring-brand-500">
                    <div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white">Delete ONLY Verified & Parsed Bills</div>
                        <div class="text-[11px] text-slate-500">Keep PDFs for bills that still need manual checking or re-parsing.</div>
                    </div>
                </label>

                <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 cursor-pointer hover:border-brand-500 transition">
                    <input type="radio" name="deleteScope" value="unparsed" x-model="deleteModalData.targetScope" class="mt-0.5 text-brand-600 focus:ring-brand-500">
                    <div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white">Delete ONLY Unparsed / Corrupt Bills</div>
                        <div class="text-[11px] text-slate-500">Removes broken or failed PDF downloads while keeping verified ones.</div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-5 sm:p-6 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5">
            <button type="button" @click="showDeleteModal = false" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 transition text-center">
                Cancel
            </button>
            <button type="button" 
                    @click="confirmCycleDelete()" 
                    :disabled="deleteRunning"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-md shadow-rose-500/20">
                <span x-show="!deleteRunning">🗑️ Confirm & Delete PDFs</span>
                <span x-show="deleteRunning" class="flex items-center gap-1">
                    <span class="animate-spin text-xs">⏳</span> Deleting...
                </span>
            </button>
        </div>
    </div>
</div>
