<!-- Multi-Select Sticky Batch Actions Bar -->
<div class="bg-slate-900 text-white p-4 rounded-2xl shadow-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border border-slate-800"
     x-show="selectedIds.length > 0"
     x-cloak
     x-transition>
    <div class="flex items-center gap-3">
        <span class="w-7 h-7 rounded-xl bg-brand-500 text-white font-black text-xs flex items-center justify-center font-mono" x-text="selectedIds.length"></span>
        <span class="text-xs font-bold text-slate-200">PDFs Selected</span>
        <button type="button" @click="selectedIds = []" class="text-xs text-slate-400 hover:text-white underline">Clear</button>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
        <button type="button" 
                @click="triggerBatchDownload()" 
                class="px-3 py-1.5 bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5">
            <span>📦 Download ZIP</span>
        </button>

        <button type="button" 
                @click="triggerBatchReparse()" 
                :disabled="actionRunning"
                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5">
            <span>⚡ Re-parse</span>
        </button>

        <button type="button" 
                @click="triggerBatchRedownload()" 
                :disabled="actionRunning"
                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5">
            <span>🔄 Re-download</span>
        </button>

        <button type="button" 
                @click="triggerBatchDelete()" 
                :disabled="actionRunning"
                class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5">
            <span>🗑️ Delete PDFs</span>
        </button>
    </div>
</div>
