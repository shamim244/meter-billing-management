{{-- Header & Breadcrumb --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-400 transition">Admin Dashboard</a>
            <span>/</span>
            <span class="text-indigo-400">Disaster Recovery</span>
        </div>
        <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-3">
            <span>💾 Disaster Recovery & Backups Cockpit</span>
            <span class="text-xs font-mono font-normal px-2.5 py-1 rounded-full bg-indigo-950 text-indigo-300 border border-indigo-800">
                Live System Health
            </span>
        </h1>
        <p class="text-xs text-slate-400 mt-1">Transaction-safe database streaming, chunked PDF archiving & automated retention.</p>
    </div>

    {{-- Quick Top Actions --}}
    <div class="flex items-center gap-2 flex-wrap">
        {{-- Run Retention Cleanup --}}
        <form method="POST" action="{{ route('admin.backups.clean') }}" onsubmit="return confirm('Prune expired backups according to retention schedule (7d daily, 4w weekly, 6m monthly)?')">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-300 bg-slate-800/80 hover:bg-slate-700 border border-slate-700 transition">
                <span>🧹</span>
                <span>Run Retention Clean</span>
            </button>
        </form>
    </div>
</div>
