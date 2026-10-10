<!-- 4 Core Metrics Grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-300 flex items-center justify-center text-xl shrink-0">
            🗂️
        </div>
        <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">MRU Workspaces</div>
            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-0.5">{{ number_format($stats['mru_count']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Active areas</div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-300 flex items-center justify-center text-xl shrink-0">
            👥
        </div>
        <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Consumers</div>
            <div class="text-2xl font-black text-cyan-600 dark:text-cyan-400 font-mono mt-0.5">{{ number_format($stats['consumer_count']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Master ledger CAs</div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-300 flex items-center justify-center text-xl shrink-0">
            📄
        </div>
        <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Bills Processed</div>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-0.5">{{ number_format($stats['bills_count']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">{{ number_format($stats['pdf_count']) }} PDFs on disk</div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-300 flex items-center justify-center text-xl shrink-0">
            💾
        </div>
        <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Storage Used</div>
            <div class="text-2xl font-black text-purple-600 dark:text-purple-400 font-mono mt-0.5">
                {{ round($stats['storage_used_bytes'] / (1024 * 1024), 1) }} MB
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">
                {{ $stats['storage_percent'] }}% of {{ round($stats['storage_limit_bytes'] / (1024 * 1024)) }} MB
            </div>
        </div>
    </div>
</div>
