<!-- MODAL: Bulk Paste CAs -->
<div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div @click.outside="showImportModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-base">
                    📥
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Bulk Import CA Numbers</h3>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">Paste plain CAs, CSV rows, or TSV data</p>
                </div>
            </div>
            <button @click="showImportModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
        </div>

        <form method="POST" action="{{ route('mrus.consumers.import', $mru) }}" class="overflow-y-auto p-4 sm:p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Paste CA Numbers / Master Data:</label>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mb-2">Supported: Plain CAs, or CSV/TSV with columns (<code>CA Number, Name, Tariff, Basis, Amount, Meter, Initial Reading, Mobile, Address</code>)</p>
                <textarea name="ca_data" x-model="bulkImportText" rows="7" placeholder="10230046961, Ramesh Kumar, DS-II, OK, 450.00, 3808220, 1000, 9876543210, Village Area&#10;102300783538, Suresh Devi, DS-II, LK, 320.00, 3808221, 9876543211&#10;102300783541" required class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-3 focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Detected Lines: <strong class="font-mono text-slate-900 dark:text-white" x-text="detectedLinesCount">0</strong></span>
                <span class="text-slate-400">Non-numeric headers skipped</span>
            </div>
            <div class="pt-4 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-2.5 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="showImportModal = false" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-center">Cancel</button>
                <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition text-center">Import CAs</button>
            </div>
        </form>
    </div>
</div>
