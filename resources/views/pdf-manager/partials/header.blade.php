<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-600 p-0.5 shadow-lg shadow-brand-500/20 flex items-center justify-center text-xl text-white">
            📑
        </div>
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">PDF Document Management Center</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Inspect, batch export, re-parse, upload, and monitor physical electricity bill PDFs</p>
        </div>
    </div>

    <!-- Header Quick Actions -->
    <div class="flex items-center gap-2 flex-wrap">
        <button type="button" 
                @click="$dispatch('open-health-modal')"
                class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition flex items-center gap-1.5 shadow-sm">
            <span>🩺</span>
            <span>Storage Health Check</span>
        </button>

        <button type="button" 
                @click="$dispatch('open-upload-modal')"
                class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white font-bold text-xs transition flex items-center gap-1.5 shadow-md shadow-brand-500/20">
            <span>📤</span>
            <span>Upload PDFs / ZIP</span>
        </button>
    </div>
</div>
