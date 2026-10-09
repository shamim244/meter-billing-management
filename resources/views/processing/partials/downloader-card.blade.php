<!-- CARD 1: Bill Downloader (Network I/O) -->
<div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm p-6 space-y-5 flex flex-col justify-between hover:shadow-md transition">
    <div>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-lg">
                    📥
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">1. Bill Downloader</h2>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">BSPHCL API Multi-cURL Network Pipeline</p>
                </div>
            </div>
            <span class="px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider"
                  :class="downloaderRunning ? 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 animate-pulse' : (stats.missing_downloads === 0 && stats.total_cas > 0 ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400')"
                  x-text="downloaderRunning ? '⏳ Downloading...' : (stats.missing_downloads === 0 && stats.total_cas > 0 ? '✅ Up to date' : 'Idle')">
            </span>
        </div>

        <!-- Visual Progress Bar -->
        <div class="mt-4 space-y-1.5">
            <div class="flex items-center justify-between text-xs font-mono font-bold">
                <span class="text-slate-600 dark:text-slate-300">Download Progress</span>
                <span class="text-blue-600 dark:text-cyan-400" x-text="(stats.download_percent || 0) + '% (' + stats.downloaded_count + '/' + stats.total_cas + ')'"></span>
            </div>
            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-3 overflow-hidden p-0.5 border border-slate-200 dark:border-slate-700/60">
                <div class="bg-gradient-to-r from-blue-600 to-cyan-400 h-2 rounded-full transition-all duration-500 shadow-sm" :style="'width: ' + (stats.download_percent || 0) + '%'"></div>
            </div>
        </div>

        <!-- Live Metrics Grid -->
        <div class="grid grid-cols-3 gap-3 mt-4">
            <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total CAs</span>
                <div class="text-lg font-black text-slate-900 dark:text-white mt-0.5 font-mono" x-text="stats.total_cas">0</div>
            </div>
            <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Downloaded</span>
                <div class="text-lg font-black text-blue-600 dark:text-cyan-400 mt-0.5 font-mono" x-text="stats.downloaded_count">0</div>
            </div>
            <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Missing</span>
                <div class="text-lg font-black mt-0.5 font-mono" :class="stats.missing_downloads > 0 ? 'text-amber-500' : 'text-emerald-500'" x-text="stats.missing_downloads">0</div>
            </div>
        </div>
    </div>

    <!-- Downloader Actions -->
    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-center gap-2 sm:gap-2.5">
        <button @click="runDownloader('all')" :disabled="downloaderRunning || parserRunning || stats.total_cas === 0" class="w-full sm:flex-1 py-2.5 px-4 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-1.5">
            <span x-show="!downloaderRunning">⚡ Download All CAs</span>
            <span x-show="downloaderRunning" class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Downloading...
            </span>
        </button>
        <button @click="runDownloader('missing_only')" :disabled="downloaderRunning || parserRunning || stats.missing_downloads === 0" class="w-full sm:w-auto px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-40 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition text-center" title="Download only missing or pending CAs">
            🔄 Sync Missing (<span x-text="stats.missing_downloads"></span>)
        </button>
    </div>
</div>
