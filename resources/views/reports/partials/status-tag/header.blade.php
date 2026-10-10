<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.usage', ['month' => $month, 'year' => $year]) }}" class="text-xs font-bold text-blue-600 dark:text-cyan-400 hover:underline">
                ← ROI Overview
            </a>
        </div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5 mt-1">
            <span>🏷️</span> Monthly Status & Tag Report
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Detailed review status and tag distribution with drill-down consumer ledger records.
        </p>
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('reports.status_tag.export_csv', ['month' => $month, 'year' => $year, 'mru_id' => $mruId, 'status' => $status, 'tag' => $tag]) }}" 
           class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-emerald-600/20">
            <span>📄</span> Export CSV Report
        </a>
    </div>
</div>
