<!-- Top Toolbar & Navigation -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl">
    <div>
        <h1 class="text-xl font-black text-white flex items-center gap-3">
            <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 text-lg">⚡</span>
            NBPDCL Billing & Extraction Engine
        </h1>
        <p class="text-xs text-slate-400 mt-1">
            Configure download drivers, automated fallbacks, and layout signature detection engines for NBPDCL consumer bills.
        </p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.bills.index') }}" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 rounded-xl text-xs font-bold border border-slate-700/60 transition flex items-center gap-2">
            <span>📑</span>
            <span>All Bills Inspector</span>
        </a>
        <form action="{{ route('admin.bills.engine-settings.reset') }}" method="POST" onsubmit="return confirm('Reset all NBPDCL download & extraction engine configurations to factory defaults?');">
            @csrf
            <button type="submit" class="px-4 py-2 bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 rounded-xl text-xs font-bold border border-rose-800/40 transition">
                Reset Defaults
            </button>
        </form>
    </div>
</div>
