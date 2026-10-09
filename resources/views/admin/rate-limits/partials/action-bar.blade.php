{{-- Save Action Bar --}}
<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
    <div class="text-xs text-slate-400">
        Changes take effect immediately across all active API clients.
    </div>
    <div class="flex items-center gap-3 w-full sm:w-auto">
        <button type="button" @click="openResetModal()" class="px-5 py-2.5 rounded-xl text-xs font-bold text-rose-400 hover:text-rose-300 bg-rose-950/30 hover:bg-rose-950/60 border border-rose-500/30 transition">
            Reset Defaults
        </button>
        <button type="submit" class="flex-1 sm:flex-initial px-6 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-gradient-to-r from-emerald-400 to-cyan-400 hover:from-emerald-300 hover:to-cyan-300 shadow-lg shadow-emerald-500/20 transition">
            Save Rate Limit Settings
        </button>
    </div>
</div>
