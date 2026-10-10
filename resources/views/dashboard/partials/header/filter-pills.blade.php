<!-- 1. Status Filter Pills Container -->
<div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">Review:</span>
        <button @click="filterStatus = 'all'; fetchData(1)" :class="filterStatus === 'all' ? 'bg-slate-900 dark:bg-blue-600 text-white shadow-sm font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-3.5 py-1.5 rounded-2xl text-xs font-semibold transition cursor-pointer">
            📋 All (<span x-text="counts.all ?? 0"></span>)
        </button>
        <button @click="filterStatus = 'pending'; fetchData(1)" :class="filterStatus === 'pending' ? 'bg-slate-700 dark:bg-slate-600 text-white shadow-sm font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-3.5 py-1.5 rounded-2xl text-xs font-semibold transition cursor-pointer">
            ⏳ Pending (<span x-text="counts.pending ?? 0"></span>)
        </button>
        <button @click="filterStatus = 'submitted'; fetchData(1)" :class="filterStatus === 'submitted' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/50'" class="px-3.5 py-1.5 rounded-2xl text-xs font-semibold transition cursor-pointer">
            ✅ Submitted (<span x-text="counts.submitted ?? 0"></span>)
        </button>
        <button @click="filterStatus = 'critical'; fetchData(1)" :class="filterStatus === 'critical' ? 'bg-rose-600 text-white shadow-sm font-bold' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/50'" class="px-3.5 py-1.5 rounded-2xl text-xs font-semibold transition cursor-pointer">
            ❌ Critical (<span x-text="counts.critical ?? 0"></span>)
        </button>
        <button @click="filterStatus = 'doubt'; fetchData(1)" :class="filterStatus === 'doubt' ? 'bg-amber-600 text-white shadow-sm font-bold' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/50'" class="px-3.5 py-1.5 rounded-2xl text-xs font-semibold transition cursor-pointer">
            ⚠️ Doubt (<span x-text="counts.doubt ?? 0"></span>)
        </button>
    </div>

    <!-- 1b. Basis Filter Pills (OK, LK, MD, PL, RN) -->
    <div class="flex flex-wrap items-center gap-2 pt-2.5 border-t border-slate-100 dark:border-slate-800/80">
        <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">⚡ Basis:</span>
        <button @click="setBasisFilter('all')" :class="basisFilter === 'all' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition cursor-pointer">
            All Basis
        </button>
        <button @click="setBasisFilter('OK')" :class="basisFilter === 'OK' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/50'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1 cursor-pointer">
            <span>🟢 OK (Normal)</span>
            <span class="text-[10px] opacity-75 font-mono" x-show="counts.basis_ok !== undefined" x-text="'(' + (counts.basis_ok ?? 0) + ')'"></span>
        </button>
        <button @click="setBasisFilter('LK')" :class="basisFilter === 'LK' ? 'bg-amber-600 text-white shadow-xs font-bold' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/50'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1 cursor-pointer">
            <span>🟡 LK (Locked)</span>
            <span class="text-[10px] opacity-75 font-mono" x-show="counts.basis_lk !== undefined" x-text="'(' + (counts.basis_lk ?? 0) + ')'"></span>
        </button>
        <button @click="setBasisFilter('MD')" :class="basisFilter === 'MD' ? 'bg-rose-600 text-white shadow-xs font-bold' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/50'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1 cursor-pointer">
            <span>🟠 MD (Defective)</span>
            <span class="text-[10px] opacity-75 font-mono" x-show="counts.basis_md !== undefined" x-text="'(' + (counts.basis_md ?? 0) + ')'"></span>
        </button>
        <button @click="setBasisFilter('PL')" :class="basisFilter === 'PL' ? 'bg-indigo-600 text-white shadow-xs font-bold' : 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/50'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1 cursor-pointer">
            <span>🔵 PL</span>
            <span class="text-[10px] opacity-75 font-mono" x-show="counts.basis_pl !== undefined" x-text="'(' + (counts.basis_pl ?? 0) + ')'"></span>
        </button>
        <button @click="setBasisFilter('RN')" :class="basisFilter === 'RN' ? 'bg-purple-600 text-white shadow-xs font-bold' : 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 hover:bg-purple-100 dark:hover:bg-purple-900/50'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1 cursor-pointer">
            <span>⚪ RN</span>
            <span class="text-[10px] opacity-75 font-mono" x-show="counts.basis_rn !== undefined" x-text="'(' + (counts.basis_rn ?? 0) + ')'"></span>
        </button>
    </div>
</div>
