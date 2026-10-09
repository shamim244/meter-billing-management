<!-- CARD 2: Bill Parser & Extractor (Local Data Extraction) -->
<div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm p-6 space-y-5 flex flex-col justify-between hover:shadow-md transition">
    <div>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-lg">
                    ⚙️
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">2. PDF Parser & Extractor</h2>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">Local PDF Text Data Extraction Engine</p>
                </div>
            </div>
            <span class="px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider"
                  :class="parserRunning ? 'bg-indigo-100 dark:bg-indigo-950/80 text-indigo-800 dark:text-indigo-300 animate-pulse' : (stats.pending_parse === 0 && stats.pdf_bills_count > 0 ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400')"
                  x-text="parserRunning ? '⚙️ Parsing...' : (stats.pending_parse === 0 && stats.pdf_bills_count > 0 ? '✅ All Extracted' : 'Idle')">
            </span>
        </div>

        <!-- Visual Progress Bar -->
        <div class="mt-4 space-y-1.5">
            <div class="flex items-center justify-between text-xs font-mono font-bold">
                <span class="text-slate-600 dark:text-slate-300">Extraction Progress</span>
                <span class="text-indigo-600 dark:text-indigo-400" x-text="(stats.parse_percent || 0) + '% (' + stats.parsed_count + '/' + stats.pdf_bills_count + ')'"></span>
            </div>
            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-3 overflow-hidden p-0.5 border border-slate-200 dark:border-slate-700/60">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-400 h-2 rounded-full transition-all duration-500 shadow-sm" :style="'width: ' + (stats.parse_percent || 0) + '%'"></div>
            </div>
        </div>

        <!-- Live Metrics Grid -->
        <div class="grid grid-cols-3 gap-3 mt-4">
            <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">PDFs on Disk</span>
                <div class="text-lg font-black text-slate-900 dark:text-white mt-0.5 font-mono" x-text="stats.pdf_bills_count">0</div>
            </div>
            <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Extracted</span>
                <div class="text-lg font-black text-indigo-600 dark:text-indigo-400 mt-0.5 font-mono" x-text="stats.parsed_count">0</div>
            </div>
            <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pending Parse</span>
                <div class="text-lg font-black mt-0.5 font-mono" :class="stats.pending_parse > 0 ? 'text-amber-500' : 'text-emerald-500'" x-text="stats.pending_parse">0</div>
            </div>
        </div>
    </div>

    <!-- Parser Actions -->
    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-center gap-2 sm:gap-2.5">
        <button @click="runParser('pending_only')" :disabled="parserRunning || downloaderRunning || stats.pending_parse === 0" class="w-full sm:flex-1 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-500/20 transition flex items-center justify-center gap-1.5">
            <span x-show="!parserRunning">⚙️ Extract Pending (<span x-text="stats.pending_parse"></span>)</span>
            <span x-show="parserRunning" class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Extracting Data...
            </span>
        </button>
        <button @click="runParser('all')" :disabled="parserRunning || downloaderRunning || stats.pdf_bills_count === 0" class="w-full sm:w-auto px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-40 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition text-center" title="Force re-extract all PDFs in this cycle">
            🔄 Re-Parse All
        </button>
    </div>
</div>
