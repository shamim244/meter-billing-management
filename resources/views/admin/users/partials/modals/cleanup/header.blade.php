<!-- Modal Header -->
<div class="flex items-center justify-between border-b border-slate-800 pb-3">
    <div class="flex items-center gap-2.5">
        <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20 text-base">🧹</span>
        <div>
            <h3 class="text-base font-bold text-white">Data & Storage Management Console</h3>
            <p class="text-[11px] text-slate-400">Targeted cleanup options for <span class="text-slate-200 font-semibold">{{ $user->name }}</span> (ID: #{{ $user->id }})</p>
        </div>
    </div>
    <button type="button" @click="showCleanupModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
</div>

<!-- Navigation Tabs -->
<div class="flex items-center gap-1.5 p-1 bg-slate-950 rounded-2xl border border-slate-800 text-xs font-bold overflow-x-auto">
    <button type="button" @click="cleanupTab = 'pdfs'" :class="cleanupTab === 'pdfs' ? 'bg-purple-600 text-white shadow' : 'text-slate-400 hover:text-white'" class="flex-1 py-2 px-3 rounded-xl transition flex items-center justify-center gap-1.5 whitespace-nowrap">
        <span>📄</span> Clean PDFs
    </button>
    <button type="button" @click="cleanupTab = 'mrus'" :class="cleanupTab === 'mrus' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'" class="flex-1 py-2 px-3 rounded-xl transition flex items-center justify-center gap-1.5 whitespace-nowrap">
        <span>🗂️</span> Clean MRUs
    </button>
    <button type="button" @click="cleanupTab = 'bills'" :class="cleanupTab === 'bills' ? 'bg-cyan-600 text-white shadow' : 'text-slate-400 hover:text-white'" class="flex-1 py-2 px-3 rounded-xl transition flex items-center justify-center gap-1.5 whitespace-nowrap">
        <span>👥</span> Clean Bills
    </button>
    <button type="button" @click="cleanupTab = 'purge'" :class="cleanupTab === 'purge' ? 'bg-rose-600 text-white shadow' : 'text-rose-400 hover:text-rose-300'" class="flex-1 py-2 px-3 rounded-xl transition flex items-center justify-center gap-1.5 whitespace-nowrap">
        <span>⚠️</span> Full Purge
    </button>
</div>
