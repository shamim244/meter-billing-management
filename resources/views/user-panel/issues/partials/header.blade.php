<!-- Top Breadcrumb & Actions -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">
            <a href="{{ route('user-panel.index') }}" class="hover:text-brand-600 dark:hover:text-cyan-400 transition">User Hub</a>
            <span>/</span>
            <span class="text-slate-900 dark:text-white">Bug Reports & Tickets</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
            <span>🐞</span>
            <span>My Bug Reports & Tracking</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Track issues you have reported, inspect live triage progress, and read AI agent resolution notes.
        </p>
    </div>

    <button type="button" 
            @click="openNewReportModal()"
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white text-xs font-bold shadow-md shadow-indigo-500/20 transition active:scale-95 cursor-pointer">
        <span>➕</span>
        <span>Report a New Bug</span>
    </button>
</div>
