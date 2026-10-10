<!-- Sub-Report Navigation Tabs -->
<div class="flex flex-wrap items-center gap-3">
    <a href="{{ route('admin.reports.index', ['month' => $month, 'year' => $year]) }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-lg shadow-indigo-600/30">
        📊 Platform Overview
    </a>
    <a href="{{ route('admin.reports.status_tag', ['month' => $month, 'year' => $year]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
        🏷️ Status & Tag Distribution
    </a>
    <a href="{{ route('admin.reports.quota', ['month' => $month, 'year' => $year]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
        💳 Quota & Overage Leaderboard
    </a>
    <a href="{{ route('admin.reports.flagged', ['month' => $month, 'year' => $year]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
        ⚠️ Consecutive Estimates
    </a>
</div>
