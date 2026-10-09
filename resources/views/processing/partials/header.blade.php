<!-- Minimal Header Bar -->
<div class="bg-white dark:bg-slate-900 px-6 py-5 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-950/80 text-blue-600 dark:text-cyan-400 flex items-center justify-center text-lg font-bold">
            ⚡
        </div>
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                Data Processing Center
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                Automated Bill Downloader & PDF Extractor Hub
            </p>
        </div>
    </div>

    <div class="flex items-center gap-2.5">
        <button @click="showCycleModal = true" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 text-blue-700 dark:text-cyan-300 rounded-2xl text-xs font-bold border border-blue-200 dark:border-blue-800/80 transition" title="Start a new billing cycle for any MRU">
            <span>⚡ + New Cycle</span>
        </button>
        <a :href="getDashboardUrl()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white rounded-2xl text-xs font-bold transition shadow-sm" title="View Month Dashboard">
            <span>📂 Open Dashboard →</span>
        </a>
    </div>
</div>
